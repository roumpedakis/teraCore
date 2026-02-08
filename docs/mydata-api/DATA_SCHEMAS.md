# MyData API - Data Schemas & Specifications

## 📋 User Profile Schema

```json
{
  "id": "uuid-string",
  "name": "Γιάννης Παπαδόπουλος",
  "email": "giannis@example.com",
  "phone": "+30-210-1234567",
  "birth_date": "1990-05-15",
  "afm": "123456789",
  "amka": "01234567890",
  "address": {
    "street": "Αγίας Σοφίας 10",
    "street_number": "10",
    "city": "Αθήνα",
    "postal_code": "10100",
    "prefecture": "Αττική",
    "country": "GR",
    "country_name": "Ελλάδα"
  },
  "documents": {
    "id_number": "AB123456",
    "passport_number": "PA123456",
    "driver_license": "AB123456"
  },
  "created_at": "2020-01-01T00:00:00Z",
  "updated_at": "2026-02-08T10:30:00Z"
}
```

## 💰 Tax Declaration Schema

```json
{
  "id": "tax-declaration-2025",
  "year": 2025,
  "submitted_date": "2026-02-08T10:30:00Z",
  "status": "submitted",
  "type": "personal",
  "income_sources": [
    {
      "type": "employment",
      "description": "Μισθωτή εργασία",
      "amount": 35000.50
    },
    {
      "type": "self_employment",
      "description": "Ελευθέρα επαγγελματική δραστηριότητα",
      "amount": 5000.00
    },
    {
      "type": "rental",
      "description": "Εισοδήματα από ακίνητα",
      "amount": 3000.00
    }
  ],
  "total_income": 43000.50,
  "deductions": [
    {
      "type": "insurance",
      "amount": 2500.00
    },
    {
      "type": "donation",
      "amount": 500.00
    }
  ],
  "total_deductions": 3000.00,
  "taxable_income": 40000.50,
  "calculated_tax": 8400.10,
  "paid_tax": 8500.00,
  "tax_difference": 99.90,
  "status_details": "Approved on 2026-02-08"
}
```

## 💼 Employment Schema

```json
{
  "id": "emp-001",
  "employer": "Τεχνολογίες Α.Ε.",
  "employer_afm": "987654321",
  "position": "Senior Developer",
  "position_code": "IT-002",
  "employment_type": "full_time",
  "start_date": "2020-01-15",
  "end_date": null,
  "status": "active",
  "contract_type": "permanent",
  "working_days_per_week": 5,
  "working_hours_per_week": 40,
  "salary_type": "monthly",
  "base_salary": 45000,
  "currency": "EUR",
  "insurance_fund": "IKA",
  "insurance_id": "1234567890",
  "department": "Ανάπτυξη Λογισμικού",
  "manager": "Μαρία Εconomopoulos",
  "benefits": [
    "health_insurance",
    "meal_vouchers",
    "transportation_allowance"
  ]
}
```

## 📊 Salary Payment Schema

```json
{
  "id": "sal-2025-01-001",
  "employee_id": "emp-001",
  "employer": "Τεχνολογίες Α.Ε.",
  "period": "2025-01",
  "month": 1,
  "year": 2025,
  "payment_date": "2025-01-31",
  "gross_salary": 3750.00,
  "deductions": {
    "income_tax": 575.00,
    "social_insurance": 250.00,
    "health_insurance": 75.00,
    "unemployment_insurance": 12.50,
    "union_fees": 25.00
  },
  "total_deductions": 937.50,
  "net_salary": 2812.50,
  "employer_contributions": {
    "social_insurance_employer": 931.25,
    "health_insurance_employer": 112.50,
    "unemployment_insurance_employer": 37.50,
    "other_funds": 375.00
  },
  "total_employer_contribution": 1456.25,
  "notes": "Μισθός Ιανουαρίου 2025"
}
```

## 🏠 Real Estate Schema

```json
{
  "id": "prop-001",
  "owner_afm": "123456789",
  "address": {
    "street": "Αγίας Σοφίας",
    "street_number": "10",
    "city": "Αθήνα",
    "postal_code": "10100",
    "prefecture": "Αττική",
    "country": "GR"
  },
  "property_details": {
    "type": "residential",
    "subtype": "apartment",
    "built_year": 1990,
    "total_square_meters": 85,
    "land_square_meters": 100,
    "floors": 4,
    "floor_number": 2,
    "number_of_rooms": 2,
    "number_of_bathrooms": 1
  },
  "registration": {
    "registration_number": "ΑΒ1234567",
    "notary_office": "Αθήνα",
    "registration_date": "2020-03-15"
  },
  "ownership": {
    "ownership_percentage": 100,
    "joint_owners": false,
    "ownership_status": "sole_owner"
  },
  "valuation": {
    "estimated_value": 250000,
    "currency": "EUR",
    "valuation_date": "2025-12-31",
    "valuation_method": "professional_assessment"
  },
  "taxation": {
    "property_tax_code": "Π123456789",
    "annual_property_tax": 750.00,
    "last_tax_year": 2025
  },
  "mortgage": {
    "mortgaged": false,
    "mortgage_lender": null,
    "mortgage_amount": null
  }
}
```

## 🏦 Bank Account Schema

```json
{
  "id": "bank-001",
  "bank_name": "Τράπεζα της Ελλάδος",
  "account_type": "checking",
  "account_number": "123456789",
  "iban": "GR1601101250000000012300695",
  "swift": "BNMAGR2A",
  "account_holder": "Γιάννης Παπαδόπουλος",
  "currency": "EUR",
  "status": "active",
  "opened_date": "2015-06-10",
  "account_feature": [
    "online_banking",
    "mobile_banking",
    "debit_card"
  ]
}
```

## 🔑 OAuth Token Schema

```json
{
  "access_token": "eyJ0eXAiOiJKV1QiLCJhbGciOiJSUzI1NiIsImtpZCI6IjEyMzQ1Njc4OTAifQ...",
  "token_type": "Bearer",
  "expires_in": 3600,
  "refresh_token": "refresh_token_value_here",
  "scope": "profile tax employment realestate",
  "issued_at": "2026-02-08T10:30:00Z"
}
```

### JWT Payload (Decoded)
```json
{
  "sub": "user-uuid-123",
  "iat": 1707475200,
  "exp": 1707478800,
  "iss": "https://mydata.gov.gr",
  "aud": "your-client-id",
  "scope": "profile tax employment",
  "tenant": "mydata",
  "name": "Γιάννης Παπαδόπουλος",
  "email": "giannis@example.com",
  "afm": "123456789"
}
```

## 📝 Consent Grant Schema

```json
{
  "id": "consent-001",
  "user_id": "user-uuid",
  "client_id": "your-client-id",
  "client_name": "HR Management System",
  "scopes_granted": [
    "profile",
    "tax",
    "employment"
  ],
  "granted_at": "2026-01-15T08:00:00Z",
  "expires_at": "2027-01-15T08:00:00Z",
  "status": "active",
  "data_accessed": [
    {
      "scope": "profile",
      "last_accessed": "2026-02-08T10:30:00Z",
      "access_count": 5
    },
    {
      "scope": "tax",
      "last_accessed": "2026-02-07T14:20:00Z",
      "access_count": 2
    }
  ]
}
```

## 🔄 API Response Envelope

**Standard Success Response:**
```json
{
  "success": true,
  "data": {
    "id": "...",
    "...": "..."
  },
  "timestamp": "2026-02-08T10:30:00Z",
  "request_id": "req-abc123def456"
}
```

**Standard Error Response:**
```json
{
  "success": false,
  "error": "invalid_parameter",
  "error_description": "The 'year' parameter is invalid",
  "error_code": 400,
  "timestamp": "2026-02-08T10:30:00Z",
  "request_id": "req-abc123def456"
}
```

**Paginated Response:**
```json
{
  "success": true,
  "data": [
    { "id": 1, "...": "..." },
    { "id": 2, "...": "..." }
  ],
  "pagination": {
    "page": 1,
    "per_page": 50,
    "total": 1234,
    "total_pages": 25,
    "has_next": true,
    "has_previous": false
  },
  "timestamp": "2026-02-08T10:30:00Z"
}
```

---

**Last Updated**: February 2026  
**Version**: 1.0
