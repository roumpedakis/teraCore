<?php

namespace App\Core\Classes;

abstract class BaseController
{
    protected object $view;
    protected object $repository;

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
        $this->repository->insert($data);
        return $this->view->render(['success' => true, 'message' => 'Created']);
    }

    /**
     * Read single record (GET /:id)
     */
    public function read(string $id): mixed
    {
        $record = $this->repository->findById($id);
        return $this->view->render($record);
    }

    /**
     * Read all records (GET)
     */
    public function readAll(array $filters = []): mixed
    {
        $records = $this->repository->findAll();
        return $this->view->render($records);
    }

    /**
     * Update record (PUT /:id)
     */
    public function update(string $id, array $data): mixed
    {
        $this->repository->update($id, $data);
        return $this->view->render(['success' => true, 'message' => 'Updated']);
    }

    /**
     * Delete record (DELETE /:id)
     */
    public function delete(string $id): mixed
    {
        $this->repository->delete($id);
        return $this->view->render(['success' => true, 'message' => 'Deleted']);
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
