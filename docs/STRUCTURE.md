# teraCore Project Structure Guide

## Directory Overview

### Root Level Files
- **`install.php`** - Database installer CLI script
- **`run-tests.php`** - Unit test runner
- **`INSTALL.md`** - Installation guide
- **`API.md`** - API documentation
- **`STRUCTURE.md`** - This file
- **`README.md`** - Project overview
- **`.env`** - Environment configuration (create from .env.example)
- **`.gitignore`** - Git ignore patterns

---

## Directory Structure

```
project/
├── public/                      # Web root
│   └── index.php               # Application entry point
│
├── app/                         # Application code
│   ├── Autoloader.php          # PSR-4 autoloader
│   ├── core/                   # Framework core
│   │   ├── classes/            # Base classes
│   │   │   ├── BaseModel.php
│   │   │   ├── BaseController.php
│   │   │   ├── BaseRepository.php
│   │   │   └── BaseView.php
│   │   │
│   │   ├── handlers/           # Request/Response handlers
│   │   │   ├── URLHandler.php
│   │   │   ├── SessionHandler.php
│   │   │   └── CookieHandler.php
│   │   │
│   │   ├── libraries/          # Utility libraries
│   │   │   ├── Encrypt.php     # Encryption & hashing
│   │   │   ├── Parser.php      # JSON/XML/Form parsing
│   │   │   └── Sanitizer.php   # Input validation & sanitization
│   │   │
│   │   ├── Config.php          # Configuration loader (.env)
│   │   ├── Database.php        # PDO abstraction (Singleton)
│   │   ├── DatabaseInstaller.php # Auto-schema installation
│   │   ├── Factory.php         # Object factory (MVC)
│   │   ├── ModuleLoader.php    # Module auto-loader
│   │   ├── Logger.php          # Logging utility
│   │   ├── Request.php         # HTTP request handler
│   │   └── Response.php        # HTTP response handler
│   │
│   └── modules/                # User-defined modules
│       ├── users/              # Users module (entity group)
│       │   ├── init.json       # Module metadata
│       │   ├── User/           # User entity
│       │   │   ├── schema.json     # Data definition
│       │   │   ├── Model.php
│       │   │   ├── Controller.php
│       │   │   ├── View.php
│       │   │   └── Repository.php
│       │   └── Role/           # Role entity
│       │       ├── schema.json
│       │       ├── Model.php
│       │       ├── Controller.php
│       │       ├── View.php
│       │       └── Repository.php
│       │
│       └── articles/           # Articles module
│           ├── init.json
│           ├── Article/        # Article entity
│           ├── Category/       # Category entity
│           └── Tag/            # Tag entity
│
├── config/                     # Configuration files
│   ├── .env.example           # Example environment file
│   ├── .env                   # Your environment (created from example)
│   ├── modules.json.example   # Example module tracking
│   └── modules.json           # Generated during installation
│
├── tests/                      # Test suite
│   ├── bootstrap.php          # Test framework bootstrap
│   └── unit/                  # Unit tests
│       ├── EncryptTest.php
│       ├── SanitizerTest.php
│       └── ParserTest.php
│
└── storage/                    # Runtime files
    └── logs/                   # Application logs
        └── YYYY-MM-DD.log      # Daily log files
```

---

## Core Components

### 1. **Framework Core** (`app/core/`)

#### Config.php
- Loads environment variables from `.env`
- Singleton pattern
- Methods: `load()`, `get()`, `set()`, `has()`, `all()`

#### Database.php
- PDO abstraction layer
- Singleton pattern
- Methods: `execute()`, `fetch()`, `fetchAll()`, `beginTransaction()`, `commit()`, `rollback()`

#### DatabaseInstaller.php
- Auto-creates tables from module schemas
- Manages module versions
- Methods: `install()`, `migrate()`, `purge()`, `status()`

#### Logger.php
- File-based logging
- Methods: `info()`, `warning()`, `error()`, `debug()`

#### Factory.php
- Creates Model, Controller, View, Repository instances
- Handles dependency injection
- Methods: `createModel()`, `createController()`, `createView()`, `createRepository()`

#### ModuleLoader.php
- Auto-scans modules folder
- Loads schemas and metadata
- Methods: `load()`, `getModule()`, `getEntity()`

### 2. **Base Classes** (`app/core/classes/`)

#### BaseModel
- Represents database record
- Methods: `getAttribute()`, `setAttribute()`, `save()`, `delete()`

#### BaseController
- Handles HTTP requests
- Inherited CRUD methods: `create()`, `read()`, `readAll()`, `update()`, `delete()`

#### BaseRepository
- Data access object (DAO)
- Chainable query builder: `where()`, `orderBy()`, `limit()`, `get()`
- CRUD methods: `insert()`, `update()`, `delete()`, `findById()`, `findAll()`

#### BaseView
- Response formatting
- Methods: `render()` (supports JSON, XML, HTML)

### 3. **Handlers** (`app/core/handlers/`)

#### URLHandler
- URL building and parsing
- Methods: `build()`, `parse()`, `getRoute()`, `redirect()`

#### SessionHandler
- Session management with optional encryption
- Methods: `set()`, `get()`, `remove()`, `destroy()`, `regenerate()`

#### CookieHandler
- Cookie management
- Methods: `set()`, `get()`, `delete()`, `all()`

### 4. **Libraries** (`app/core/libraries/`)

#### Encrypt.php
- AES-256-CBC encryption/decryption
- BCrypt password hashing
- Token generation
- Methods: `encrypt()`, `decrypt()`, `hashPassword()`, `verifyPassword()`, `generateToken()`

#### Parser.php
- JSON, XML, and form data parsing
- Content-type detection
- Methods: `parseJson()`, `parseXml()`, `parseFormData()`, `parseByContentType()`

#### Sanitizer.php
- Input sanitization and validation
- Methods: `sanitizeString()`, `sanitizeEmail()`, `validateEmail()`, `validateUrl()`, etc.

### 5. **Request/Response** (`app/core/`)

#### Request.php
- HTTP request parsing
- Multi-format body parsing (JSON, XML, FORM)
- Methods: `get()`, `all()`, `method()`, `path()`, `uri()`

#### Response.php
- HTTP response building and sending
- Multi-format output (JSON, XML, HTML)
- Methods: `status()`, `header()`, `json()`, `xml()`, `html()`

---

## Module System

### Module Structure
```
modules/{module_name}/
├── init.json                  # Module metadata
├── {EntityName}/              # Each entity in the module
│   ├── schema.json           # Data definition
│   ├── Model.php             # Business logic
│   ├── Controller.php        # HTTP handling
│   ├── View.php              # Response formatting
│   └── Repository.php        # Data access
```

### Module Metadata (init.json)
```json
{
  "name": "module_name",
  "version": "1.0.0",
  "status": "active",
  "description": "Module description",
  "dependencies": ["other_module"],
  "entities": ["Entity1", "Entity2"]
}
```

### Entity Schema (schema.json)
```json
{
  "tableName": "table_name",
  "module": "module_name",
  "fields": {
    "id": {
      "type": "int",
      "primaryKey": true,
      "autoIncrement": true
    },
    "name": {
      "type": "varchar(100)",
      "nullable": false,
      "unique": true,
      "view": "text",
      "controller": "raw",
      "validation": "required|unique"
    }
  },
  "indexes": [
    { "name": "idx_name", "columns": ["name"], "unique": true }
  ]
}
```

---

## Data Flow

### Request Lifecycle

1. **HTTP Request** → `public/index.php`
2. **Autoloader** loads PSR-4 classes
3. **Config.load()** reads `.env`
4. **Request** parses input (JSON/XML/FORM)
5. **ModuleLoader** auto-scans modules
6. **Factory** creates appropriate objects
7. **Controller** handles business logic
8. **Repository** accesses database
9. **View** formats response
10. **Response** returns to client

### MVC Architecture

```
┌─────────────────────────────────────┐
│         HTTP Request                 │
└──────────────────┬──────────────────┘
                   │
                   ▼
         ┌─────────────────┐
         │   Controller    │
         │  (HTTP handling)│
         └────────┬────────┘
                  │
        ┌─────────▼──────────┐
        │  Model-Repository  │
        │ (Data access)      │
        └─────────┬──────────┘
                  │
        ┌─────────▼──────────┐
        │    Database        │
        │   (MySQL/InnoDB)   │
        └────────────────────┘

        Result flows back through:
        Repository ←→ Model ←→ Controller ←→ View ←→ Response
```

---

## Configuration

### .env Variables
```
APP_NAME=teraCore              # Application name
APP_ENV=development            # Environment
APP_DEBUG=true                 # Debug mode

DB_HOST=localhost              # Database host
DB_PORT=3306                   # Database port
DB_USER=root                   # Database user
DB_PASS=password               # Database password
DB_NAME=teracore_db            # Database name

SESSION_ENCRYPT=true           # Encrypt sessions
SESSION_TIMEOUT=3600           # Session timeout (seconds)

ENCRYPTION_KEY=your_key        # AES encryption key
```

---

## Adding New Modules

### Step 1: Create Module Structure
```bash
mkdir -p app/modules/mymodule/MyEntity
```

### Step 2: Create Module Metadata
Create `app/modules/mymodule/init.json`:
```json
{
  "name": "mymodule",
  "version": "1.0.0",
  "status": "active",
  "description": "My custom module",
  "entities": ["MyEntity"]
}
```

### Step 3: Create Entity Schema
Create `app/modules/mymodule/MyEntity/schema.json`:
```json
{
  "tableName": "my_entities",
  "module": "mymodule",
  "fields": {
    "id": { "type": "int", "primaryKey": true, "autoIncrement": true },
    "name": { "type": "varchar(100)", "nullable": false }
  }
}
```

### Step 4: Create Classes
- `Model.php` extends `BaseModel`
- `Controller.php` extends `BaseController`
- `View.php` extends `BaseView`
- `Repository.php` extends `BaseRepository`

### Step 5: Install
```bash
php install.php install
```

---

## Testing

### Run Tests
```bash
php run-tests.php
```

### Test Structure
- `tests/bootstrap.php` - Test framework
- `tests/unit/*.php` - Unit tests

### Test Base Class
```php
class MyTest extends TestCase {
    public function test_something() {
        assert_true($condition);
    }
}
```

---

## Deployment

### Production Checklist
- [ ] Set `APP_ENV=production`
- [ ] Set `APP_DEBUG=false`
- [ ] Change `ENCRYPTION_KEY` to strong value
- [ ] Use `.htaccess` or nginx rewrites to route to `public/index.php`
- [ ] Ensure `storage/logs/` is writable
- [ ] Backup database regularly
- [ ] Use HTTPS
- [ ] Implement authentication
- [ ] Add rate limiting

---

## Security Considerations

1. **Input Validation**: Use `Sanitizer` class
2. **Password Hashing**: Use `Encrypt::hashPassword()`
3. **Data Encryption**: Use `Encrypt::encrypt()`
4. **SQL Injection**: Always use prepared statements
5. **XSS Prevention**: Sanitize output in View
6. **CSRF**: Implement token validation
7. **Session Security**: Use `SessionHandler` with encryption
8. **Secret Management**: Never commit `.env` file

---

## Performance Tips

1. Use database indexes (defined in schema)
2. Implement caching for frequent queries
3. Use pagination for large datasets
4. Minimize logging in production
5. Use prepared statements (automatic in PDO)

---

## Common Usage Patterns

### Create Record
```php
$controller = Factory::createController('users', 'User');
$result = $controller->create(['username' => 'john', 'email' => 'john@example.com']);
```

### Query with Filters
```php
$repo = Factory::createRepository('articles', 'Article');
$articles = $repo->where('status', '=', 'published')
                  ->orderBy('created_at', 'DESC')
                  ->limit(10)
                  ->get();
```

### Encrypt Data
```php
$encrypted = Encrypt::encrypt($sensitiveData);
$decrypted = Encrypt::decrypt($encrypted);
```

### Validate Input
```php
if (!Sanitizer::validateEmail($email)) {
    return ['error' => 'Invalid email'];
}
```

---

## Future Enhancements

- [ ] User authentication & JWT tokens
- [ ] Permission/ACL system
- [ ] API rate limiting
- [ ] Database seeding
- [ ] Migration history
- [ ] Caching layer (Redis/Memcached)
- [ ] Feature flags
- [ ] GraphQL endpoint
- [ ] WebSocket support
- [ ] Queue/Job system

---

## Support & Contribution

For bugs, feature requests, or contributions, please refer to guidelines in contributing documentation.

