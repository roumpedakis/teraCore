<?php

namespace App\Core;

class UserModulePurchaseRepository
{
    private Database $db;
    private string $table = 'user_module_purchases';

    public function __construct()
    {
        $this->db = Database::getInstance();
    }

    public function getUserPurchases(int $userId): array
    {
        $sql = "SELECT module_name, status, price, currency, billing_period, purchased_at, canceled_at
                FROM {$this->table}
                WHERE user_id = :user_id";

        $results = $this->db->fetchAll($sql, ['user_id' => $userId]);
        $purchases = [];
        foreach ($results as $row) {
            $purchases[$row['module_name']] = [
                'module_name' => $row['module_name'],
                'status' => $row['status'],
                'price' => (float)$row['price'],
                'currency' => $row['currency'],
                'billing_period' => $row['billing_period'],
                'purchased_at' => $row['purchased_at'],
                'canceled_at' => $row['canceled_at'],
            ];
        }

        return $purchases;
    }

    public function upsertPurchase(int $userId, string $moduleName, float $price, string $currency, string $billingPeriod): bool
    {
        $sql = "INSERT INTO {$this->table}
                (user_id, module_name, status, price, currency, billing_period)
                VALUES (:user_id, :module_name, 'active', :price, :currency, :billing_period)
                ON DUPLICATE KEY UPDATE
                status = 'active',
                price = VALUES(price),
                currency = VALUES(currency),
                billing_period = VALUES(billing_period),
                purchased_at = IF(status <> 'active', CURRENT_TIMESTAMP, purchased_at),
                canceled_at = NULL,
                updated_at = CURRENT_TIMESTAMP";

        return (bool)$this->db->execute($sql, [
            'user_id' => $userId,
            'module_name' => $moduleName,
            'price' => $price,
            'currency' => $currency,
            'billing_period' => $billingPeriod,
        ]);
    }

    public function cancelPurchase(int $userId, string $moduleName): bool
    {
        $sql = "UPDATE {$this->table}
                SET status = 'canceled', canceled_at = CURRENT_TIMESTAMP, updated_at = CURRENT_TIMESTAMP
                WHERE user_id = :user_id AND module_name = :module_name";

        return (bool)$this->db->execute($sql, [
            'user_id' => $userId,
            'module_name' => $moduleName,
        ]);
    }
}
