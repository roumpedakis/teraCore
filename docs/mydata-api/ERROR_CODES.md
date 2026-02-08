# MyData API - Error Codes & Troubleshooting

## 🔴 HTTP Status Codes

| Code | Error | Solution |
|------|-------|----------|
| 200 | OK | Success - process response normally |
| 201 | Created | Resource created successfully |
| 400 | Bad Request | Check request parameters and format |
| 401 | Unauthorized | Token invalid/expired - re-authenticate |
| 403 | Forbidden | Insufficient permissions - check scopes |
| 404 | Not Found | Resource doesn't exist |
| 429 | Too Many Requests | Rate limit exceeded - retry later |
| 500 | Server Error | MyData server issue - retry after delay |
| 503 | Service Unavailable | Maintenance - check status page |

## 🔐 Authentication Errors

### 401 Unauthorized: invalid_token

```json
{
  "error": "invalid_token",
  "error_description": "Token has expired"
}
```

**Solution:**
```php
// Refresh the token
$response = Http::post('https://api.mydata.gov.gr/v1/auth/refresh', [
    'client_id' => env('MYDATA_CLIENT_ID'),
    'client_secret' => env('MYDATA_CLIENT_SECRET'),
    'refresh_token' => $user->refresh_token,
    'grant_type' => 'refresh_token',
]);

if ($response->successful()) {
    Session::set('mydata_token', $response->json()['access_token']);
} else {
    // Force re-authentication
    redirect('/mydata/login');
}
```

### 401 Unauthorized: invalid_client

```json
{
  "error": "invalid_client",
  "error_description": "Client authentication failed"
}
```

**Causes:**
- Wrong `client_id` or `client_secret`
- Credentials not set in environment variables
- Application not registered properly

**Solution:**
```php
// Verify in .env file
MYDATA_CLIENT_ID=your-client-id
MYDATA_CLIENT_SECRET=your-client-secret

// Check they're loaded
echo env('MYDATA_CLIENT_ID'); // Should not be null
```

### 401 Unauthorized: insufficient_scope

```json
{
  "error": "insufficient_scope",
  "error_description": "This resource requires scope: tax",
  "scope": "tax"
}
```

**Solution:**
```php
// Request additional scopes from user
$requiredScope = $error->scope ?? 'tax';
redirect("/mydata/login?scope=$requiredScope");
```

---

## 🚫 Permission & Consent Errors

### 403 Forbidden: no_consent

```json
{
  "error": "no_consent",
  "error_description": "User has not granted consent for this scope"
}
```

**Solution:**
```php
// Request user to grant consent
public function requestConsent($scope)
{
    $consentUrl = 'https://mydata.gov.gr/oauth/consent?' . http_build_query([
        'client_id' => env('MYDATA_CLIENT_ID'),
        'scope' => $scope,
        'redirect_uri' => url('/mydata/callback'),
    ]);
    
    return redirect($consentUrl);
}
```

### 403 Forbidden: revoked_consent

```json
{
  "error": "revoked_consent",
  "error_description": "User has revoked consent for this resource"
}
```

**Solution:**
```php
// User revoked access - clear stored data and re-authenticate
User::where('id', auth()->id())->update([
    'mydata_token' => null,
    'mydata_refresh_token' => null,
]);

Session::forget('mydata_token');
redirect('/mydata/login');
```

---

## 📊 Data Validation Errors

### 400 Bad Request: invalid_parameter

```json
{
  "error": "invalid_parameter",
  "error_description": "Invalid year parameter: 1999",
  "parameter": "year"
}
```

**Solution:**
```php
// Validate parameters before API call
$year = request('year', date('Y'));

if ($year < 2000 || $year > date('Y')) {
    throw new ValidationException("Year must be between 2000 and " . date('Y'));
}

// Make API call
Http::withToken($token)
    ->get('/tax/declarations', ['year' => $year]);
```

### 400 Bad Request: required_field_missing

```json
{
  "error": "required_field_missing",
  "error_description": "Field 'afm' is required",
  "field": "afm"
}
```

**Solution:**
```php
// Always validate request data
$validated = request()->validate([
    'afm' => 'required|regex:/^\d{9}$/',
    'year' => 'required|integer|min:2000|max:2100',
]);

// Use validated data in API call
Http::withToken($token)
    ->get('/tax/declarations', $validated);
```

---

## ⏱️ Rate Limiting Errors

### 429 Too Many Requests

```json
{
  "error": "rate_limit_exceeded",
  "error_description": "You have exceeded the rate limit of 1000 requests per hour",
  "retry_after": 60
}
```

**Headers:**
```
X-RateLimit-Limit: 1000
X-RateLimit-Remaining: 0
X-RateLimit-Reset: 1707475200 (Unix timestamp)
```

**Solution:**
```php
public function handleRateLimit(Exception $e)
{
    $retryAfter = $e->response->header('X-RateLimit-Reset');
    
    // Cache the request to retry later
    Cache::put('mydata_rate_limited', true, now()->addSeconds($retryAfter));
    
    // Show user-friendly message
    return response()->json([
        'error' => 'Service temporarily unavailable',
        'retry_after' => $retryAfter,
    ], 429);
}

// In your main service
public function fetchData($endpoint)
{
    if (Cache::has('mydata_rate_limited')) {
        throw new RateLimitException('Rate limit exceeded, retry later');
    }
    
    try {
        return Http::withToken($token)->get($endpoint);
    } catch (Exception $e) {
        if ($e->response->status() === 429) {
            $this->handleRateLimit($e);
        }
        throw $e;
    }
}
```

---

## 🔄 Data Consistency Issues

### Salary Data Mismatch

**Problem:** Salary from MyData ≠ Employee database

**Solution:**
```php
public function validateSalaryConsistency($myDataSalary, $employee)
{
    $dbSalary = $employee->getSalary() ?? 0;
    $variance = abs($myDataSalary - $dbSalary) / max($dbSalary, $myDataSalary);
    
    if ($variance > 0.05) { // More than 5% difference
        Log::warning('Salary discrepancy detected', [
            'employee_id' => $employee->id,
            'mydata_salary' => $myDataSalary,
            'db_salary' => $dbSalary,
            'variance' => $variance,
        ]);
        
        // Alert admin
        Alert::create([
            'type' => 'salary_variance',
            'severity' => 'medium',
            'message' => "Salary mismatch for {$employee->full_name}",
        ]);
    }
}
```

### Missing or Incomplete Data

```php
public function validateDataCompleteness($data)
{
    $requiredFields = ['afm', 'name', 'email', 'birth_date'];
    
    foreach ($requiredFields as $field) {
        if (empty($data[$field])) {
            Log::error('Incomplete MyData: missing ' . $field);
            return false;
        }
    }
    
    return true;
}
```

---

## 🔧 Troubleshooting Checklist

### API Not Responding

```php
// Test connectivity
$test = Http::timeout(5)->get('https://api.mydata.gov.gr/v1/status');

if ($test->failed()) {
    Log::error('MyData API unreachable');
    // Show maintenance notice
    return view('mydata.maintenance');
}
```

### Token Expiration Issues

```php
// Check token expiration
$expiresAt = Cache::get('mydata_token_expires_' . auth()->id());
$hoursUntilExpiry = ($expiresAt - time()) / 3600;

if ($hoursUntilExpiry < 0.5) {
    Log::info("Token expiring soon for user {auth()->id()}");
    $this->refreshToken();
}
```

### SSL Certificate Errors

```php
// In production, never disable SSL verification
// This is for development only
Http::withoutVerifying() // ❌ NEVER IN PRODUCTION
    ->get('https://api.mydata.gov.gr/...');

// Proper solution: Update PHP certificates
// https://stackoverflow.com/questions/29822686/curl-ssl-certificate-problem-certificate-self-signed
```

---

## 📞 Getting Help

**MyData Support:**
- Email: support@mydata.gov.gr
- Phone: +30 2131-303-000
- Status Page: https://status.mydata.gov.gr/

**Common Issues:**
1. Check status page for service incidents
2. Verify credentials in environment variables
3. Review token expiration
4. Check rate limiting headers
5. Validate request parameters format

**Debug Mode:**
```php
// Enable detailed logging
Log::channel('mydata')->info('API Request', [
    'endpoint' => $endpoint,
    'method' => $method,
    'token' => substr($token, 0, 10) . '...',
    'parameters' => $params,
]);

Log::channel('mydata')->info('API Response', [
    'status' => $response->status(),
    'headers' => $response->headers(),
    'body' => $response->json(),
]);
```

---

**Last Updated**: February 2026  
**Version**: 1.0
