# MyData API - Authentication & Security

## 🔐 OAuth2 Authorization Code Flow

### Flow Diagram
```
┌─────────┐                                ┌──────────┐
│ Client  │                                │  MyData  │
│  App    │                                │   API    │
└────┬────┘                                └────┬─────┘
     │  1. Redirect to auth endpoint             │
     ├───────────────────────────────────────────>
     │                                           │
     │  2. User authenticates & consents        │
     │  <─────────────────────────────────────── │
     │                                           │
     │  3. Return to app with auth code        │
     │  <─────────────────────────────────────── │
     │                                           │
     │  4. Exchange code for token             │
     ├───────────────────────────────────────────>
     │                                           │
     │  5. Return access token                 │
     │  <─────────────────────────────────────── │
```

### Step 1: Redirect User to Authorization Endpoint

```php
// teraCore: app/core/Auth/MyDataOAuth.php
$authUrl = "https://mydata.gov.gr/oauth/authorize?" . http_build_query([
    'client_id' => config('MYDATA_CLIENT_ID'),
    'redirect_uri' => config('MYDATA_REDIRECT_URI'),
    'response_type' => 'code',
    'scope' => 'profile tax employment realestate',
    'state' => bin2hex(random_bytes(32)), // CSRF protection
]);

header("Location: $authUrl");
```

### Step 2: Handle OAuth Callback

```php
// teraCore: app/modules/auth/oauth/Controller.php
public function handleCallback($code, $state)
{
    // Validate state
    if ($state !== session('oauth_state')) {
        throw new Exception('Invalid state parameter');
    }
    
    // Exchange code for token
    $accessToken = $this->getAccessToken($code);
    
    // Store token
    Session::set('mydata_token', $accessToken);
    
    // Redirect to app
    return redirect('/dashboard');
}

private function getAccessToken($code)
{
    $response = curl_post('https://api.mydata.gov.gr/v1/auth/token', [
        'client_id' => config('MYDATA_CLIENT_ID'),
        'client_secret' => config('MYDATA_CLIENT_SECRET'),
        'code' => $code,
        'grant_type' => 'authorization_code',
        'redirect_uri' => config('MYDATA_REDIRECT_URI'),
    ]);
    
    return $response['access_token'];
}
```

## 🛡️ JWT Token Structure

### Decoded JWT
```json
{
  "header": {
    "alg": "RS256",
    "typ": "JWT"
  },
  "payload": {
    "sub": "user-uuid",
    "iat": 1707475200,
    "exp": 1707478800,
    "iss": "https://mydata.gov.gr",
    "aud": "your-client-id",
    "scope": "profile tax employment",
    "tenant": "mydata"
  },
  "signature": "..."
}
```

### Token Validation
```php
public function validateToken($token)
{
    try {
        $decoded = (array) JWT::decode(
            $token,
            new Key(file_get_contents('mydata-public-key.pem'), 'RS256')
        );
        
        if ($decoded['exp'] < time()) {
            throw new Exception('Token expired');
        }
        
        return $decoded;
    } catch (Exception $e) {
        throw new AuthException('Invalid token: ' . $e->getMessage());
    }
}
```

## 🔄 Token Refresh Strategy

```php
public function ensureValidToken()
{
    $token = Session::get('mydata_token');
    $refreshToken = Session::get('mydata_refresh_token');
    
    if (!$token) {
        redirect('/mydata/login');
    }
    
    // Check if token expires in next 5 minutes
    $payload = JWT::decode($token, ...);
    if ($payload['exp'] - time() < 300) {
        $token = $this->refreshToken($refreshToken);
        Session::set('mydata_token', $token);
    }
    
    return $token;
}

private function refreshToken($refreshToken)
{
    $response = curl_post('https://api.mydata.gov.gr/v1/auth/refresh', [
        'client_id' => config('MYDATA_CLIENT_ID'),
        'client_secret' => config('MYDATA_CLIENT_SECRET'),
        'refresh_token' => $refreshToken,
        'grant_type' => 'refresh_token',
    ]);
    
    return $response['access_token'];
}
```

## ✅ Scope Constants

```php
class MyDataScopes
{
    const PROFILE = 'profile';
    const TAX = 'tax';
    const EMPLOYMENT = 'employment';
    const REALESTATE = 'realestate';
    const BANK = 'bank';
    const INSURANCE = 'insurance';
    
    public static function all()
    {
        return [
            self::PROFILE,
            self::TAX,
            self::EMPLOYMENT,
            self::REALESTATE,
            self::BANK,
            self::INSURANCE,
        ];
    }
}
```

## 🔒 Storing Sensitive Data

**❌ NEVER:**
```php
// DON'T store in database plaintext
DB::insert('SELECT password FROM users');

// DON'T log tokens
Log::info('Token: ' . $token);

// DON'T pass as GET parameter
redirect('/callback?token=' . $token);
```

**✅ DO:**
```php
// Store encrypted in database
$encrypted = Encrypt::encrypt($token);
DB::set('tokens', ['token' => $encrypted, 'user_id' => $userId]);

// Store in secure session
Session::set('mydata_token', $token);
session_start(['cookie_httponly' => true, 'cookie_secure' => true]);

// Use HTTP-only cookies
setcookie('mydata_token', $token, [
    'expires' => time() + 3600,
    'path' => '/',
    'secure' => true,
    'httponly' => true,
    'samesite' => 'Lax'
]);
```

## 🚨 Error Handling

```php
try {
    $userData = $myDataClient->getUserProfile();
} catch (UnauthorizedException $e) {
    // Token expired or invalid - redirect to login
    Session::clear('mydata_token');
    redirect('/mydata/login');
} catch (InsufficientScopeException $e) {
    // Need to request additional scopes
    Log::warning('Insufficient MyData scopes: ' . $e->getRequiredScope());
    redirect('/mydata/consent?scopes=' . urlencode($e->getRequiredScope()));
} catch (RateLimitException $e) {
    // Too many requests
    Log::error('MyData rate limit exceeded');
    throw new ApiException('Service temporarily unavailable', 503);
} catch (Exception $e) {
    // Log and handle
    Log::error('MyData API error: ' . $e->getMessage());
    throw new ApiException('Failed to fetch MyData', 500);
}
```

## 📋 Security Checklist

- ✅ Use HTTPS only
- ✅ Store client_secret securely (environment variable)
- ✅ Validate `state` parameter to prevent CSRF
- ✅ Use secure random numbers for state
- ✅ Set token expiration appropriately
- ✅ Implement token refresh logic
- ✅ Log authentication attempts (without sensitive data)
- ✅ Rate limit authentication endpoints
- ✅ Use HTTP-only cookies
- ✅ Implement CSRF tokens
- ✅ Validate SSL certificates

---

**Last Updated**: February 2026  
**Version**: 1.0
