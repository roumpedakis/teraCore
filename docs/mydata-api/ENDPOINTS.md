# MyData API - Endpoints Αναφορά

## 🔗 Base URL
```
https://api.mydata.gov.gr/v1
```

## 👤 User Profile Endpoints

### GET /user/profile
Λήψη προφίλ χρήστη

**Request:**
```bash
curl -H "Authorization: Bearer TOKEN" \
  https://api.mydata.gov.gr/v1/user/profile
```

**Response:**
```json
{
  "id": "user-uuid",
  "name": "Γιάννης Παπαδόπουλος",
  "email": "giannis@example.com",
  "phone": "+30-210-1234567",
  "afm": "123456789",
  "address": {
    "street": "Αγίας Σοφίας 10",
    "city": "Αθήνα",
    "postal_code": "10100",
    "country": "GR"
  }
}
```

**Status Codes:**
- `200` - Success
- `401` - Unauthorized
- `403` - Forbidden
- `404` - Not Found

---

## 💰 Tax Data Endpoints

### GET /tax/declarations
Λήψη φορολογικών δηλώσεων

```bash
curl -H "Authorization: Bearer TOKEN" \
  https://api.mydata.gov.gr/v1/tax/declarations?year=2025
```

**Query Parameters:**
- `year` - Φορολογικό έτος (π.χ. 2025)
- `status` - `submitted`, `pending`, `approved`, `rejected`
- `limit` - Αριθμός αποτελεσμάτων (default: 50)
- `offset` - Σελιδοποίηση (default: 0)

**Response:**
```json
{
  "count": 3,
  "data": [
    {
      "id": "declaration-2025",
      "year": 2025,
      "submitted_date": "2026-02-08T10:30:00Z",
      "status": "submitted",
      "total_income": 35000.50,
      "total_tax": 7000.15
    }
  ]
}
```

### GET /tax/declarations/{id}
Λήψη λεπτομερειών δήλωσης

```bash
curl -H "Authorization: Bearer TOKEN" \
  https://api.mydata.gov.gr/v1/tax/declarations/declaration-2025
```

---

## 💼 Employment Data Endpoints

### GET /employment/history
Ιστορικό απασχόλησης

```bash
curl -H "Authorization: Bearer TOKEN" \
  https://api.mydata.gov.gr/v1/employment/history
```

**Response:**
```json
{
  "count": 2,
  "data": [
    {
      "id": "emp-001",
      "employer": "Τεχνολογίες Α.Ε.",
      "position": "Senior Developer",
      "start_date": "2020-01-15",
      "end_date": null,
      "status": "active",
      "salary": 45000
    }
  ]
}
```

### GET /employment/salaries
Μισθολόγια

```bash
curl -H "Authorization: Bearer TOKEN" \
  https://api.mydata.gov.gr/v1/employment/salaries?year=2025
```

**Query Parameters:**
- `year` - Έτος (default: current)
- `month` - Μήνας (1-12)

**Response:**
```json
{
  "count": 12,
  "data": [
    {
      "month": 1,
      "year": 2025,
      "gross_salary": 3750.00,
      "net_salary": 2950.00,
      "employer": "Τεχνολογίες Α.Ε.",
      "payment_date": "2025-01-31"
    }
  ]
}
```

### GET /employment/insurance
Ασφαλιστικά δεδομένα

```bash
curl -H "Authorization: Bearer TOKEN" \
  https://api.mydata.gov.gr/v1/employment/insurance
```

---

## 🏠 Real Estate Endpoints

### GET /realestate/properties
Κατάσταση ακινήτων

```bash
curl -H "Authorization: Bearer TOKEN" \
  https://api.mydata.gov.gr/v1/realestate/properties
```

**Response:**
```json
{
  "count": 2,
  "data": [
    {
      "id": "prop-001",
      "address": "Αγίας Σοφίας 10, 10100 Αθήνα",
      "type": "residential",
      "registration_number": "ΑΒ1234567",
      "value": 250000,
      "ownership_percentage": 100
    }
  ]
}
```

### GET /realestate/properties/{id}/tax
Φόροι ιδιοκτησίας

```bash
curl -H "Authorization: Bearer TOKEN" \
  https://api.mydata.gov.gr/v1/realestate/properties/prop-001/tax
```

---

## 🔐 Authentication Endpoints

### POST /auth/authorize
Έναρξη OAuth2 flow

```bash
curl -X POST https://api.mydata.gov.gr/v1/auth/authorize \
  -d "client_id=YOUR_CLIENT_ID&redirect_uri=YOUR_REDIRECT&scope=profile,tax,employment"
```

**Parameters:**
- `client_id` - Application ID
- `redirect_uri` - Callback URL
- `scope` - Requested permissions
- `state` - CSRF protection

**Response:**
```
https://mydata.gov.gr/oauth/login?code=AUTH_CODE&state=STATE
```

### POST /auth/token
Ανταλλαγή κώδικα για token

```bash
curl -X POST https://api.mydata.gov.gr/v1/auth/token \
  -H "Content-Type: application/json" \
  -d '{
    "client_id": "YOUR_CLIENT_ID",
    "client_secret": "YOUR_CLIENT_SECRET",
    "code": "AUTH_CODE",
    "grant_type": "authorization_code",
    "redirect_uri": "YOUR_REDIRECT"
  }'
```

**Response:**
```json
{
  "access_token": "eyJ0eXAiOiJKV1Q...",
  "token_type": "Bearer",
  "expires_in": 3600,
  "refresh_token": "REFRESH_TOKEN",
  "scope": "profile tax employment"
}
```

### POST /auth/refresh
Ανανέωση Access Token

```bash
curl -X POST https://api.mydata.gov.gr/v1/auth/refresh \
  -H "Content-Type: application/json" \
  -d '{
    "client_id": "YOUR_CLIENT_ID",
    "client_secret": "YOUR_CLIENT_SECRET",
    "refresh_token": "REFRESH_TOKEN",
    "grant_type": "refresh_token"
  }'
```

### POST /auth/revoke
Ανακλήση πρόσβασης

```bash
curl -X POST https://api.mydata.gov.gr/v1/auth/revoke \
  -H "Authorization: Bearer TOKEN" \
  -d "client_id=YOUR_CLIENT_ID"
```

---

## 📊 Consent Management Endpoints

### GET /consents
Λήψη συγκαταθέσεων

```bash
curl -H "Authorization: Bearer TOKEN" \
  https://api.mydata.gov.gr/v1/consents
```

### POST /consents
Δημιουργία συγκατάθεσης

```bash
curl -X POST https://api.mydata.gov.gr/v1/consents \
  -H "Authorization: Bearer TOKEN" \
  -H "Content-Type: application/json" \
  -d '{
    "scopes": ["profile", "tax"],
    "expires_at": "2027-02-08T00:00:00Z"
  }'
```

### DELETE /consents/{id}
Ανακλήση συγκατάθεσης

```bash
curl -X DELETE https://api.mydata.gov.gr/v1/consents/consent-001 \
  -H "Authorization: Bearer TOKEN"
```

---

## ⚠️ Error Responses

**401 Unauthorized:**
```json
{
  "error": "invalid_token",
  "error_description": "Token has expired"
}
```

**403 Forbidden:**
```json
{
  "error": "insufficient_scope",
  "error_description": "This resource requires scope: tax"
}
```

**404 Not Found:**
```json
{
  "error": "not_found",
  "error_description": "Resource not found"
}
```

**429 Rate Limited:**
```json
{
  "error": "rate_limit_exceeded",
  "error_description": "Too many requests. Retry after 60 seconds"
}
```

---

## 🔄 Rate Limiting

- **Limit**: 1000 requests/hour
- **Headers**:
  - `X-RateLimit-Limit: 1000`
  - `X-RateLimit-Remaining: 999`
  - `X-RateLimit-Reset: 1707475200`

---

## 📚 Scopes

| Scope | Description |
|-------|-------------|
| `profile` | Ταυτοτικά στοιχεία |
| `tax` | Φορολογικά δεδομένα |
| `employment` | Εργασιακά δεδομένα |
| `realestate` | Ακίνητα |
| `bank` | Τραπεζικά δεδομένα |
| `insurance` | Ασφαλιστικά |

---

**Last Updated**: February 2026  
**Version**: 1.0  
**Status**: Production
