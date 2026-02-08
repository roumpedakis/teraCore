<?php

namespace App\Modules\Users\User;

use App\Core\Classes\BaseModel;

class Model extends BaseModel
{
    protected string $table = 'users';

    /**
     * Get attributes with hidden fields removed
     */
    public function toArray(): array
    {
        $array = $this->attributes;
        unset($array['password']); // Never expose password
        return $array;
    }

    /**
     * Get user's role relationship
     */
    public function getRole(): ?array
    {
        // This would use a join query to get role
        // For now, returning null
        return null;
    }

    /**
     * Check if user is active
     */
    public function isActive(): bool
    {
        return (bool)$this->getAttribute('is_active');
    }

    /**
     * Get full name
     */
    public function getFullName(): string
    {
        $firstName = $this->getAttribute('first_name') ?? '';
        $lastName = $this->getAttribute('last_name') ?? '';
        return trim("$firstName $lastName") ?: $this->getAttribute('username');
    }
}
