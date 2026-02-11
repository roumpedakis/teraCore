# 📦 Πώς να Προσθέσω Module σε User

## 🎯 Overview

Υπάρχουν **3 τρόποι** να δώσεις πρόσβαση σε module σε έναν user:

1. **Μέσω UI** (Permissions Modal)
2. **Μέσω API** (POST request)
3. **Μέσω Database** (Direct SQL)
4. **Προγραμματικά** (UserModuleRepository)

---

## 1️⃣ Μέσω Admin UI (Recommended)

### Βήματα:
1. Πήγαινε στο [http://localhost:8000/admin/users](http://localhost:8000/admin/users)
2. Βρες τον user που θέλεις
3. Κάνε κλικ στο **🛡️ shield icon** (Manage Permissions)
4. Θα ανοίξει modal με τα διαθέσιμα modules
5. Επίλεξε το **permission level** για κάθε module:
   - **0: No Access** ❌ (Κανένα δικαίωμα)
   - **1: Read Only** 👁️ (Μόνο ανάγνωση)
   - **2: Read/Write** ✏️ (Ανάγνωση + Εγγραφή)
   - **3: Full Access** 👑 (Πλήρη δικαιώματα)
6. Πάτα **Save Permissions**

### Παράδειγμα:
```
User: john_doe
Module: articles → Level 2 (Read/Write)
Module: comments → Level 1 (Read Only)
```

---

## 2️⃣ Μέσω API

### Endpoint:
```http
POST /api/users/{user_id}/permissions
Content-Type: application/json
Authorization: Bearer {token}
```

### Request Body:
```json
{
  "permissions": {
    "articles": 2,
    "comments": 1
  }
}
```

### Example με curl:
```bash
curl -X POST http://localhost:8000/api/users/1/permissions \
  -H "Content-Type: application/json" \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -d '{
    "permissions": {
      "articles": 2,
      "comments": 1
    }
  }'
```

### Response:
```json
{
  "success": true,
  "message": "Permissions updated successfully"
}
```

---

## 3️⃣ Μέσω Database (SQL)

### Direct INSERT:
```sql
INSERT INTO user_modules (user_id, module_name, permission_level, enabled)
VALUES (1, 'articles', 2, 1);
```

### UPDATE existing:
```sql
UPDATE user_modules 
SET permission_level = 2, enabled = 1 
WHERE user_id = 1 AND module_name = 'articles';
```

### INSERT or UPDATE (UPSERT):
```sql
INSERT INTO user_modules (user_id, module_name, permission_level, enabled)
VALUES (1, 'articles', 2, 1)
ON DUPLICATE KEY UPDATE 
  permission_level = VALUES(permission_level),
  enabled = 1,
  updated_at = CURRENT_TIMESTAMP;
```

### Remove Access:
```sql
DELETE FROM user_modules 
WHERE user_id = 1 AND module_name = 'articles';
```

---

## 4️⃣ Προγραμματικά (PHP)

### Χρήση UserModuleRepository:

```php
use App\Core\UserModuleRepository;

$userModuleRepo = new UserModuleRepository();

// Set permission
$userModuleRepo->setModulePermission(
    userId: 1,
    moduleName: 'articles',
    permissionLevel: 2  // Read/Write
);

// Check if has access
$hasAccess = $userModuleRepo->hasModuleAccess(1, 'articles');

// Get permission level
$level = $userModuleRepo->getModulePermission(1, 'articles');

// Get all user modules
$modules = $userModuleRepo->getUserModules(1);
// Returns: ['articles' => 2, 'comments' => 1]

// Remove access
$userModuleRepo->removeModuleAccess(1, 'articles');
```

---

## 📋 Database Schema

### Table: `user_modules`
```sql
CREATE TABLE user_modules (
  id INT AUTO_INCREMENT PRIMARY KEY,
  user_id INT NOT NULL,
  module_name VARCHAR(50) NOT NULL,
  permission_level INT NOT NULL DEFAULT 0,
  enabled TINYINT(1) NOT NULL DEFAULT 1,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  UNIQUE KEY unique_user_module (user_id, module_name),
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);
```

---

## 🔒 Permission Levels Explained

| Level | Name | Icon | Description |
|-------|------|------|-------------|
| **0** | No Access | ❌ | Δεν έχει καμία πρόσβαση στο module |
| **1** | Read Only | 👁️ | Μπορεί να δει μόνο (GET requests) |
| **2** | Read/Write | ✏️ | Μπορεί να δει και να τροποποιήσει (GET/POST/PUT) |
| **3** | Full Access | 👑 | Πλήρη δικαιώματα (GET/POST/PUT/DELETE + Admin) |

---

## 🎨 Διαθέσιμα Modules

### Τρέχοντα Modules:
```json
{
  "articles": {
    "version": "1.0.0",
    "isCore": false,
    "description": "Article management system"
  },
  "comments": {
    "version": "1.0.0",
    "isCore": false,
    "description": "Universal commenting system"
  },
  "core": {
    "version": "1.0.0",
    "isCore": true,
    "description": "Core system (User, Role, Admin)"
  }
}
```

### ⚠️ Σημαντικό:
- Τα **core modules** (`isCore: true`) **ΔΕΝ** εμφανίζονται στο permissions UI
- Όλοι οι users έχουν **αυτόματη πρόσβαση** στα core modules
- Μόνο τα **non-core modules** χρειάζονται explicit permissions

---

## 🚀 Πώς να Προσθέσω Νέο Module

### 1. Δημιούργησε το Module:
```bash
mkdir -p app/modules/mymodule/MyEntity
```

### 2. Δημιούργησε init.json:
```json
{
  "name": "mymodule",
  "version": "1.0.0",
  "status": "active",
  "description": "My custom module",
  "entities": ["MyEntity"],
  "isCore": false,
  "price": 0,
  "priceCurrency": "EUR"
}
```

### 3. Πρόσθεσέ το στο config/modules.json:
```json
{
  "articles": { ... },
  "comments": { ... },
  "mymodule": {
    "version": "1.0.0",
    "installed_at": "2026-02-12 10:00:00"
  }
}
```

### 4. Δημιούργησε τα Entity files:
```bash
app/modules/mymodule/MyEntity/
  ├── Controller.php
  ├── Model.php
  ├── Repository.php
  ├── View.php
  └── schema.json
```

### 5. Τώρα το module θα εμφανίζεται στο Permissions UI! ✅

---

## 🧹 Cleanup Orphaned Permissions

Αν έμειναν **παλιά permissions** για modules που δεν υπάρχουν πια:

```bash
php scripts/cleanup_orphaned_permissions.php
```

Αυτό το script:
- ✅ Βρίσκει permissions για modules που δεν είναι πια installed
- ✅ Βρίσκει permissions για modules που δεν υπάρχουν στο filesystem
- ✅ Αφαιρεί core module permissions (δεν χρειάζονται)
- ✅ Σου ζητάει confirmation πριν διαγράψει

---

## 📊 Παραδείγματα

### Scenario 1: Νέος User χωρίς Permissions
```php
// User: new_user (id: 10)
// Modules: articles, comments
// Goal: Δώσε read-only σε όλα

$userModuleRepo = new UserModuleRepository();
$userModuleRepo->setModulePermission(10, 'articles', 1);
$userModuleRepo->setModulePermission(10, 'comments', 1);
```

### Scenario 2: Upgrade User Permissions
```php
// User: john_doe (id: 1)
// Current: articles (Read Only)
// Goal: Upgrade to Read/Write

$userModuleRepo = new UserModuleRepository();
$userModuleRepo->setModulePermission(1, 'articles', 2);
```

### Scenario 3: Bulk Update via API
```bash
# Give admin full access to all modules
curl -X POST http://localhost:8000/api/users/29/permissions \
  -H "Content-Type: application/json" \
  -H "Authorization: Bearer $TOKEN" \
  -d '{
    "permissions": {
      "articles": 3,
      "comments": 3
    }
  }'
```

### Scenario 4: Revoke Access
```php
// Remove articles access from user
$userModuleRepo = new UserModuleRepository();
$userModuleRepo->removeModuleAccess(1, 'articles');
```

---

## 🔍 Debugging

### Check User's Permissions:
```bash
curl http://localhost:8000/api/users/1/permissions
```

### Check Database:
```sql
SELECT 
  u.username,
  um.module_name,
  um.permission_level,
  um.enabled
FROM users u
LEFT JOIN user_modules um ON u.id = um.user_id
WHERE u.id = 1;
```

### Check Available Modules:
```bash
cat config/modules.json
```

### Check Module Init:
```bash
cat app/modules/articles/init.json
```

---

## ✅ Best Practices

1. **Πάντα χρησιμοποίησε το UI** για manual changes (πιο safe)
2. **Χρησιμοποίησε API** για bulk operations
3. **Μην αγγίζεις το database** απευθείας (unless debugging)
4. **Τρέχε cleanup script** μετά από module uninstall
5. **Μην δίνεις Full Access** χωρίς λόγο
6. **Core modules** δεν χρειάζονται permissions

---

## 🆘 Troubleshooting

### Module δεν εμφανίζεται στο UI:
- ✅ Είναι στο `config/modules.json`?
- ✅ Το `isCore` είναι `false` ή `undefined`?
- ✅ Υπάρχει το `app/modules/{name}/init.json`?

### Permissions δεν αποθηκεύονται:
- ✅ Έχει ο user valid JWT token?
- ✅ Τσέκαρε το `storage/logs/*.log` για errors
- ✅ Υπάρχει η εγγραφή στο `user_modules` table?

### Παλιά permissions εμφανίζονται:
- ✅ Τρέξε `php scripts/cleanup_orphaned_permissions.php`
- ✅ Restart τον PHP server

---

## 📚 Related Files

- [UserModuleRepository.php](d:/MrSRK/testai/app/core/UserModuleRepository.php) - Module permission logic
- [UserController.php](d:/MrSRK/testai/app/core/UserController.php) - API endpoints
- [users.js](d:/MrSRK/testai/public/admin/users.js) - Frontend JavaScript
- [users.html](d:/MrSRK/testai/public/admin/users.html) - Permissions UI
- [modules.json](d:/MrSRK/testai/config/modules.json) - Installed modules
- [cleanup_orphaned_permissions.php](d:/MrSRK/testai/scripts/cleanup_orphaned_permissions.php) - Cleanup script
