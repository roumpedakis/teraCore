<?php

namespace App\Modules\Users\User;

use App\Core\Classes\BaseController;
use App\Core\Libraries\Encrypt;
use App\Core\Libraries\Sanitizer;

class Controller extends BaseController
{
        /**
         * Constructor - Enable sensitive data filtering for users
         */
        public function __construct()
        {
            parent::__construct();
            $this->useSensitiveDataFilter = true; // Filter passwords, tokens, etc.
        }

    /**
     * Create new user
        * Override to add password hashing and email validation
     */
    public function create(array $data): array
    {
        // Custom validation for users
        if (empty($data['email']) || !Sanitizer::validateEmail($data['email'])) {
            return ['success' => false, 'error' => 'Invalid email address'];
        }
        
        if (empty($data['password']) || !Sanitizer::validateMinLength($data['password'], 6)) {
            return ['success' => false, 'error' => 'Password must be at least 6 characters'];
        }
        
        // Hash password before storage
        $data['password'] = Encrypt::hashPassword($data['password']);
        
        // Normalize email
        $data['email'] = Sanitizer::sanitizeEmail($data['email']);
        
        // Call parent create method (handles sanitization, created_by, etc.)
        return parent::create($data);
    }

    /**
     * Update user
     * Override to add password hashing if password is being updated
     */
    public function update(string $id, array $data): array
    {
        // Validate email if provided
        if (isset($data['email']) && !Sanitizer::validateEmail($data['email'])) {
            return ['success' => false, 'error' => 'Invalid email address'];
        }
        
        // Validate password length if provided
        if (isset($data['password']) && !Sanitizer::validateMinLength($data['password'], 6)) {
            return ['success' => false, 'error' => 'Password must be at least 6 characters'];
        }
        
        // Hash password if provided
        if (isset($data['password'])) {
            $data['password'] = Encrypt::hashPassword($data['password']);
        }
        
        // Normalize email if provided
        if (isset($data['email'])) {
            $data['email'] = Sanitizer::sanitizeEmail($data['email']);
        }
        
    // Call parent update method (handles sanitization, updated_by, etc.)
    return parent::update($id, $data);
    }
}
