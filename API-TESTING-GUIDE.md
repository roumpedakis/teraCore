# TeraCore API - Testing Guide

## ✅ All Endpoints Working

Your API is fully functional and tested! Here's how to use it:

---

## 🚀 Quick Start with Postman

### 1. Import the Collection
- Open **Postman**
- Go to `File` → `Import`
- Select: `postman/TeraCore-JWT-API.postman_collection.json`
- Click **Import**

### 2. Set Base URL (if needed)
The collection defaults to `http://localhost:8000`. Change it if using a different port:
- Click the request
- Look for `{{baseUrl}}` variable
- Update in the **Variables** tab

### 3. Test Authentication Flow

#### Step 1: Login (Get Tokens)
- Find and run: **"🔐 Authentication → Login"**
- Request body already has test credentials:
  ```json
  {
    "username_or_email": "postman_test",
    "password": "PostmanTest123"
  }
  ```
- **Response** contains:
  ```json
  {
    "access_token": "eyJ0eXAiOiJKV1QiLCJhbGc...",
    "refresh_token": "eyJ0eXAiOiJKV1QiLCJhbGc...",
    "token_type": "Bearer"
  }
  ```
- ✅ Tokens are **automatically saved** to Postman variables!

#### Step 2: Verify Token
- Run: **"🔐 Authentication → Verify Token"**
- Token is automatically added from variables: `Authorization: Bearer {{accessToken}}`
- Returns user info if valid ✓

---

## 📋 API Endpoints (All Working)

### Authentication (🔐)
| Method | Endpoint | Auth Required | Purpose |
|--------|----------|---------------|---------|
| POST | `/api/auth/register` | ❌ No | Create new user account |
| POST | `/api/auth/login` | ❌ No | Get JWT tokens |
| POST | `/api/auth/refresh` | ❌ No | Refresh access token |
| POST | `/api/auth/logout` | ✅ Yes | Invalidate tokens |
| GET | `/api/auth/verify` | ✅ Yes | Verify token validity |

### Articles (📄) - Full CRUD
| Method | Endpoint | Auth Required | Purpose |
|--------|----------|---------------|---------|
| GET | `/api/articles/article` | ❌ No | Get all articles |
| GET | `/api/articles/article/{id}` | ❌ No | Get single article |
| POST | `/api/articles/article` | ✅ Yes | Create article |
| PUT | `/api/articles/article/{id}` | ✅ Yes | Update article |
| DELETE | `/api/articles/article/{id}` | ✅ Yes | Delete article |

### Users (👥) - Limited Access
| Method | Endpoint | Auth Required | Purpose |
|--------|----------|---------------|---------|
| GET | `/api/users/user/{id}` | ✅ Yes | Get user info |
| PUT | `/api/users/user/{id}` | ✅ Yes | Update own profile |
| ❌ POST | `/api/users/user` | - | **Blocked** - Use register instead |
| ❌ DELETE | `/api/users/user/{id}` | - | **Blocked** - Users cannot delete |

### Categories & Tags (🏷️)
| Method | Endpoint | Auth Required | Purpose |
|--------|----------|---------------|---------|
| GET | `/api/articles/category` | ❌ No | Get all categories |
| GET | `/api/articles/tag` | ❌ No | Get all tags |

### Admin (⚙️)
| Status | Purpose |
|--------|---------|
| 🚫 **Blocked** | Admin entity has **no API access** - 403 Forbidden |

---

## 🧪 Manual Testing with cURL

### 1. Register User
```bash
curl -X POST http://localhost:8000/api/auth/register \
  -H "Content-Type: application/json" \
  -d '{
    "username": "newuser",
    "email": "newuser@example.com",
    "password": "Password123"
  }'
```

### 2. Login
```bash
curl -X POST http://localhost:8000/api/auth/login \
  -H "Content-Type: application/json" \
  -d '{
    "username_or_email": "postman_test",
    "password": "PostmanTest123"
  }'
```
**Copy the `access_token` from response!**

### 3. Use Token for Protected Endpoints
```bash
curl -X GET http://localhost:8000/api/auth/verify \
  -H "Authorization: Bearer YOUR_ACCESS_TOKEN_HERE"
```

### 4. Create Article (requires token)
```bash
curl -X POST http://localhost:8000/api/articles/article \
  -H "Content-Type: application/json" \
  -H "Authorization: Bearer YOUR_ACCESS_TOKEN_HERE" \
  -d '{
    "title": "My Article",
    "content": "Article content here...",
    "status": "published"
  }'
```

### 5. Get Articles (public, no token needed)
```bash
curl -X GET http://localhost:8000/api/articles/article
```

---

## 📖 API Documentation

### View in Browser
Open in browser: **http://localhost:8000/**
- Beautiful interactive HTML documentation
- Shows all endpoints, parameters, examples
- Color-coded by HTTP method

### View as JSON
```bash
curl http://localhost:8000/api
```
Returns JSON with all endpoint metadata

---

## 🔑 JWT Token Details

### Access Token
- **Duration**: 1 hour (3600 seconds)
- **Format**: Bearer token in Authorization header
- **Usage**: `Authorization: Bearer <token>`
- **Expires**: Automatically after 1 hour

### Refresh Token
- **Duration**: 30 days
- **Purpose**: Get new access token without re-login
- **Stored**: In database (can be revoked)
- **Endpoint**: POST `/api/auth/refresh`

### Token Structure
```
Header: {
  "alg": "HS256",
  "typ": "JWT"
}

Payload: {
  "user_id": 7,
  "iat": 1770563494,
  "exp": 1770567094
}

Signature: HMAC-SHA256(secret)
```

---

## ✨ Key Features Implemented

✅ **JWT Authentication** - HMAC-SHA256 signed tokens  
✅ **Access & Refresh Tokens** - Stateless + stateful hybrid  
✅ **Register/Login/Logout** - Complete auth flow  
✅ **Token Refresh** - Get new access token without login  
✅ **Token Verification** - Validate tokens on protected routes  
✅ **Public Documentation** - HTML & JSON API docs  
✅ **Access Controls**:
  - Articles: Full CRUD for authenticated users
  - Users: Read + update only (no create/delete)
  - Admin: Completely blocked from API
✅ **Error Handling** - Proper HTTP status codes (401, 403, 405)  

---

## 🐛 Troubleshooting

### "Invalid credentials" on login
- ✅ Test user exists: `postman_test` / `PostmanTest123`
- Register new user if needed via `/api/auth/register`

### "Token expired" error
- Get a new access token using `/api/auth/refresh` with refresh_token
- Or login again

### "No token provided" error  
- Add Authorization header: `Authorization: Bearer <token>`
- Check Bearer format (space between Bearer and token)

### "Not found" (404) error
- Check endpoint path has `/api/` prefix
- Verify HTTP method (GET vs POST vs PUT vs DELETE)

---

## 📂 Test Credentials

**Pre-created test user:**
- **Username**: `postman_test`
- **Email**: `postman@test.com`
- **Password**: `PostmanTest123`

**Test with Postman:**
1. Click "Login" request in collection
2. Body already has credentials
3. Send the request
4. Tokens auto-save to variables
5. All other requests will use saved token ✓

---

## 🎯 Next Steps

1. **Open Postman** → Import `TeraCore-JWT-API.postman_collection.json`
2. **Click Login** → Get tokens
3. **Test other endpoints** → All variables populated
4. **Create articles** → Test POST/PUT/DELETE
5. **Check access controls** → Try admin/user endpoints to see restrictions

---

Happy testing! 🚀
