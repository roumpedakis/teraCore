# MyData API - Practical Examples

## 🔐 Complete OAuth2 Flow Example

### Step 1: User Initiates Login

```php
// Route: GET /mydata/login
Route::get('/mydata/login', function() {
    // Generate random state for CSRF protection
    $state = bin2hex(random_bytes(32));
    Session::set('oauth_state', $state);
    
    // Build authorization URL
    $authUrl = 'https://mydata.gov.gr/oauth/authorize?' . http_build_query([
        'client_id' => env('MYDATA_CLIENT_ID'),
        'redirect_uri' => url('/mydata/callback'),
        'response_type' => 'code',
        'scope' => 'profile tax employment',
        'state' => $state,
    ]);
    
    return redirect($authUrl);
});
```

### Step 2: Handle OAuth Callback

```php
// Route: GET /mydata/callback?code=XXX&state=YYY
Route::get('/mydata/callback', function() {
    $code = request('code');
    $state = request('state');
    
    // Validate state
    if ($state !== Session::get('oauth_state')) {
        throw new Exception('Invalid state parameter - CSRF attack detected');
    }
    
    // Exchange code for token
    $response = Http::post('https://api.mydata.gov.gr/v1/auth/token', [
        'client_id' => env('MYDATA_CLIENT_ID'),
        'client_secret' => env('MYDATA_CLIENT_SECRET'),
        'code' => $code,
        'grant_type' => 'authorization_code',
        'redirect_uri' => url('/mydata/callback'),
    ]);
    
    if ($response->failed()) {
        throw new Exception('Failed to exchange code for token');
    }
    
    $tokens = $response->json();
    
    // Store tokens securely in session
    Session::set('mydata_access_token', $tokens['access_token']);
    Session::set('mydata_refresh_token', $tokens['refresh_token']);
    Session::set('mydata_token_expires', time() + $tokens['expires_in']);
    
    // Fetch user profile
    $profile = Http::withToken($tokens['access_token'])
        ->get('https://api.mydata.gov.gr/v1/user/profile')
        ->json();
    
    // Create/update user in database
    $user = User::updateOrCreate(
        ['mydata_id' => $profile['id']],
        [
            'name' => $profile['name'],
            'email' => $profile['email'],
            'afm' => $profile['afm'],
            'mydata_token' => encrypt($tokens['access_token']),
            'mydata_refresh_token' => encrypt($tokens['refresh_token']),
        ]
    );
    
    auth()->login($user);
    
    return redirect('/dashboard');
});
```

## 💾 Fetching User Data from MyData

### Get Profile Data

```php
// app/modules/mydata/profile/Controller.php
class ProfileController extends BaseController
{
    public function getUserProfile()
    {
        try {
            $token = $this->getValidToken();
            
            $response = Http::withToken($token)
                ->get('https://api.mydata.gov.gr/v1/user/profile');
            
            if ($response->failed()) {
                throw new Exception('Failed to fetch profile');
            }
            
            return $this->response->json([
                'success' => true,
                'data' => $response->json(),
            ]);
        } catch (Exception $e) {
            return $this->response->json([
                'success' => false,
                'error' => $e->getMessage(),
            ], 400);
        }
    }
    
    /**
     * Get valid token, refresh if needed
     */
    private function getValidToken()
    {
        $user = auth()->user();
        $expiresAt = Session::get('mydata_token_expires', 0);
        
        // If token expires in less than 5 minutes, refresh it
        if ($expiresAt - time() < 300) {
            $this->refreshToken($user);
        }
        
        return Session::get('mydata_access_token');
    }
    
    private function refreshToken($user)
    {
        $response = Http::post('https://api.mydata.gov.gr/v1/auth/refresh', [
            'client_id' => env('MYDATA_CLIENT_ID'),
            'client_secret' => env('MYDATA_CLIENT_SECRET'),
            'refresh_token' => decrypt($user->mydata_refresh_token),
            'grant_type' => 'refresh_token',
        ]);
        
        if ($response->failed()) {
            throw new Exception('Failed to refresh token');
        }
        
        $tokens = $response->json();
        Session::set('mydata_access_token', $tokens['access_token']);
        Session::set('mydata_token_expires', time() + $tokens['expires_in']);
    }
}
```

### Get Tax Information

```php
class TaxController extends BaseController
{
    public function getTaxDeclarations($year = null)
    {
        $year = $year ?? date('Y');
        $token = $this->getValidToken();
        
        try {
            $response = Http::withToken($token)
                ->get('https://api.mydata.gov.gr/v1/tax/declarations', [
                    'year' => $year,
                ]);
            
            if ($response->failed()) {
                throw new Exception('Failed to fetch tax data');
            }
            
            $declarations = $response->json()['data'] ?? [];
            
            // Format response
            return $this->response->json([
                'year' => $year,
                'declarations' => $declarations,
                'summary' => [
                    'total_income' => array_sum(array_column($declarations, 'total_income')),
                    'total_tax' => array_sum(array_column($declarations, 'total_tax')),
                    'count' => count($declarations),
                ],
            ]);
        } catch (Exception $e) {
            return $this->response->json(['error' => $e->getMessage()], 400);
        }
    }
}
```

### Get Employment History

```php
class EmploymentController extends BaseController
{
    public function getEmploymentHistory()
    {
        $token = $this->getValidToken();
        
        $response = Http::withToken($token)
            ->get('https://api.mydata.gov.gr/v1/employment/history');
        
        if ($response->failed()) {
            return $this->response->json(['error' => 'Failed to fetch employment data'], 400);
        }
        
        $jobs = $response->json()['data'] ?? [];
        
        // Group by status (active, inactive)
        $active = array_filter($jobs, fn($j) => $j['status'] === 'active');
        $inactive = array_filter($jobs, fn($j) => $j['status'] !== 'active');
        
        return $this->response->json([
            'active_employment' => array_values($active),
            'employment_history' => array_values($inactive),
            'current_employer' => reset($active),
        ]);
    }
    
    public function getSalaryHistory($year = null, $month = null)
    {
        $token = $this->getValidToken();
        $year = $year ?? date('Y');
        
        $response = Http::withToken($token)
            ->get('https://api.mydata.gov.gr/v1/employment/salaries', [
                'year' => $year,
                'month' => $month,
            ]);
        
        if ($response->failed()) {
            return $this->response->json(['error' => 'Failed to fetch salary data'], 400);
        }
        
        $salaries = $response->json()['data'] ?? [];
        
        // Calculate totals
        return $this->response->json([
            'period' => $month ? "$year-" . str_pad($month, 2, '0', STR_PAD_LEFT) : $year,
            'salaries' => $salaries,
            'summary' => [
                'months' => count($salaries),
                'total_gross' => array_sum(array_column($salaries, 'gross_salary')),
                'average_salary' => array_sum(array_column($salaries, 'gross_salary')) / max(1, count($salaries)),
            ],
        ]);
    }
}
```

## 🏢 Real Estate Data

```php
class RealEstateController extends BaseController
{
    public function getProperties()
    {
        $token = $this->getValidToken();
        
        $response = Http::withToken($token)
            ->get('https://api.mydata.gov.gr/v1/realestate/properties');
        
        if ($response->failed()) {
            return $this->response->json(['error' => 'Failed to fetch properties'], 400);
        }
        
        $properties = $response->json()['data'] ?? [];
        
        // Calculate total property value
        $totalValue = array_sum(array_column($properties, 'value'));
        
        return $this->response->json([
            'properties' => $properties,
            'count' => count($properties),
            'total_value' => $totalValue,
            'by_type' => $this->groupByType($properties),
        ]);
    }
    
    private function groupByType($properties)
    {
        $grouped = [];
        foreach ($properties as $prop) {
            $type = $prop['type'] ?? 'unknown';
            if (!isset($grouped[$type])) {
                $grouped[$type] = ['count' => 0, 'total_value' => 0];
            }
            $grouped[$type]['count']++;
            $grouped[$type]['total_value'] += $prop['value'];
        }
        return $grouped;
    }
}
```

## 💡 React Frontend Example

```jsx
// frontend/components/MyDataIntegration.jsx
import React, { useState, useEffect } from 'react';

function MyDataIntegration() {
    const [userProfile, setUserProfile] = useState(null);
    const [taxData, setTaxData] = useState(null);
    const [salaries, setSalaries] = useState(null);
    const [loading, setLoading] = useState(false);

    useEffect(() => {
        fetchUserData();
    }, []);

    const handleLogin = () => {
        window.location.href = '/mydata/login';
    };

    const fetchUserData = async () => {
        setLoading(true);
        try {
            const profile = await fetch('/api/mydata/profile').then(r => r.json());
            const tax = await fetch('/api/mydata/tax?year=2025').then(r => r.json());
            const sal = await fetch('/api/mydata/employment/salaries').then(r => r.json());
            
            setUserProfile(profile.data);
            setTaxData(tax);
            setSalaries(sal);
        } catch (error) {
            console.error('Failed to fetch data:', error);
        } finally {
            setLoading(false);
        }
    };

    return (
        <div className="mydata-integration">
            <h2>MyData Integration</h2>
            
            {!userProfile ? (
                <button onClick={handleLogin}>Login with MyData</button>
            ) : (
                <>
                    <div className="user-info">
                        <h3>Profile</h3>
                        <p>Name: {userProfile.name}</p>
                        <p>AFM: {userProfile.afm}</p>
                        <p>Email: {userProfile.email}</p>
                    </div>
                    
                    <div className="tax-info">
                        <h3>Tax Information (2025)</h3>
                        <p>Total Income: €{taxData.summary.total_income}</p>
                        <p>Tax: €{taxData.summary.total_tax}</p>
                    </div>
                    
                    <div className="salary-info">
                        <h3>Current Year Salaries</h3>
                        <p>Average Monthly: €{salaries.summary.average_salary.toFixed(2)}</p>
                        <p>Total: €{salaries.summary.total_gross.toFixed(2)}</p>
                    </div>
                </>
            )}
        </div>
    );
}

export default MyDataIntegration;
```

---

**Last Updated**: February 2026  
**Version**: 1.0
