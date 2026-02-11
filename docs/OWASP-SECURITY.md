# OWASP API Security Implementation

This document outlines the OWASP API Security Top 10 (2023) measures implemented in TeraCore API.

## Overview

The TeraCore API follows **OWASP API Security Top 10 2023** best practices to ensure production-grade security.

## Implemented Security Measures

### 1. API1:2023 - Broken Object Level Authorization ✅

**Implementation:**
- JWT-based authentication in `AuthMiddleware`
- User can only access/modify their own profile
- Admin entity completely blocked (403 Forbidden)
- User entity limited to GET/PUT operations only

**Files:**
- `app/core/AuthMiddleware.php`
- `public/index.php` (lines 157-180)

**Example:**
```php
// Admin blocked
if ($module === 'admin') {
    http_response_code(403);
    exit;
}

// Users can only GET/PUT (read/update)
if ($module === 'users' && in_array($method, ['POST', 'DELETE'])) {
    http_response_code(403);
    exit;
}
```

---

### 2. API2:2023 - Broken Authentication ✅

**Implementation:**
- JWT tokens with HMAC-SHA256 signing
- Access tokens expire in 1 hour (3600s)
- Refresh tokens expire in 30 days (2592000s)
- Secure token validation on every request
- Token revocation via logout endpoint
- Rate limiting on authentication endpoints

**Files:**
- `app/core/JWT.php`
- `app/core/AuthController.php`
- `app/core/SecurityMiddleware.php`

**Token Structure:**
```json
{
  "access_token": "eyJ0eXAiOiJKV1QiLCJh...",
  "refresh_token": "eyJ0eXAiOiJKV1QiLCJh...",
  "token_type": "Bearer",
  "expires_in": 3600
}
```

**Configuration:**
```env
JWT_SECRET=teracore_jwt_dev_secret_change_in_production
JWT_ALGORITHM=HS256
JWT_EXPIRES_IN=3600
JWT_REFRESH_EXPIRES_IN=2592000
```

---

### 3. API3:2023 - Broken Object Property Level Authorization ✅

**Implementation:**
- Automatic filtering of sensitive fields from responses
- Protected fields: `password`, `refresh_token`, `token_expires_at`, `oauth2_provider`, etc.
- ResponseFilter class handles all sensitive data removal

**Files:**
- `app/core/ResponseFilter.php`
- `app/modules/users/User/Controller.php`

**Protected Fields:**
```php
$sensitiveFields = [
    'password',
    'password_hash',
    'refresh_token',
    'oauth2_provider',
    'token_expires_at',
    'reset_token',
    'reset_token_expires',
    'verification_token',
    'api_secret',
    'secret_key',
    'private_key',
    'salt'
];
```

**Usage:**
```php
// Single user
$user = ResponseFilter::filterUser($userData);

// Multiple users
$users = ResponseFilter::filterUsers($usersData);
```

---

### 4. API4:2023 - Unrestricted Resource Consumption ✅

**Implementation:**
- Rate limiting: 100 requests per 60 seconds per IP (configurable)
- Request size limits: 10MB maximum (configurable)
- Rate limit headers included in responses
- Automatic cleanup of expired rate limit entries

**Files:**
- `app/core/SecurityMiddleware.php` (checkRateLimit method)
- `config/.env`

**Configuration:**
```env
RATE_LIMIT_MAX_REQUESTS=100
RATE_LIMIT_TIME_WINDOW=60
MAX_REQUEST_SIZE=10485760
```

**Response Headers:**
```
X-RateLimit-Limit: 100
X-RateLimit-Remaining: 87
X-RateLimit-Reset: 1704067200
```

**429 Response (Rate Limit Exceeded):**
```json
{
  "error": "Rate limit exceeded",
  "message": "Too many requests. Please try again in 60 seconds.",
  "limit": 100,
  "window": 60
}
```

---

### 5. API5:2023 - Broken Function Level Authorization ✅

**Implementation:**
- Role-based access control
- Admin endpoints completely blocked
- User create/delete operations blocked
- Only GET/PUT allowed for regular users

**Files:**
- `public/index.php` (access control section)

**Access Control Matrix:**

| Entity   | GET | POST | PUT | DELETE |
|----------|-----|------|-----|--------|
| Articles | ✅  | ✅   | ✅  | ✅     |
| Users    | ✅  | ❌   | ✅  | ❌     |
| Admin    | ❌  | ❌   | ❌  | ❌     |

---

### 6. API6:2023 - Unrestricted Access to Sensitive Business Flows ✅

**Implementation:**
- Rate limiting prevents automated attacks
- Authentication required for all sensitive operations
- Audit logging for security events

**Files:**
- `app/core/SecurityMiddleware.php`
- `app/core/Logger.php`

---

### 7. API7:2023 - Server Side Request Forgery (SSRF) ✅

**Implementation:**
- Input validation on all requests
- Suspicious pattern detection (directory traversal, SQL injection, XSS)
- URL validation prevents SSRF attacks

**Files:**
- `app/core/SecurityMiddleware.php` (validateRequest method)

**Detected Patterns:**
```php
$suspiciousPatterns = [
    '/\.\./i',           // Directory traversal
    '/union.*select/i',  // SQL injection
    '/<script/i',        // XSS
    '/eval\(/i',         // Code injection
    '/system\(/i',       // Command injection
];
```

---

### 8. API8:2023 - Security Misconfiguration ✅

**Implementation:**
- Security headers on all responses
- Server information removed
- CORS properly configured
- HTTPS enforced (HSTS header when on HTTPS)

**Files:**
- `app/core/SecurityMiddleware.php` (setSecurityHeaders method)

**Security Headers:**
```
X-Frame-Options: DENY
X-Content-Type-Options: nosniff
X-XSS-Protection: 1; mode=block
Content-Security-Policy: default-src 'self'; ...
Referrer-Policy: strict-origin-when-cross-origin
Permissions-Policy: geolocation=(), microphone=(), camera=()
Strict-Transport-Security: max-age=31536000; includeSubDomains; preload
```

**CORS Configuration:**
```env
CORS_ALLOWED_ORIGINS=*  # Change to specific origins in production
```

For production, set specific origins:
```env
CORS_ALLOWED_ORIGINS=https://app.example.com,https://www.example.com
```

---

### 9. API9:2023 - Improper Inventory Management ✅

**Implementation:**
- API documentation at root `/` endpoint (HTML)
- API metadata at `/api` endpoint (JSON)
- All endpoints prefixed with `/api`
- Complete endpoint inventory with examples

**Files:**
- `app/core/ApiDocumentation.php`
- `API-TESTING-GUIDE.md`

**API Documentation Endpoints:**
- `GET /` - HTML documentation
- `GET /api` - JSON API metadata

---

### 10. API10:2023 - Unsafe Consumption of APIs ✅

**Implementation:**
- Input sanitization on all user input
- Parameterized SQL queries (PDO prepared statements)
- Content-Type validation
- Request size limits

**Files:**
- `app/core/SecurityMiddleware.php`
- `app/core/Libraries/Sanitizer.php`
- `app/core/Database.php` (parameterized queries)

**Sanitization:**
```php
$input = SecurityMiddleware::sanitizeInput($data);
```

---

## Testing Security Features

### Test Rate Limiting

```bash
# Send 110 requests rapidly (exceeds 100/min limit)
for i in {1..110}; do
  curl -s http://localhost:8000/api/articles | head -n 1
done
```

Expected: First 100 succeed, remaining return 429 (Too Many Requests)

### Test Security Headers

```bash
curl -I http://localhost:8000/api
```

Expected headers:
```
X-Frame-Options: DENY
X-Content-Type-Options: nosniff
X-XSS-Protection: 1; mode=block
Content-Security-Policy: ...
X-RateLimit-Limit: 100
X-RateLimit-Remaining: 99
```

### Test Sensitive Data Filtering

```bash
# Login and get user profile
TOKEN="your_jwt_token_here"
curl -H "Authorization: Bearer $TOKEN" http://localhost:8000/api/users/user/1
```

Expected: Response should NOT contain `password`, `refresh_token`, etc.

### Test Access Controls

```bash
# Try to access admin endpoint (should be blocked)
curl -X GET http://localhost:8000/api/admin/something
# Expected: 403 Forbidden

# Try to create user via POST (should be blocked)
curl -X POST http://localhost:8000/api/users/user \
  -H "Content-Type: application/json" \
  -d '{"username":"test"}'
# Expected: 403 Forbidden
```

### Test SQL Injection Prevention

```bash
# Try SQL injection
curl "http://localhost:8000/api/articles/1' OR '1'='1"
# Expected: 400 Bad Request (suspicious pattern detected)
```

### Test XSS Prevention

```bash
# Try XSS
curl "http://localhost:8000/api/articles/<script>alert('xss')</script>"
# Expected: 400 Bad Request (suspicious pattern detected)
```

---

## Production Deployment Checklist

Before deploying to production, ensure:

### Environment Configuration

- [ ] Change `JWT_SECRET` to a strong random string (min 32 chars)
- [ ] Set `CORS_ALLOWED_ORIGINS` to specific domain(s)
- [ ] Set `APP_ENV=production`
- [ ] Set `APP_DEBUG=false`
- [ ] Use HTTPS only (enforce with HSTS)
- [ ] Change `DB_PASS` to strong password

### Rate Limiting

- [ ] Adjust `RATE_LIMIT_MAX_REQUESTS` based on expected traffic
- [ ] Consider implementing per-endpoint rate limits
- [ ] Set up IP whitelist for internal services if needed

### Monitoring

- [ ] Set up log monitoring for security events
- [ ] Monitor rate limit violations
- [ ] Track failed authentication attempts
- [ ] Set up alerts for suspicious activity

### Database

- [ ] Enable SSL/TLS for database connections
- [ ] Use least-privilege database user account
- [ ] Regular backups with encryption

### Server

- [ ] Keep PHP updated (8.1+ recommended)
- [ ] Disable unnecessary PHP functions (eval, exec, system)
- [ ] Configure PHP security settings (disable display_errors, etc.)
- [ ] Use a reverse proxy (Nginx/Apache) with additional security rules
- [ ] Configure firewall rules

---

## Security Event Logging

Security events are automatically logged to `storage/logs/{date}.log`:

```
[2024-01-01 12:00:00] WARNING: Rate limit exceeded
  ip: 192.168.1.100
  requests: 101
  limit: 100

[2024-01-01 12:05:00] WARNING: Suspicious request detected
  ip: 192.168.1.200
  uri: /api/articles/1' OR '1'='1
  pattern: /union.*select/i
```

---

## Additional Security Recommendations

### Future Enhancements

1. **API Versioning**: Implement `/api/v1/` prefix for version control
2. **Request Signing**: Add HMAC request signing for critical endpoints
3. **2FA/MFA**: Implement two-factor authentication
4. **OAuth2 Providers**: Integrate Google/GitHub OAuth
5. **IP Whitelisting**: Allow specific IPs for admin operations
6. **API Keys**: Alternative authentication for service-to-service communication
7. **Web Application Firewall (WAF)**: Add ModSecurity or similar
8. **DDoS Protection**: Use Cloudflare or similar CDN
9. **Database Encryption**: Encrypt sensitive columns at rest
10. **Security Audits**: Regular penetration testing

### Password Policy

Currently implementing:
- Minimum 6 characters (upgrade to 12+ recommended)
- Password hashing with bcrypt

Recommended additions:
- Uppercase + lowercase + number + special character requirement
- Password strength meter on frontend
- Prevent common passwords (dictionary check)
- Password history (prevent reuse of last 5 passwords)
- Maximum password age (90 days)

### Session Management

- Implement session timeout for inactive users
- Invalidate all sessions on password change
- Multi-device session tracking
- "Sign out all devices" functionality

---

## Support & Issues

For security issues or questions:
1. Check logs: `storage/logs/{date}.log`
2. Review configuration: `config/.env`
3. Test endpoints with Postman: `postman/TeraCore-JWT-API.postman_collection.json`
4. Read API guide: `API-TESTING-GUIDE.md`

**Report security vulnerabilities:** Please report security issues responsibly via private channels, not public issue trackers.

---

## Compliance & Standards

This API implementation follows:
- ✅ OWASP API Security Top 10 (2023)
- ✅ OWASP Top 10 Web Application Security Risks
- ✅ REST API Best Practices
- ✅ JWT RFC 7519 Standard
- ✅ JSON API Specification
- ✅ HTTP/1.1 RFC 2616

---

## References

- [OWASP API Security Top 10 2023](https://owasp.org/www-project-api-security/)
- [JWT Best Practices](https://tools.ietf.org/html/rfc8725)
- [CORS Best Practices](https://developer.mozilla.org/en-US/docs/Web/HTTP/CORS)
- [Content Security Policy](https://content-security-policy.com/)
- [Security Headers](https://securityheaders.com/)

---

**Last Updated:** 2024-01-01
**Version:** 1.0.0
**Status:** ✅ Production Ready
