# teraCore Installation Guide

## Prerequisites

- PHP 8.4+
- MySQL 5.7+ or compatible
- PDO PHP Extension

## Setup Steps

### 1. Clone/Download Project
```bash
cd d:\MrSRK\testai
```

### 2. Create Configuration File
Copy the example environment configuration:
```bash
cp config/.env.example config/.env
```

Edit `config/.env` with your database credentials:
```
DB_HOST=localhost
DB_PORT=3306
DB_USER=root
DB_PASS=your_password
DB_NAME=teracore_db
```

### 3. Create Database
Create an empty MySQL database:
```bash
mysql -u root -p -e "CREATE DATABASE teracore_db CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"
```

### 4. Install Database Schema

Run the installer to create all tables from module schemas:

```bash
php install.php install
```

This will:
- Load all modules from `app/modules/`
- Read each entity's `schema.json`
- Create tables in your database
- Save module versions to `config/modules.json`

### 5. Check Installation Status

```bash
php install.php status
```

Expected output:
```
=== Database Installation Status ===

Module: users
  Installed Ver: 1.0.0
  Available Ver: 1.0.0
  Needs Update: NO
  Entities: User, Role

Module: articles
  Installed Ver: 1.0.0
  Available Ver: 1.0.0
  Needs Update: NO
  Entities: Article, Category, Tag
```

### 6. Start Development Server

```bash
php -S localhost:8000 -t public/
```

Open browser: http://localhost:8000

---

## Available Commands

### Install
Creates tables from current schemas:
```bash
php install.php install
```

### Migrate
Updates existing tables if schemas have changed (checks version):
```bash
php install.php migrate
```

### Install Fresh (Clean Reinstall)
Drops all tables and reinstalls from scratch:
```bash
php install.php install:fresh
```
⚠️ **Warning**: This will delete all data!

### Status
Shows installation status of all modules:
```bash
php install.php status
```

### Help
Shows all available commands:
```bash
php install.php help
```

---

## Database Structure

### Users Module
- **users** table
  - id (PK)
  - username (unique)
  - password (encrypted)
  - email (unique)
  - first_name
  - last_name
  - is_active
  - created_at
  - updated_at

- **roles** table
  - id (PK)
  - name (unique)
  - description
  - created_at

### Articles Module
- **articles** table
  - id (PK)
  - title
  - slug (unique)
  - content
  - summary
  - author_id (FK to users)
  - status (draft|published|archived)
  - published_at
  - created_at
  - updated_at

- **article_categories** table
  - id (PK)
  - name (unique)
  - description
  - created_at

- **article_tags** table
  - id (PK)
  - name (unique)
  - created_at

---

## Troubleshooting

### "Cannot connect to database"
- Check DB_HOST, DB_USER, DB_PASS in config/.env
- Verify MySQL is running
- Check database exists

### "Table already exists"
- Run migration if schema changed: `php install.php migrate`
- Or reinstall: `php install.php install:fresh`

### "tableName is required in schema"
- Check each entity's schema.json has "tableName" field
- See example: `app/modules/users/User/schema.json`

---

## API Usage

### Create User (POST)
```bash
curl -X POST http://localhost:8000/users/user \
  -H "Content-Type: application/json" \
  -d '{"username":"john","email":"john@example.com","password":"123456"}'
```

### Get User (GET)
```bash
curl http://localhost:8000/users/user/1
```

### Get All Users (GET)
```bash
curl http://localhost:8000/users/user
```

### Update User (PUT)
```bash
curl -X PUT http://localhost:8000/users/user/1 \
  -H "Content-Type: application/json" \
  -d '{"email":"newemail@example.com"}'
```

### Delete User (DELETE)
```bash
curl -X DELETE http://localhost:8000/users/user/1
```

---

## Module Management

### Adding New Module

1. Create folder: `app/modules/yourmodule/`
2. Create init.json:
```json
{
  "name": "yourmodule",
  "version": "1.0.0",
  "status": "active",
  "description": "Your module description",
  "author": "Your Name",
  "installDate": "2026-02-08"
}
```

3. Create entity folder: `app/modules/yourmodule/YourEntity/`
4. Create schema.json with field definitions
5. Create Model.php, Controller.php, View.php, Repository.php
6. Run install: `php install.php install`

---

## Next Steps

- Read [API Documentation](./API.md)
- Create tests in `tests/` folder
- Extend models and controllers as needed
- Deploy to production

