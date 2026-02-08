# MyData Integration - ERP Systems

## 📊 ERP Integration Architecture

### High-Level Flow
```
┌──────────────┐         ┌─────────────┐         ┌─────────────┐
│   MyData     │ ────>   │  teraCore   │ ────>   │     ERP     │
│   API        │ OAuth2  │  Middleware │ Sync    │   System    │
└──────────────┘         └─────────────┘         └─────────────┘
     ↓
  Tax Data
  Company Data
  Employee Data
     ↓
  Validate & Transform
     ↓
  Send to ERP
```

## 🏢 Company Data from MyData

### Extract Company Information
```php
// app/modules/mydata/company/Model.php
class Company extends BaseModel
{
    protected $table = 'mydata_companies';
    
    public function fetchFromMyData($afm)
    {
        $response = Http::withToken(Session::get('mydata_token'))
            ->get('https://api.mydata.gov.gr/v1/company/details', [
                'afm' => $afm
            ]);
        
        if ($response->failed()) {
            throw new ApiException('Failed to fetch company data');
        }
        
        return $response->json();
    }
    
    public function getCompanyForERP($companyId)
    {
        $company = $this->find($companyId);
        
        return [
            'code' => $company['afm'],
            'name' => $company['name'],
            'address' => $company['address'],
            'city' => $company['city'],
            'postal_code' => $company['postal_code'],
            'country' => 'GR',
            'tax_id' => $company['afm'],
            'legal_form' => $company['legal_form'],
        ];
    }
}
```

## 👥 Employee Data Synchronization

### Schema: MyData to ERP
```php
class EmployeeSync
{
    /**
     * Map MyData employee to ERP format
     */
    public function mapEmployee(array $myDataEmployee): array
    {
        return [
            'employee_code' => $myDataEmployee['id'],
            'first_name' => $myDataEmployee['first_name'],
            'last_name' => $myDataEmployee['last_name'],
            'email' => $myDataEmployee['email'],
            'phone' => $myDataEmployee['phone'],
            'afm' => $myDataEmployee['afm'],
            'insurance_id' => $myDataEmployee['insurance_id'],
            'bank_account' => $myDataEmployee['bank_account'],
            'birth_date' => $myDataEmployee['birth_date'],
            'tax_bracket' => $myDataEmployee['tax_bracket'],
        ];
    }
    
    /**
     * Sync employee to ERP
     */
    public function syncToERP(string $employeeId): bool
    {
        // Get from MyData
        $employee = $this->fetchFromMyData($employeeId);
        
        // Map to ERP format
        $erpEmployee = $this->mapEmployee($employee);
        
        // Send to ERP via API or direct DB
        return $this->sendToERP($erpEmployee);
    }
}
```

## 💼 Employee Salary Integration

### Automatic Salary Processing
```php
class SalarySync
{
    /**
     * Fetch salary data from MyData
     */
    public function fetchSalaryData($year, $month)
    {
        return Http::withToken(Session::get('mydata_token'))
            ->get('https://api.mydata.gov.gr/v1/employment/salaries', [
                'year' => $year,
                'month' => $month,
            ])->json();
    }
    
    /**
     * Process salary for payroll
     */
    public function processSalaryForPayroll($employeeId, $year, $month)
    {
        $salaryData = $this->fetchSalaryData($year, $month);
        
        // Calculate deductions
        $grossSalary = $salaryData['gross_salary'];
        $insurance = $this->calculateInsurance($grossSalary);
        $tax = $this->calculateTax($grossSalary, $insurance);
        
        $payroll = [
            'employee_id' => $employeeId,
            'period' => "$year-$month",
            'gross_salary' => $grossSalary,
            'insurance' => $insurance,
            'income_tax' => $tax,
            'net_salary' => $grossSalary - $insurance - $tax,
            'source' => 'mydata_api',
        ];
        
        // Insert into ERP payroll
        return $this->saveToERP($payroll);
    }
}
```

## 💰 Deduction & Tax Calculation

### Automatic Tax Bracket Assignment
```php
class TaxCalculator
{
    /**
     * Get tax data from MyData declarations
     */
    public function getTaxDataFromMyData($afm, $year)
    {
        return Http::withToken(Session::get('mydata_token'))
            ->get('https://api.mydata.gov.gr/v1/tax/declarations', [
                'afm' => $afm,
                'year' => $year,
            ])->json();
    }
    
    /**
     * Calculate employee tax bracket for year
     */
    public function calculateTaxBracket($afm, $year)
    {
        $declarations = $this->getTaxDataFromMyData($afm, $year);
        $totalIncome = 0;
        
        foreach ($declarations['data'] as $declaration) {
            $totalIncome += $declaration['total_income'];
        }
        
        // Greek tax brackets 2025
        if ($totalIncome <= 10000) return ['bracket' => 9, 'rate' => 0.09];
        if ($totalIncome <= 20000) return ['bracket' => 22, 'rate' => 0.22];
        if ($totalIncome <= 30000) return ['bracket' => 28, 'rate' => 0.28];
        if ($totalIncome <= 40000) return ['bracket' => 36, 'rate' => 0.36];
        return ['bracket' => 44, 'rate' => 0.44];
    }
}
```

## 📈 Reconciliation with ERP

### Import History
```php
class ImportLog extends BaseModel
{
    protected $table = 'mydata_import_logs';
    
    public function logImport($type, $data, $status = 'success')
    {
        return $this->create([
            'type' => $type, // 'employee', 'salary', 'tax'
            'data' => json_encode($data),
            'status' => $status,
            'imported_at' => now(),
            'synced_to_erp' => false,
        ]);
    }
    
    public function getFailedImports()
    {
        return $this->where('status', 'failed')->get();
    }
}
```

### Validation Before Import
```php
class ImportValidator
{
    public function validateEmployeeData(array $data): bool
    {
        $rules = [
            'afm' => 'required|regex:/^\d{9}$/',
            'first_name' => 'required|string|max:100',
            'last_name' => 'required|string|max:100',
            'email' => 'required|email',
            'birth_date' => 'required|date',
            'insurance_id' => 'required|string',
        ];
        
        return $this->validate($data, $rules);
    }
    
    public function validateSalaryData(array $data): bool
    {
        $rules = [
            'employee_id' => 'required|exists:employees',
            'gross_salary' => 'required|numeric|min:800|max:1000000',
            'period' => 'required|date_format:Y-m',
        ];
        
        return $this->validate($data, $rules);
    }
}
```

## 🔄 Scheduled Sync Operations

### Configure in teraCore

```php
// app/config/scheduler.php
return [
    'sync_employee_data' => [
        'enabled' => true,
        'interval' => 'daily', // daily, weekly, monthly
        'time' => '03:00', // 3 AM
        'job' => 'App\Jobs\SyncEmployeeDataJob',
    ],
    
    'sync_salary_data' => [
        'enabled' => true,
        'interval' => 'monthly',
        'day' => 1,
        'time' => '02:00',
        'job' => 'App\Jobs\SyncSalaryDataJob',
    ],
    
    'sync_tax_data' => [
        'enabled' => true,
        'interval' => 'yearly',
        'day' => '15-03',
        'time' => '04:00',
        'job' => 'App\Jobs\SyncTaxDataJob',
    ],
];
```

### Job Implementation
```php
class SyncEmployeeDataJob extends Job
{
    public function execute()
    {
        $employees = Employee::where('source', 'mydata')->get();
        
        foreach ($employees as $employee) {
            try {
                $this->syncToERP($employee);
                ImportLog::logImport('employee', $employee, 'success');
            } catch (Exception $e) {
                ImportLog::logImport('employee', $employee, 'failed');
                Log::error('Employee sync failed: ' . $e->getMessage());
            }
        }
    }
}
```

## 🎯 ERP Integration Points

| Data Type | ERP Module | Update Frequency | Fields |
|-----------|-----------|------------------|--------|
| Employees | HR/Payroll | Daily | Name, Phone, Email, Address |
| Salaries | Payroll | Monthly | Gross, Deductions, Net |
| Tax Data | Finance | Yearly | Total Income, Tax Bracket |
| Insurance | HR | Quarterly | Insurance Status, Contributions |
| Real Estate | Finance/Assets | Yearly | Property Address, Value |

## ⚠️ Error Handling

```php
try {
    $this->syncEmployeeToERP($employee);
} catch (MyDataAuthException $e) {
    // Re-authenticate user
    Log::warning('MyData auth failed, requesting new consent');
    Session::clear('mydata_token');
    redirect('/mydata/reauth');
} catch (ValidationException $e) {
    // Data validation failed
    Log::error('Employee data validation failed: ' . $e->getMessage());
    ImportLog::logImport('employee', $employee->toArray(), 'validation_failed');
} catch (ERPException $e) {
    // ERP communication failed
    Log::error('ERP sync failed: ' . $e->getMessage());
    ImportLog::logImport('employee', $employee->toArray(), 'erp_sync_failed');
} catch (Exception $e) {
    // Generic error
    Log::error('Unexpected error during sync: ' . $e->getMessage());
    throw $e;
}
```

---

**Last Updated**: February 2026  
**Version**: 1.0
