<?php

namespace App\Modules\Core\Admin;

use App\Core\Classes\BaseView;

/**
 * Admin View
 * Renders admin data in requested format (JSON, XML, HTML)
 */
class View extends BaseView
{
    /**
     * Format admin data for response
     */
    public function format(array $data = []): array
    {
        if (empty($data)) {
            return [
                'success' => false,
                'message' => 'No data to render'
            ];
        }

        // If data contains 'success' key (from controller), return as-is
        if (isset($data['success'])) {
            return $data;
        }

        // Otherwise wrap in response envelope
        return [
            'success' => true,
            'data' => $data
        ];
    }
}
