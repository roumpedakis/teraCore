<?php

namespace Tests\Unit;

use TestCase;
use App\Core\AuthController;
use App\Core\JWT;
use App\Core\Database;
use App\Modules\Core\User\Repository as UserRepository;

class FakeAuthDatabase extends Database
{
    public function __construct()
    {
    }

    public function execute(string $query, array $params = []): int
    {
        return 1;
    }

    public function fetch(string $query, array $params = []): ?array
    {
        return null;
    }

    public function fetchAll(string $query, array $params = []): array
    {
        return [];
    }

    public function lastInsertId(): string
    {
        return '1';
    }
}

class FakeUserRepository extends UserRepository
{
    public array $users = [];
    public array $lastUpdate = [];

    public function __construct()
    {
    }

    public function findById(mixed $id): ?array
    {
        return $this->users[$id] ?? null;
    }

    public function update(mixed $id, array $data): int
    {
        $this->lastUpdate = ['id' => $id, 'data' => $data];
        return 1;
    }
}

class AuthControllerTest extends TestCase
{
    public function test_refresh_uses_find_by_id(): void
    {
        $repo = new FakeUserRepository();
        $token = JWT::generateRefreshToken(1);
        $repo->users[1] = ['id' => 1, 'refresh_token' => $token];

        $controller = new AuthController(new FakeAuthDatabase(), $repo);
        $result = $controller->refresh(['refresh_token' => $token]);

        assert_true($result['success']);
        assert_equal(1, $repo->lastUpdate['id']);
        assert_array_key_exists('token_expires_at', $repo->lastUpdate['data']);
    }
}

require_once __DIR__ . '/../bootstrap.php';
$test = new AuthControllerTest();
$test->run();
