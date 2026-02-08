# Plan: TeraCore API Enhancement - Field Types, Filtering & Testing

## TL;DR

Comprehensive enhancement of TeraCore API to add:
1. **18 new HTML field types** (textarea, select, checkbox, email, date, file, etc.)
2. **Centralized base schema** with common fields (id, reference_id, uid, created_at, etc.)
3. **API filtering & pagination** support (?limit=10&offset=0&orderBy=name&name=takis)
4. **Controller refactoring** - eliminate code duplication using BaseController
5. **Comprehensive test suite** - all field types, endpoints, filters

**Decision:** Simple query parameter format for filtering (not complex REST standard)  
**Decision:** Refactor all controllers to use BaseController (DRY principle)  
**Decision:** Create base schema inherited by all entities

---

## Steps

### 1. Create Field Type Classes & Configuration

**Create field type classes** in [app/core/fields/](app/core/fields/)

New classes to create:
```
app/core/fields/
├── BaseField.php           (Abstract base class)
├── TextField.php           (Already referenced but missing)
├── TextArea.php            ⭐ NEW
├── NumberBox.php           (Already referenced)
├── Price.php               (Already referenced)
├── EmailField.php          ⭐ NEW
├── UrlField.php            ⭐ NEW
├── TelField.php            ⭐ NEW
├── PasswordField.php       ⭐ NEW
├── DateField.php           ⭐ NEW
├── TimeField.php           ⭐ NEW
├── DateTimeField.php       ⭐ NEW
├── ColorField.php          ⭐ NEW
├── CheckboxField.php       ⭐ NEW
├── RadioField.php          ⭐ NEW
├── SelectField.php         ⭐ NEW
├── FileField.php           ⭐ NEW
└── HiddenField.php         ⭐ NEW
```

**Update** [config/field-types.php](config/field-types.php) with all 18 field types:
- Add textarea, email, url, tel, password
- Add date, time, datetime, color
- Add checkbox, radio, select
- Add file, hidden
- Configure validators, metadata, HTML5 attributes

Each field type includes:
- SQL type mapping (VARCHAR, TEXT, INT, DATE, DATETIME, etc.)
- Validators (email, url, phone, date format, file type)
- HTML5 input attributes (pattern, maxlength, accept, multiple)
- UI metadata for frontend rendering

---

### 2. Create Centralized Base Schema

**Create** [config/base-schema.json](config/base-schema.json)

Standard fields for all entities:
```json
{
  "id": "Primary key (auto-increment)",
  "reference_id": "UUID for external references",
  "uid": "Unique identifier string",
  "created_at": "Creation timestamp",
  "updated_at": "Update timestamp",
  "created_by": "User ID who created",
  "updated_by": "User ID who updated",
  "is_active": "Soft delete flag",
  "is_deleted": "Hard delete flag"
}
```

**Update** [app/core/SchemaLoader.php](app/core/SchemaLoader.php):
- Load base-schema.json
- Merge base fields with entity-specific fields
- Override mechanism for entities that don't need certain base fields

**Update existing entity schemas**:
- [app/modules/articles/Article/schema.json](app/modules/articles/Article/schema.json)
- [app/modules/users/User/schema.json](app/modules/users/User/schema.json)
- [app/modules/categories/Category/schema.json](app/modules/categories/Category/schema.json)
- [app/modules/tags/Tag/schema.json](app/modules/tags/Tag/schema.json)

Remove duplicate base fields, keep only entity-specific fields.

---

### 3. Implement API Filtering & Pagination

**Update** [app/core/classes/BaseRepository.php](app/core/classes/BaseRepository.php):

Add query parameter parsing:
```php
public function applyFilters(array $params): self
{
    // Handle limit/offset
    if (isset($params['limit'])) {
        $this->limit((int)$params['limit'], (int)($params['offset'] ?? 0));
    }
    
    // Handle orderBy
    if (isset($params['orderBy'])) {
        $direction = strtoupper($params['order'] ?? 'ASC');
        $this->orderBy($params['orderBy'], $direction);
    }
    
    // Handle dynamic filters (name=takis, status=active)
    foreach ($params as $key => $value) {
        if (!in_array($key, ['limit', 'offset', 'orderBy', 'order'])) {
            $this->where($key, '=', $value);
        }
    }
    
    return $this;
}

public function getPaginated(array $params = []): array
{
    $this->applyFilters($params);
    
    $total = $this->count();
    $data = $this->get();
    
    return [
        'data' => $data,
        'pagination' => [
            'total' => $total,
            'limit' => $this->limit,
            'offset' => $this->offset,
            'count' => count($data)
        ]
    ];
}

public function count(): int
{
    // Count total records matching current where clauses
}
```

**Update** [app/core/classes/BaseController.php](app/core/classes/BaseController.php):

```php
public function readAll(array $filters = []): mixed
{
    // Parse query parameters from request
    $params = $_GET;
    
    // Get paginated results with filters
    $result = $this->repository->getPaginated($params);
    
    return $this->view->render($result);
}
```

---

### 4. Refactor Controllers to Use BaseController

**Current Problem:** All 7+ entity controllers have **duplicate CRUD code**:
- [app/modules/articles/Article/Controller.php](app/modules/articles/Article/Controller.php)
- [app/modules/users/User/Controller.php](app/modules/users/User/Controller.php)
- [app/modules/categories/Category/Controller.php](app/modules/categories/Category/Controller.php)
- [app/modules/tags/Tag/Controller.php](app/modules/tags/Tag/schema.json)

**Refactoring Strategy:**

1. **Enhance BaseController** with:
   - Input validation via Sanitizer
   - Error handling
   - Security checks (via ResponseFilter)
   - Logging

2. **Simplify entity controllers**:
   - Remove duplicate CRUD methods
   - Keep only entity-specific business logic
   - Override BaseController methods only when custom logic needed

**Example refactored Article Controller**:
```php
class Controller extends BaseController
{
    // Only keep custom methods, remove readAll(), read(), etc.
    // BaseController handles standard CRUD
    
    // Override only if custom logic needed:
    public function create(array $data): array
    {
        // Custom validation for articles
        if (empty($data['title'])) {
            return ['error' => 'Title required'];
        }
        
        // Add slug generation
        $data['slug'] = $this->generateSlug($data['title']);
        
        // Call parent
        return parent::create($data);
    }
}
```

**Update** [app/core/classes/BaseController.php](app/core/classes/BaseController.php):
- Add input sanitization in create/update methods
- Add ResponseFilter integration for sensitive data
- Add error handling with try/catch
- Add logging for CRUD operations

---

### 5. Add Comprehensive Testing

**Create test files** in [tests/unit/](tests/unit/) and [tests/integration/](tests/integration/):

#### Unit Tests (18 field type tests)

```
tests/unit/fields/
├── TextFieldTest.php
├── TextAreaTest.php
├── EmailFieldTest.php
├── UrlFieldTest.php
├── TelFieldTest.php
├── PasswordFieldTest.php
├── DateFieldTest.php
├── TimeFieldTest.php
├── DateTimeFieldTest.php
├── ColorFieldTest.php
├── CheckboxFieldTest.php
├── RadioFieldTest.php
├── SelectFieldTest.php
├── FileFieldTest.php
├── NumberBoxTest.php
├── PriceFieldTest.php
└── HiddenFieldTest.php
```

Each test covers:
- SQL type mapping
- Validation rules
- HTML rendering
- Value transformation

#### Integration Tests (API endpoints)

**Create** [tests/integration/ApiFilteringTest.php](tests/integration/ApiFilteringTest.php):
```php
- testPagination() - ?limit=10&offset=0
- testSorting() - ?orderBy=name&order=desc
- testFiltering() - ?name=takis&status=active
- testCombinedFilters() - ?limit=5&orderBy=created_at&order=desc&status=active
```

**Create** [tests/integration/FieldTypesTest.php](tests/integration/FieldTypesTest.php):
- Test creating/updating records with each field type
- Validate data persistence for each SQL type
- Test validation for email, url, date formats

**Expand** [tests/integration/ApiTest.php](tests/integration/ApiTest.php):
- Add tests for all entity endpoints (currently only articles/users tested)
- Test categories CRUD
- Test tags CRUD
- Test base fields (reference_id, uid)

**Create** [run-field-tests.php](run-field-tests.php):
- Test runner for all field type tests
- Summary report

---

### 6. Update Documentation

**Create** [FIELD-TYPES-GUIDE.md](FIELD-TYPES-GUIDE.md):
- Complete field type reference
- SQL type mappings
- Validation rules
- HTML examples
- Schema configuration examples

**Create** [API-FILTERING-GUIDE.md](API-FILTERING-GUIDE.md):
- Query parameter documentation
- Pagination examples
- Sorting examples
- Filtering examples with multiple parameters
- cURL examples for each pattern

**Update** [API-TESTING-GUIDE.md](API-TESTING-GUIDE.md):
- Add filtering/pagination test examples
- Add field type validation examples

---

## Verification

### Test Commands

```bash
# Test field types
php run-field-tests.php

# Test API filtering
php tests/integration/ApiFilteringTest.php

# Test all API endpoints
php run-api-tests.php

# Test security (existing)
php run-security-tests.php
```

### Expected Results

- **Field Types:** 18/18 unit tests passing
- **API Filtering:** All pagination/sorting/filtering tests passing
- **Endpoint Tests:** All CRUD operations for all entities passing
- **Security Tests:** 26/26 still passing (no regression)
- **Integration Tests:** 40+ tests total passing

### Manual Testing

```bash
# Test pagination
curl "http://localhost:8000/api/articles?limit=5&offset=0"

# Test sorting
curl "http://localhost:8000/api/articles?orderBy=created_at&order=desc"

# Test filtering
curl "http://localhost:8000/api/articles?status=published&author_id=1"

# Test combined
curl "http://localhost:8000/api/articles?limit=10&orderBy=title&order=asc&status=published"

# Test response format
# Should return:
{
  "data": [...],
  "pagination": {
    "total": 25,
    "limit": 10,
    "offset": 0,
    "count": 10
  }
}
```

---

## Decisions

### Query Parameter Format
**Chosen:** Simple format (`?limit=10&offset=0&orderBy=name&name=takis`)  
**Rationale:** Easier to implement, more intuitive for simple use cases  
**Alternative rejected:** Complex REST format with nested filters

### Base Schema Approach
**Chosen:** Centralized base-schema.json with entity inheritance  
**Rationale:** DRY principle, consistent fields across entities, easier maintenance  
**Alternative rejected:** Continue duplicating fields in each schema

### Controller Refactoring
**Chosen:** Refactor all controllers to use BaseController, remove duplication  
**Rationale:** Current duplication is severe (7+ controllers with identical methods), violates DRY  
**Benefits:** 
- ~70% reduction in controller code
- Consistent behavior across entities
- Easier to add features (just update BaseController)
- Better testability

### Field Types Implementation
**Chosen:** Create actual Field classes (not just config)  
**Rationale:** Allows validation logic, SQL mapping, HTML rendering in class methods  
**Structure:** BaseField abstract class → specific field types inherit

---

## Files Summary

### New Files (30+)
- 18 field type classes
- 1 base schema config
- 18 unit test files
- 3 integration test files
- 3 documentation files
- 1 test runner

### Modified Files (12+)
- BaseRepository (add filtering/pagination)
- BaseController (enhance with validation/security)
- 7+ entity controllers (remove duplication)
- SchemaLoader (add base schema merging)
- field-types.php (add 12+ new types)

### Test Coverage
- Unit tests: 18 (field types)
- Integration tests: 40+ (endpoints + filtering)
- Security tests: 26 (existing, no regression)
- **Total: 84+ tests**

---

## Implementation Order

1. **Field Types** (2-3 hours)
   - Create BaseField and 18 field type classes
   - Update field-types.php configuration
   - Unit tests for each field type

2. **Base Schema** (1 hour)
   - Create base-schema.json
   - Update SchemaLoader
   - Update entity schemas

3. **API Filtering** (2 hours)
   - Update BaseRepository with filtering/pagination
   - Update BaseController readAll method
   - Integration tests for filtering

4. **Controller Refactoring** (2-3 hours)
   - Enhance BaseController
   - Refactor 7+ entity controllers
   - Test all endpoints

5. **Comprehensive Testing** (2 hours)
   - Create test files
   - Run all tests
   - Fix any issues

6. **Documentation** (1 hour)
   - Create field types guide
   - Create filtering guide
   - Update testing guide

**Total Estimated Time:** 10-12 hours of focused development

---

## Risk Mitigation

### Backward Compatibility
- Keep existing API responses format
- Add pagination metadata without breaking clients
- Test with existing Postman collection

### Database Changes
- Base fields can be added gradually
- No destructive migrations needed
- reference_id, uid are optional initially

### Testing
- Run existing 17 API tests + 26 security tests after each step
- Ensure no regressions
- Add new tests incrementally

---

## Note on Git Commits

✅ **Confirmed:** No automatic push to GitHub  
User will review and push manually after each milestone.

Suggested commit strategy:
1. Commit: "feat: Add 18 field types with validation"
2. Commit: "feat: Add base schema with common fields"
3. Commit: "feat: Add API filtering and pagination"
4. Commit: "refactor: Simplify controllers using BaseController"
5. Commit: "test: Add comprehensive field and endpoint tests"

User reviews and pushes when satisfied with each feature set.

---

**Ready to proceed?** This plan implements all requested features with comprehensive testing and documentation.
