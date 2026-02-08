<?php

namespace App\Core\Classes;

use App\Core\Libraries\Sanitizer;
use App\Core\ResponseFilter;
use App\Core\Logger;

abstract class BaseController
{
    protected object $view;
    protected object $repository;
    protected bool $useFiltering = true;
    protected bool $useSensitiveDataFilter = false;

    /**
     * Constructor
     */
    public function __construct()
    {
        // To be set by Factory
    }

    /**
     * Create new record (POST)
     */
    public function create(array $data): mixed
    {
        try {
            // Sanitize input data
            $data = $this->sanitizeInput($data);
            
            // Add created_by if user is authenticated
            if (isset($_SESSION['user_id'])) {
                $data['created_by'] = $_SESSION['user_id'];
            }
            
            // Insert record
            $this->repository->insert($data);
            $id = $this->repository->db->lastInsertId();
            
            Logger::info("Record created", [
                'table' => $this->repository->getTable(),
                'id' => $id
            ]);
            
            return $this->view->render([
                'success' => true,
                'message' => 'Created successfully',
                'id' => $id
            ]);
        } catch (\Exception $e) {
            Logger::error("Create failed", [
                'table' => $this->repository->getTable(),
                'error' => $e->getMessage()
            ]);
            
            return $this->view->render([
                'success' => false,
                'error' => 'Failed to create record'
            ]);
        }
    }

    /**
     * Read single record (GET /:id)
     */
    public function read(string $id): mixed
    {
        try {
            $id = Sanitizer::sanitizeInt($id);
            $record = $this->repository->findById($id);
            
            if (!$record) {
                return $this->view->render([
                    'success' => false,
                    'error' => 'Record not found'
                ]);
            }
            
            // Apply sensitive data filtering if enabled
            if ($this->useSensitiveDataFilter) {
                $record = ResponseFilter::filter($record);
            }
            
            return $this->view->render($record);
        } catch (\Exception $e) {
            Logger::error("Read failed", [
                'table' => $this->repository->getTable(),
                'id' => $id,
                'error' => $e->getMessage()
            ]);
            
            return $this->view->render([
                'success' => false,
                'error' => 'Failed to read record'
            ]);
        }
    }

    /**
     * Read all records (GET)
     */
    public function readAll(array $filters = []): mixed
    {
        try {
            if ($this->useFiltering) {
                // Get query parameters for filtering/pagination
                $params = $_GET;
                
                // Get paginated results
                $result = $this->repository->getPaginated($params);
                
                // Apply sensitive data filtering if enabled
                if ($this->useSensitiveDataFilter && !empty($result['data'])) {
                    $result['data'] = array_map(function($record) {
                        return ResponseFilter::filter($record);
                    }, $result['data']);
                }
                
                return $this->view->render($result);
            } else {
                // Legacy: no filtering
                $records = $this->repository->findAll();
                
                if ($this->useSensitiveDataFilter) {
                    $records = array_map(function($record) {
                        return ResponseFilter::filter($record);
                    }, $records);
                }
                
                return $this->view->render($records);
            }
        } catch (\Exception $e) {
            Logger::error("ReadAll failed", [
                'table' => $this->repository->getTable(),
                'error' => $e->getMessage()
            ]);
            
            return $this->view->render([
                'success' => false,
                'error' => 'Failed to read records'
            ]);
        }
    }

    /**
     * Update record (PUT /:id)
     */
    public function update(string $id, array $data): mixed
    {
        try {
            $id = Sanitizer::sanitizeInt($id);
            
            // Check if record exists
            $existing = $this->repository->findById($id);
            if (!$existing) {
                return $this->view->render([
                    'success' => false,
                    'error' => 'Record not found'
                ]);
            }
            
            // Sanitize input data
            $data = $this->sanitizeInput($data);
            
            // Add updated_by if user is authenticated
            if (isset($_SESSION['user_id'])) {
                $data['updated_by'] = $_SESSION['user_id'];
            }
            
            // Update record
            $this->repository->update($id, $data);
            
            Logger::info("Record updated", [
                'table' => $this->repository->getTable(),
                'id' => $id
            ]);
            
            return $this->view->render([
                'success' => true,
                'message' => 'Updated successfully'
            ]);
        } catch (\Exception $e) {
            Logger::error("Update failed", [
                'table' => $this->repository->getTable(),
                'id' => $id,
                'error' => $e->getMessage()
            ]);
            
            return $this->view->render([
                'success' => false,
                'error' => 'Failed to update record'
            ]);
        }
    }

    /**
     * Delete record (DELETE /:id)
     */
    public function delete(string $id): mixed
    {
        try {
            $id = Sanitizer::sanitizeInt($id);
            
            // Check if record exists
            $existing = $this->repository->findById($id);
            if (!$existing) {
                return $this->view->render([
                    'success' => false,
                    'error' => 'Record not found'
                ]);
            }
            
            // Delete record
            $this->repository->delete($id);
            
            Logger::info("Record deleted", [
                'table' => $this->repository->getTable(),
                'id' => $id
            ]);
            
            return $this->view->render([
                'success' => true,
                'message' => 'Deleted successfully'
            ]);
        } catch (\Exception $e) {
            Logger::error("Delete failed", [
                'table' => $this->repository->getTable(),
                'id' => $id,
                'error' => $e->getMessage()
            ]);
            
            return $this->view->render([
                'success' => false,
                'error' => 'Failed to delete record'
            ]);
        }
    }

    /**
     * Sanitize input data
     */
    protected function sanitizeInput(array $data): array
    {
        $sanitized = [];
        foreach ($data as $key => $value) {
            if (is_string($value)) {
                $sanitized[$key] = Sanitizer::sanitizeString($value);
            } elseif (is_numeric($value)) {
                $sanitized[$key] = is_float($value) ? (float)$value : (int)$value;
            } elseif (is_array($value)) {
                $sanitized[$key] = $this->sanitizeInput($value);
            } else {
                $sanitized[$key] = $value;
            }
        }
        return $sanitized;
    }

    /**
     * Set repository
     */
    public function setRepository(object $repository): void
    {
        $this->repository = $repository;
    }

    /**
     * Set view
     */
    public function setView(object $view): void
    {
        $this->view = $view;
    }
}
