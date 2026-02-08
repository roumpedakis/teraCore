<?php

namespace Tests\Unit;

use TestCase;
use App\Core\Classes\BaseRepository;
use App\Core\Database;
use App\Modules\Core\Admin\Model as AdminModel;

class FakeDatabase extends Database
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

class FakeAdminRepository extends BaseRepository
{
    public array $lastWhere = [];
    public ?AdminModel $nextModel = null;
    public array $models = [];
    public array $lastUpdate = [];

    public function where(string $column, string $operator, mixed $value): self
    {
        $this->lastWhere = compact('column', 'operator', 'value');
        return $this;
    }

    public function firstModel(): ?AdminModel
    {
        return $this->nextModel;
    }

    public function getModels(): array
    {
        return $this->models;
    }

    public function update(mixed $id, array $data): int
    {
        $this->lastUpdate = ['id' => $id, 'data' => $data];
        return 1;
    }
}

class AdminModelTest extends TestCase
{
    public function test_get_by_name_returns_model(): void
    {
        $repo = new FakeAdminRepository(new FakeDatabase());
        $model = new AdminModel();
        $expected = new AdminModel();
        $expected->fill(['id' => 1, 'name' => 'Test', 'status' => 'active']);
        $repo->nextModel = $expected;

        $model->setRepository($repo);
        $result = $model->getByName('Test');

        assert_true($result instanceof AdminModel);
        assert_equal('name', $repo->lastWhere['column']);
        assert_equal('Test', $repo->lastWhere['value']);
        assert_equal('Test', $result->name);
    }

    public function test_is_active_checks_status(): void
    {
        $model = new AdminModel();
        $model->status = 'active';
        assert_true($model->isActive());

        $model->status = 'inactive';
        assert_false($model->isActive());
    }

    public function test_deactivate_updates_status(): void
    {
        $repo = new FakeAdminRepository(new FakeDatabase());
        $model = new AdminModel();
        $model->fill(['id' => 10, 'status' => 'active']);
        $model->setRepository($repo);

        $result = $model->deactivate();

        assert_true($result);
        assert_equal('inactive', $model->status);
        assert_equal(10, $repo->lastUpdate['id']);
        assert_equal('inactive', $repo->lastUpdate['data']['status']);
    }

    public function test_suspend_updates_status(): void
    {
        $repo = new FakeAdminRepository(new FakeDatabase());
        $model = new AdminModel();
        $model->fill(['id' => 11, 'status' => 'active']);
        $model->setRepository($repo);

        $result = $model->suspend();

        assert_true($result);
        assert_equal('suspended', $model->status);
        assert_equal(11, $repo->lastUpdate['id']);
        assert_equal('suspended', $repo->lastUpdate['data']['status']);
    }

    public function test_get_active_uses_repo(): void
    {
        $repo = new FakeAdminRepository(new FakeDatabase());
        $active = new AdminModel();
        $active->fill(['id' => 1, 'status' => 'active']);
        $repo->models = [$active];

        $results = AdminModel::getActive($repo);

        assert_equal(1, count($results));
        assert_true($results[0] instanceof AdminModel);
    }
}

require_once __DIR__ . '/../bootstrap.php';
$test = new AdminModelTest();
$test->run();
