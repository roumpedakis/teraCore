# TeraCore Codebase Research Findings

**Date:** February 8, 2026
**Research Focus:** Field types system, HTML components, base structures, API filtering/pagination, duplicate functions, and testing patterns

---

## 1. Field Types System Analysis

### 1.1 Existing Configuration
**Location:** [config/field-types.php](config/field-types.php)

#### Current Field Types:
1. **text** - Basic text input
   - Class: `App\Core\Fields\TextField`
   - No validators by default
   - UI Type: `text`

2. **number** - Numeric input
   - Class: `App\Core\Fields\NumberBox`
   - Validators: `['numeric']`
   - Decimals: 0
   - UI Type: `number`

3. **price** - Currency field
   - Class: `App\Core\Fields\Price`
   - Extends: `number`
   - Validators: `['numeric', 'positive']`
   - Decimals: 2
   - Currency: EUR
   - UI Type: `price`

4. **decimal** - Decimal number
   - Class: `App\Core\Fields\NumberBox`
   - Validators: `['numeric']`
   - Decimals: 2
   - UI Type: `decimal`

5. **title** - Translatable title
   - Class: `App\Core\Fields\TextField`
   - Extends: `text`
   - Validators: `['required']`
   - Max Length: 255
   - Translatable: true
   - UI Type: `text`

6. **description** - Translatable description
   - Class: `App\Core\Fields\TextField`
   - Extends: `text`
   - Translatable: true
   - UI Type: `textarea`

### 1.2 Field Type Infrastructure
**Files:**
- [app/core/fields/BaseElement.php](app/core/fields/BaseElement.php) - Base field type class (152 lines)
- [app/core/fields/TextField.php](app/core/fields/TextField.php) - Text field implementation
- [app/core/fields/NumberBox.php](app/core/fields/NumberBox.php) - Number field implementation
- [app/core/fields/Price.php](app/core/fields/Price.php) - Price field implementation
- [app/core/FieldTypeLoader.php](app/core/FieldTypeLoader.php) - Field type loader with inheritance
- [app/core/Factory.php](app/core/Factory.php) - Factory for creating field instances

### 1.3 Field Type Features
**BaseElement capabilities:**
- Name, type, value management
- Validators array
- Required field support
- Default values
- Translatable fields with translation table linkage
- Metadata storage
- Validation logic
- toArray() conversion

**Inheritance System:**
- Supports field type inheritance (e.g., `price` extends `number`)
- Configuration merging from parent types
- Entity-specific overrides via JSON files in `config/{entity}/field-types.json`

---

## 2. Missing HTML Components

### 2.1 Components Referenced in Schemas But Not Implemented:

From [app/modules/articles/Article/schema.json](app/modules/articles/Article/schema.json):
- **textarea** - Used for `content`, `summary` fields
- **select** - Used for `status` field with options
- **datetime** - Used for `published_at` field
- **hidden** - Used for `author_id`, `created_at`, `updated_at`

From [app/modules/users/User/schema.json](app/modules/users/User/schema.json):
- **email** - Used for `email` field
- **checkbox** - Used for `is_active` field
- **hidden** - Multiple fields (password, refresh_token, oauth fields)

### 2.2 Standard HTML Components Missing:

#### High Priority (Used in Existing Schemas):
1. **TextArea** - Multi-line text input
2. **Select** - Dropdown selection with options
3. **DateTime** - Date and time picker
4. **Date** - Date picker only
5. **Time** - Time picker only
6. **Email** - Email input with validation
7. **Checkbox** - Boolean toggle
8. **Hidden** - Hidden fields

#### Medium Priority (Common in Forms):
9. **Radio** - Radio button groups
10. **File** - File upload
11. **Image** - Image upload with preview
12. **Color** - Color picker
13. **Range** - Slider input
14. **URL** - URL input with validation
15. **Tel** - Telephone number input

#### Lower Priority (Advanced):
16. **Password** - Password input with strength indicator
17. **Editor** - Rich text editor (WYSIWYG)
18. **Tags** - Tag input with autocomplete
19. **MultiSelect** - Multiple selection dropdown
20. **Autocomplete** - Text input with suggestions

---

## 3. Base Entity/Model Structure

### 3.1 "Super Fields" (Standard Across All Entities)

**Location:** Defined in schema.json files, not in a central base class

#### Common Pattern Found in All Schemas:

**Primary Key:**
- `id` - int, primary key, auto increment

**Timestamps (Most Entities):**
- `created_at` - timestamp, default CURRENT_TIMESTAMP, controller="auto", view="hidden"
- `updated_at` - timestamp, onUpdateCurrentTimestamp=true, controller="auto", view="hidden"

**User-Specific Fields:**
- `password` - varchar(150), controller="bcrypt", view="hidden"
- `refresh_token` - varchar(500), view="hidden"
- `token_expires_at` - datetime, view="hidden"

#### Notable Absence:
- **No `reference_id` field** found in any schema
- **No `uid` field** found in any schema

### 3.2 BaseModel Structure
**Location:** [app/core/classes/BaseModel.php](app/core/classes/BaseModel.php) (259 lines)

**Features:**
- Abstract class for all models
- Attribute management (`$attributes`, `$original`)
- Table name resolution
- Exists flag for insert/update logic
- Translation support (Translator integration)
- Translatable fields management
- Magic methods (`__get`, `__set`, `__isset`)
- getDirty() for change tracking

**Methods:**
- `getTable()` - Auto-resolve table name from class
- `setAttribute()`, `getAttribute()`, `getAttributes()`
- `save()`, `insert()`, `update()`, `delete()`
- `markAsExisting()`, `getDirty()`
- Translation methods: `saveTranslation()`, `getTranslation()`, `getAllTranslations()`

---

## 4. API Filtering and Pagination

### 4.1 Current State: **PARTIALLY IMPLEMENTED**

#### Filtering:
**Location:** [app/modules/articles/Article/Controller.php](app/modules/articles/Article/Controller.php#L58-L72)

```php
public function readAll(array $filters = []): array
{
    $query = $this->repository;

    if (!empty($filters['status'])) {
        $query = $query->where('status', '=', $filters['status']);
    }

    if (!empty($filters['author_id'])) {
        $query = $query->where('author_id', '=', $filters['author_id']);
    }

    $articles = $query->orderBy('created_at', 'DESC')->get();

    return [
        'count' => count($articles),
        'data' => $articles
    ];
}
```

**Filtering Capabilities:**
- ✅ Basic WHERE clauses via `BaseRepository->where()`
- ✅ AND conditions (multiple where() calls)
- ✅ ORDER BY via `BaseRepository->orderBy()`
- ❌ OR conditions - NOT IMPLEMENTED
- ❌ LIKE/search - NOT IMPLEMENTED
- ❌ IN clauses - NOT IMPLEMENTED
- ❌ Date range filters - NOT IMPLEMENTED

#### Pagination:
**Location:** [app/core/classes/BaseRepository.php](app/core/classes/BaseRepository.php#L51-L57)

```php
public function limit(int $limit, int $offset = 0): self
{
    $this->limit = $limit;
    $this->offset = $offset;
    return $this;
}
```

**Pagination Capabilities:**
- ✅ `limit()` method exists in BaseRepository
- ✅ LIMIT and OFFSET added to SQL queries
- ❌ **NOT USED in any controller** - All controllers use `findAll()` without pagination
- ❌ No pagination metadata (total pages, current page, etc.)
- ❌ No Request parameter parsing for page/limit/offset
- ❌ No standard pagination response format

### 4.2 Request Parameter Handling
**Location:** [app/core/Request.php](app/core/Request.php)

**Current Capabilities:**
- ✅ `get()`, `all()`, `has()` methods
- ✅ Parses JSON, form data, query strings
- ❌ No built-in pagination parameter extraction
- ❌ No filter parameter parsing utilities

### 4.3 API Documentation References
**Location:** [app/core/ApiDocumentation.php](app/core/ApiDocumentation.php#L90-L97)

Shows filtering examples but **not actually implemented consistently**:
```php
'params' => [
    'status' => 'string (optional) - published|draft|archived',
    'author_id' => 'integer (optional) - filter by author'
]
```

---

## 5. Duplicate and Unused Functions

### 5.1 **SEVERE DUPLICATION** in Controllers

**Pattern:** Every entity controller implements the same CRUD methods with minimal differences

#### Example Duplication Count:
- `create()` method: **7+ implementations** (User, Role, Admin, Article, Category, Tag, etc.)
- `read()` method: **7+ implementations**
- `readAll()` method: **7+ implementations** 
- `update()` method: **7+ implementations**
- `delete()` method: **7+ implementations**

#### Files with Duplicate CRUD Logic:
1. [app/modules/users/User/Controller.php](app/modules/users/User/Controller.php)
2. [app/modules/users/Role/Controller.php](app/modules/users/Role/Controller.php)
3. [app/modules/core/Admin/Controller.php](app/modules/core/Admin/Controller.php)
4. [app/modules/articles/Article/Controller.php](app/modules/articles/Article/Controller.php)
5. [app/modules/articles/Category/Controller.php](app/modules/articles/Category/Controller.php)
6. [app/modules/articles/Tag/Controller.php](app/modules/articles/Tag/Controller.php)

### 5.2 Comparison with BaseController

**BaseController** ([app/core/classes/BaseController.php](app/core/classes/BaseController.php)):
- ✅ Has basic CRUD methods defined
- ❌ **Child controllers override everything** instead of using parent implementation
- ❌ Base methods are too generic and unused

```php
// BaseController has these, but NO ONE USES THEM:
public function create(array $data): mixed
public function read(string $id): mixed
public function readAll(array $filters = []): mixed
public function update(string $id, array $data): mixed
public function delete(string $id): mixed
```

### 5.3 Specific Duplication Issues

#### Issue 1: Sanitization Logic
Every controller manually sanitizes inputs:
```php
$title = Sanitizer::sanitizeString($data['title'] ?? '');
$id = Sanitizer::sanitizeInt($id);
```

**Recommendation:** Move to middleware or base controller

#### Issue 2: Error Response Format
Every controller returns same error format:
```php
return ['error' => 'Not found'];
return ['success' => true, 'message' => 'Created successfully'];
```

**Recommendation:** Standardize in Response class

#### Issue 3: Pagination NOT Applied
Every `readAll()` uses `findAll()` instead of paginated queries:
```php
$users = $this->repository->findAll(); // No pagination!
```

**Recommendation:** Implement pagination helper in BaseController

### 5.4 Unused Functions

#### BaseRepository Methods (Partially Unused):
- `limit()`, `offset` - Defined but **never called** in practice
- Query builder pattern (`where()`, `orderBy()`) - Used in Article controller only

#### BaseController Methods (Completely Unused):
- All base CRUD methods are overridden and never called

---

## 6. Testing Structure and Patterns

### 6.1 Test File Organization

**Root:** [tests/](tests/)

#### Directory Structure:
```
tests/
├── bootstrap.php          - Test setup and helpers
├── unit/                  - Unit tests
│   ├── EncryptTest.php
│   ├── FieldTypeTest.php
│   ├── ParserTest.php
│   ├── SanitizerTest.php
│   └── TranslatorTest.php
├── integration/           - Integration tests
│   └── ApiTest.php
└── feature/               - Feature tests (empty)
```

### 6.2 Test Bootstrap ([tests/bootstrap.php](tests/bootstrap.php))

**Features:**
- Custom assertion helpers (no PHPUnit dependency)
- Simple TestCase base class
- Assertion functions:
  - `assert_true()`, `assert_false()`
  - `assert_equal()`, `assert_not_equal()`
  - `assert_contains()`, `assert_array_key_exists()`

**Pattern:**
```php
class MyTest extends TestCase {
    public function test_something() {
        assert_true($condition);
        assert_equal($expected, $actual);
    }
}
```

### 6.3 Test Execution Pattern

**Unit Tests:** Run individually
```bash
php tests/unit/FieldTypeTest.php
```

**No Test Runner:** Each test file is standalone

### 6.4 Test Coverage Analysis

#### Well-Covered Areas:
- ✅ Field types (TextField, NumberBox, Price)
- ✅ Field type inheritance and configuration
- ✅ Validation logic
- ✅ Encryption/hashing (Encrypt library)
- ✅ Data sanitization (Sanitizer library)
- ✅ Parsing (Parser library)
- ✅ Translation system (Translator)

#### Unit Test Example ([tests/unit/FieldTypeTest.php](tests/unit/FieldTypeTest.php)):
```php
public function test_create_text_field() {
    $field = Factory::createFieldType('text');
    assert_true($field instanceof TextField);
    assert_equal('TextField', class_basename($field));
}

public function test_field_validation_max_length() {
    $field = Factory::createFieldType('title');
    $field->setValue(str_repeat('x', 300));
    assert_false($field->validate());
}
```

#### Integration Test Pattern ([tests/integration/ApiTest.php](tests/integration/ApiTest.php)):
- Uses cURL to test actual HTTP endpoints
- Tests auth flow (register, login, verify, refresh)
- Tests CRUD operations on articles
- Tests public vs authenticated endpoints
- **Current Status:** Some tests skipped due to schema issues (slug column missing)

### 6.5 Missing Test Coverage

#### Not Tested:
- ❌ BaseController CRUD operations
- ❌ BaseRepository query builder methods
- ❌ Response filtering (ResponseFilter)
- ❌ Security middleware (rate limiting, XSS protection)
- ❌ Request validation
- ❌ Factory module/controller creation
- ❌ Module loader
- ❌ Database installer
- ❌ Most new HTML components (when implemented)

---

## 7. Schema Analysis

### 7.1 Schema Definition Pattern

**Format:** JSON files in `app/modules/{module}/{Entity}/schema.json`

**Example Files:**
- [app/modules/articles/Article/schema.json](app/modules/articles/Article/schema.json)
- [app/modules/articles/Category/schema.json](app/modules/articles/Category/schema.json)
- [app/modules/users/User/schema.json](app/modules/users/User/schema.json)

### 7.2 Schema Structure

```json
{
  "tableName": "articles",
  "module": "articles",
  "description": "Article management",
  "fields": {
    "field_name": {
      "type": "varchar(200)",           // SQL type
      "nullable": false,
      "unique": true,
      "primaryKey": true,
      "autoIncrement": true,
      "default": "value",
      "view": "text",                   // HTML component type
      "controller": "raw",              // Controller processing
      "validation": "required|minLength:5",
      "options": ["option1", "option2"], // For select/radio
      "comment": "Description"
    }
  },
  "indexes": [
    { "name": "idx_name", "columns": ["col"], "unique": false }
  ]
}
```

### 7.3 Field Attributes in Schemas

#### Common Attributes:
- `type` - SQL data type
- `nullable` - Allow NULL values
- `unique` - Unique constraint
- `primaryKey` - Primary key flag
- `autoIncrement` - Auto-increment flag
- `default` - Default value
- `view` - HTML component type (text, textarea, select, hidden, etc.)
- `controller` - Processing type (raw, bcrypt, slug, auto)
- `validation` - Validation rules (pipe-separated)
- `options` - Array of options (for select/radio)
- `comment` - Field description

#### View Types Found:
- `text` - Text input
- `textarea` - Multi-line text
- `select` - Dropdown with options
- `hidden` - Hidden field
- `datetime` - Date/time picker
- `email` - Email input
- `checkbox` - Boolean checkbox

#### Controller Types Found:
- `raw` - No processing
- `bcrypt` - Hash with bcrypt
- `slug` - Generate URL slug
- `auto` - Auto-generated (timestamps)

---

## 8. Key Findings Summary

### 8.1 Strengths
✅ Well-structured field type system with inheritance
✅ Translation support built into core
✅ Good separation of concerns (Model, View, Controller, Repository)
✅ Security-conscious (ResponseFilter, SecurityMiddleware)
✅ Schema-driven database structure
✅ Factory pattern for component creation

### 8.2 Critical Gaps

#### Field Types:
- ❌ Only 6 field types defined (text, number, price, decimal, title, description)
- ❌ Missing 14+ HTML component implementations
- ❌ No file upload support
- ❌ No date/time field types
- ❌ No select/radio/checkbox field types

#### API Features:
- ❌ Pagination defined but NOT USED anywhere
- ❌ Filtering is ad-hoc per controller, not standardized
- ❌ No search capabilities
- ❌ No sorting by query parameter
- ❌ No standard API response format for paginated data

#### Code Quality:
- ❌ **MASSIVE duplication** in all controller CRUD methods
- ❌ BaseController methods unused
- ❌ No middleware for input sanitization
- ❌ No validation framework (string-based rules in schemas not enforced)

#### Testing:
- ❌ Limited integration test coverage
- ❌ No tests for new features when added
- ❌ No automated test runner
- ❌ Feature test directory empty

### 8.3 Super Fields Status

**Requested Super Fields:**
- `id` ✅ - Present in all schemas
- `created_at` ✅ - Present in most schemas
- `updated_at` ✅ - Present in most schemas
- `password` ⚠️ - Only in User schema (entity-specific)
- `reference_id` ❌ - NOT found anywhere
- `uid` ❌ - NOT found anywhere

**Recommendation:** Super fields should be defined in a base schema or BaseModel constant, not duplicated across schemas.

---

## 9. Implementation Priorities

### 9.1 High Priority (Immediate Need)

1. **Implement Missing HTML Components** (14 components)
   - TextArea, Select, DateTime, Date, Time
   - Email, Checkbox, Radio, Hidden
   - File, Image, Password, URL, Tel
   - Editor (rich text)

2. **Standardize API Pagination**
   - Add pagination helper to BaseController
   - Parse page/limit parameters from Request
   - Return standardized pagination metadata
   - Update all `readAll()` methods to use pagination

3. **Refactor Controller Duplication**
   - Make BaseController methods actually useful
   - Apply DRY principle to CRUD operations
   - Use traits for common patterns (sanitization, validation)
   - Consider generic CRUD controller with configuration

4. **Implement Standard Filtering**
   - Query parameter parser for filters
   - Support for operators (eq, ne, gt, lt, like, in)
   - Support for multi-field sorting
   - Support for search across fields

### 9.2 Medium Priority

5. **Centralize Super Fields**
   - Define standard fields in base schema
   - Add reference_id, uid if needed
   - Auto-include timestamps in all tables

6. **Validation Framework**
   - Parse validation rules from schemas
   - Apply validation in BaseController
   - Return standardized validation errors

7. **Expand Test Coverage**
   - Add tests for all new field types
   - Test pagination logic
   - Test filter combinations
   - Test all CRUD operations

### 9.3 Lower Priority

8. **Advanced Features**
   - Field dependencies (conditional rendering)
   - Custom field type plugins
   - Bulk operations API
   - Caching layer

---

## 10. Recommended Next Steps

1. ✅ **Research complete** - This document
2. 🔄 **Create HTML component implementations** (14 new field classes)
3. 🔄 **Update field-types.php** with new component definitions
4. 🔄 **Refactor BaseController** to reduce duplication
5. 🔄 **Implement pagination helper** with query parameter parsing
6. 🔄 **Add standardized filtering** to BaseRepository
7. 🔄 **Write tests** for all new components and features
8. 🔄 **Update documentation** with new API capabilities

---

## File Reference Index

### Core Classes:
- [app/core/classes/BaseController.php](app/core/classes/BaseController.php) - Base controller (74 lines)
- [app/core/classes/BaseModel.php](app/core/classes/BaseModel.php) - Base model (259 lines)
- [app/core/classes/BaseRepository.php](app/core/classes/BaseRepository.php) - Base repository (176 lines)
- [app/core/classes/BaseView.php](app/core/classes/BaseView.php) - Base view

### Field System:
- [config/field-types.php](config/field-types.php) - Field type definitions
- [app/core/fields/BaseElement.php](app/core/fields/BaseElement.php) - Base field (152 lines)
- [app/core/fields/TextField.php](app/core/fields/TextField.php) - Text field
- [app/core/fields/NumberBox.php](app/core/fields/NumberBox.php) - Number field
- [app/core/fields/Price.php](app/core/fields/Price.php) - Price field
- [app/core/FieldTypeLoader.php](app/core/FieldTypeLoader.php) - Field loader
- [app/core/Factory.php](app/core/Factory.php) - Factory pattern

### Core Infrastructure:
- [app/core/Request.php](app/core/Request.php) - HTTP request handler
- [app/core/Response.php](app/core/Response.php) - HTTP response handler
- [app/core/Database.php](app/core/Database.php) - Database singleton
- [app/core/ResponseFilter.php](app/core/ResponseFilter.php) - Security filter

### Testing:
- [tests/bootstrap.php](tests/bootstrap.php) - Test framework
- [tests/unit/FieldTypeTest.php](tests/unit/FieldTypeTest.php) - Field type tests
- [tests/integration/ApiTest.php](tests/integration/ApiTest.php) - API tests

### Module Examples:
- [app/modules/articles/Article/schema.json](app/modules/articles/Article/schema.json)
- [app/modules/articles/Article/Controller.php](app/modules/articles/Article/Controller.php)
- [app/modules/users/User/schema.json](app/modules/users/User/schema.json)
- [app/modules/users/User/Controller.php](app/modules/users/User/Controller.php)

---

**End of Research Report**
