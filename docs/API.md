# teraCore API Documentation

## Overview

teraCore is a RESTful API framework with modular architecture. All endpoints follow REST conventions.

## Base URL
```
http://localhost:8000
```

## Response Format

### Success Response (JSON)
```json
{
  "success": true,
  "message": "Operation completed",
  "data": { ... }
}
```

### Error Response
```json
{
  "success": false,
  "error": "Error description",
  "error_code": "E1001"
}
```

### Error Codes
- `E1001` AUTH_REQUIRED - Missing or invalid auth header
- `E1002` AUTH_INVALID - Token verification failed
- `E2001` MODULE_NOT_FOUND - Module does not exist
- `E2002` ENTITY_NOT_FOUND - Entity does not exist in module
- `E2003` METHOD_NOT_ALLOWED - HTTP method not allowed
- `E2004` ADMIN_API_BLOCKED - Admin entity is not exposed via API
- `E2005` ENDPOINT_NOT_FOUND - Route does not exist
- `E3001` MODULE_NO_ACCESS - No access to requested module
- `E3002` MODULE_INSUFFICIENT - Permission level is insufficient
- `E3003` MODULE_PERMISSIONS_MISSING - Module permissions missing from token
- `E9000` GENERIC_ERROR - Unhandled server error

## Content Type
- All requests with body require: `Content-Type: application/json`
- All responses are: `application/json`
- XML output also supported: `Accept: application/xml`

---

## Core Users

### Endpoints

#### Create User
**POST** `/core/user`

Request body:
```json
{
  "username": "john_doe",
  "email": "john@example.com",
  "password": "securepassword123",
  "first_name": "John",
  "last_name": "Doe",
  "is_active": 1
}
```

Response:
```json
{
  "success": true,
  "message": "User created successfully"
}
```

#### Get User by ID
**GET** `/core/user/{id}`

Response:
```json
{
  "id": 1,
  "username": "john_doe",
  "email": "john@example.com",
  "first_name": "John",
  "last_name": "Doe",
  "is_active": 1,
  "created_at": "2026-02-08 10:00:00",
  "updated_at": null
}
```

Note: Password is never returned in response

#### Get All Users
**GET** `/core/user`

Response:
```json
{
  "count": 5,
  "data": [
    { "id": 1, "username": "john_doe", "email": "john@example.com", ... },
    { "id": 2, "username": "jane_doe", "email": "jane@example.com", ... }
  ]
}
```

#### Update User
**PUT** `/core/user/{id}`

Request body:
```json
{
  "email": "newemail@example.com",
  "first_name": "Jonathan",
  "is_active": 1
}
```

Response:
```json
{
  "success": true,
  "message": "User updated successfully"
}
```

#### Delete User
**DELETE** `/core/user/{id}`

Response:
```json
{
  "success": true,
  "message": "User deleted successfully"
}
```

---

### Roles Endpoints

#### Create Role
**POST** `/roles`

Request:
```json
{
  "name": "admin",
  "description": "Administrator role"
}
```

#### Get Role
**GET** `/roles/{id}`

#### Get All Roles
**GET** `/roles`

#### Update Role
**PUT** `/roles/{id}`

#### Delete Role
**DELETE** `/roles/{id}`

---

## Articles Module

### Endpoints

#### Create Article
**POST** `/articles/article`

Request body:
```json
{
  "title": "Introduction to teraCore",
  "content": "This is a comprehensive guide...",
  "summary": "Quick overview of teraCore",
  "author_id": 1,
  "status": "draft"
}
```

Response:
```json
{
  "success": true,
  "message": "Article created successfully"
}
```

Note: Slug is auto-generated from title

#### Get Article
**GET** `/articles/article/{id}`

Response:
```json
{
  "id": 1,
  "title": "Introduction to teraCore",
  "slug": "introduction-to-teracore",
  "content": "...",
  "summary": "...",
  "author_id": 1,
  "status": "draft",
  "published_at": null,
  "created_at": "2026-02-08 10:00:00",
  "updated_at": null
}
```

#### Get All Articles
**GET** `/articles/article`

Optional filters:
- `?status=published` - Filter by status
- `?author_id=1` - Filter by author

Response:
```json
{
  "count": 10,
  "data": [ ... ]
}
```

#### Update Article
**PUT** `/articles/article/{id}`

Request:
```json
{
  "title": "Updated Title",
  "status": "published",
  "published_at": "2026-02-08 10:00:00"
}
```

#### Delete Article
**DELETE** `/articles/article/{id}`

---

### Categories Endpoints

#### Create Category
**POST** `/articles/category`

Request:
```json
{
  "name": "Technology",
  "description": "Tech-related articles"
}
```

#### Get Category
**GET** `/articles/category/{id}`

#### Get All Categories
**GET** `/articles/category`

#### Update Category
**PUT** `/articles/category/{id}`

#### Delete Category
**DELETE** `/articles/category/{id}`

---

### Tags Endpoints

#### Create Tag
**POST** `/articles/tag`

Request:
```json
{
  "name": "php"
}
```

#### Get Tag
**GET** `/articles/tag/{id}`

#### Get All Tags
**GET** `/articles/tag`

#### Update Tag
**PUT** `/articles/tag/{id}`

#### Delete Tag
**DELETE** `/articles/tag/{id}`

---

## Error Handling

### Common Errors

#### 404 Not Found
```json
{
  "error": "User not found"
}
```

#### 400 Bad Request
```json
{
  "error": "Invalid email"
}
```

#### 500 Internal Server Error
```json
{
  "error": "Internal server error"
}
```

---

## Data Validation

### User Fields
- `username`: Required, 3-100 chars, unique
- `email`: Required, valid email format, unique
- `password`: Required, min 6 chars
- `first_name`: Optional, max 50 chars
- `last_name`: Optional, max 50 chars
- `is_active`: Optional, boolean (0 or 1)

### Article Fields
- `title`: Required, 5-200 chars
- `content`: Required, min 10 chars
- `summary`: Optional
- `author_id`: Required, must exist in users table
- `status`: Optional (draft, published, archived)
- `slug`: Auto-generated from title

---

## Rate Limiting

No rate limiting is currently implemented. This can be added in a future version.

---

## Authentication

No built-in authentication is currently implemented. For security in production:
1. Add JWT token authentication
2. Implement API keys
3. Use OAuth2 for third-party access

---

## CORS

No CORS headers are set by default. Add middleware to enable CORS if needed.

---

## Examples using cURL

### Create User
```bash
curl -X POST http://localhost:8000/core/user \
  -H "Content-Type: application/json" \
  -d '{
    "username": "testuser",
    "email": "test@example.com",
    "password": "password123"
  }'
```

### Get User
```bash
curl http://localhost:8000/core/user/1
```

### Update User
```bash
curl -X PUT http://localhost:8000/core/user/1 \
  -H "Content-Type: application/json" \
  -d '{"email": "newemail@example.com"}'
```

### Delete User
```bash
curl -X DELETE http://localhost:8000/core/user/1
```

### Get All Articles
```bash
curl "http://localhost:8000/articles/article?status=published"
```

---

## Versioning

API endpoints are not versioned at this time. All endpoints use base version 1.

---

## Status Codes

| Code | Meaning |
|------|---------|
| 200  | OK - Successful GET, PUT |
| 201  | Created - Successful POST |
| 400  | Bad Request - Invalid input |
| 404  | Not Found - Resource doesn't exist |
| 405  | Method Not Allowed - Invalid HTTP method |
| 500  | Internal Server Error |

---

## Future Enhancements

- [ ] Pagination support
- [ ] Sorting options
- [ ] Advanced filtering
- [ ] Authentication/Authorization
- [ ] Rate limiting
- [ ] API versioning
- [ ] GraphQL endpoint
- [ ] Webhook support

