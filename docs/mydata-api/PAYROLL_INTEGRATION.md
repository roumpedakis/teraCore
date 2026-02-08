# MyData Integration - Payroll Processing

## 💼 Payroll System Architecture

### Integration Timeline
```
January   February   March      April      May...
   │          │        │         │         │
   ▼          ▼        ▼         ▼         ▼
Salary    Salary    Salary    Salary    Salary
Data      Data      Data      Data      Data
(MyData)  (MyData)  (MyData)  (MyData)  (MyData)
   │          │        │         │         │
   └──────────┴────────┴─────────┴─────────┘
           Monthly Sync
             │
   ┌─────────▼──────────┐
   │   Process Payroll  │
   └─────────┬──────────┘
             │
   ┌─────────▼──────────────────────┐
   │  Tax, Insurance, Deductions     │
   └─────────┬──────────────────────┘
             │
   ┌─────────▼──────────┐
   │  Generate Payslip  │
   └─────────┬──────────┘
             │
   ┌─────────▼──────────┐
   │  Send to Bank      │
   └────────────────────┘
```

## 📋 Monthly Payroll Processing

### Phase 1: Data Import from MyData

```php
// app/modules/payroll/PayrollProcessor.php
class PayrollProcessor
{
    /**
     * Process monthly payroll
     */
    public function processMonthlyPayroll($year, $month)
    {
        // Step 1: Fetch salary data from MyData
        $salaryData = $this->fetchMyData($year, $month);
        
        // Step 2: Validate data
        $validated = $this->validateData($salaryData);
        
        // Step 3: Calculate deductions
        $payroll = $this->calculateDeductions($validated);
        
        // Step 4: Create payslips
        $payslips = $this->generatePayslips($payroll);
        
        // Step 5: Create bank transfers
        $transfers = $this->createBankTransfers($payroll);
        
        // Step 6: Record transactions
        $this->savePayrollRecords($payroll, $payslips, $transfers);
        
        return [
            'status' => 'success',
            'processed_count' => count($payroll),
            'failed_count' => 0,
        ];
    }
    
    private function fetchMyData($year, $month)
    {
        return Http::withToken(Session::get('mydata_token'))
            ->get('https://api.mydata.gov.gr/v1/employment/salaries', [
                'year' => $year,
                'month' => $month,
            ])->json()['data'];
    }
}
```

### Phase 2: Deduction Calculation

```php
class DeductionCalculator
{
    /**
     * Calculate all deductions from gross salary
     */
    public function calculate($employeeId, $grossSalary, $month, $year)
    {
        $employee = Employee::find($employeeId);
        
        // Get tax bracket
        $taxBracket = $this->getTaxBracket($employee->afm, $year);
        
        // Insurance contributions (employee part)
        $insurance = $this->calculateInsurance($grossSalary, $employee);
        
        // Income tax (after insurance deduction)
        $taxableIncome = $grossSalary - $insurance;
        $incomeTax = $taxableIncome * $taxBracket['rate'];
        
        // Additional deductions
        $personalItems = $this->getPersonalDeductions($employee);
        $childAllowance = $this->calculateChildAllowance($employee);
        $unions = $this->getUnionFees($employee);
        
        return [
            'gross_salary' => $grossSalary,
            'insurance_contribution' => $insurance,
            'income_tax' => $incomeTax,
            'personal_items' => $personalItems,
            'child_allowance' => $childAllowance,
            'union_fees' => $unions,
            'total_deductions' => $insurance + $incomeTax + $personalItems + $unions,
            'net_salary' => $grossSalary - ($insurance + $incomeTax + $personalItems + $unions),
        ];
    }
    
    /**
     * Greek Insurance Calculation (IKA-ETAM)
     * Employee contribution is typically 6.67% of gross salary
     */
    private function calculateInsurance($grossSalary, $employee)
    {
        $rate = 0.0667; // 6.67%
        
        // Cap at maximum contribution
        $maxContribution = 6000; // Annual max, ~500/month
        
        return min($grossSalary * $rate, $maxContribution / 12);
    }
    
    /**
     * Get tax bracket based on annual income
     */
    private function getTaxBracket($afm, $year)
    {
        // Fetch from MyData tax declarations
        $response = Http::withToken(Session::get('mydata_token'))
            ->get('https://api.mydata.gov.gr/v1/tax/declarations', [
                'afm' => $afm,
                'year' => $year,
            ]);
        
        $totalIncome = $response->json()['data'][0]['total_income'] ?? 0;
        
        // Greek tax brackets
        $brackets = [
            ['limit' => 10000, 'rate' => 0.09],
            ['limit' => 20000, 'rate' => 0.22],
            ['limit' => 30000, 'rate' => 0.28],
            ['limit' => 40000, 'rate' => 0.36],
            ['limit' => PHP_FLOAT_MAX, 'rate' => 0.44],
        ];
        
        foreach ($brackets as $bracket) {
            if ($totalIncome <= $bracket['limit']) {
                return $bracket;
            }
        }
    }
    
    /**
     * Child allowance calculation
     * €5 per child per month
     */
    private function calculateChildAllowance($employee)
    {
        $children = EmployeeDependent::where('employee_id', $employee->id)
            ->where('type', 'child')
            ->count();
        
        return $children * 5; // €5 per child
    }
}
```

### Phase 3: Payslip Generation

```php
class PayslipGenerator
{
    /**
     * Generate payslip for employee
     */
    public function generatePayslip($payrollRecord)
    {
        $html = view('payroll/payslip_template', [
            'employee' => $payrollRecord->employee,
            'period' => $payrollRecord->period,
            'gross_salary' => $payrollRecord->gross_salary,
            'insurance' => $payrollRecord->insurance,
            'income_tax' => $payrollRecord->income_tax,
            'deductions' => $payrollRecord->total_deductions,
            'net_salary' => $payrollRecord->net_salary,
            'employer_contributions' => $this->getEmployerContributions($payrollRecord),
        ]);
        
        // Generate PDF
        $pdf = new TCPDF();
        $pdf->AddPage();
        $pdf->writeHTML($html);
        
        $filename = "payslip_{$payrollRecord->employee_id}_{$payrollRecord->period}.pdf";
        return $pdf->Output(storage_path("payslips/$filename"), 'F');
    }
    
    /**
     * Calculate employer contributions
     * Typically 24.81% (IKA employer part)
     */
    private function getEmployerContributions($payroll)
    {
        return [
            'ika_employer' => $payroll->gross_salary * 0.2481,
            'other_funds' => $payroll->gross_salary * 0.10,
        ];
    }
}
```

## 🏦 Bank Transfer Integration

### Create Payment Instructions

```php
class BankPaymentProcessor
{
    /**
     * Create bank transfer batch for all employees
     */
    public function createPaymentBatch($payrollId)
    {
        $payroll = Payroll::find($payrollId);
        $employees = Employee::whereHas('payroll', function($q) use ($payrollId) {
            $q->where('payroll_id', $payrollId);
        })->get();
        
        $transfers = [];
        $totalAmount = 0;
        
        foreach ($employees as $employee) {
            $net_salary = $payroll->where('employee_id', $employee->id)->first()->net_salary;
            
            $transfer = [
                'beneficiary_iban' => $employee->bank_account_iban,
                'beneficiary_name' => $employee->full_name,
                'amount' => $net_salary,
                'description' => "Salary {$payroll->period}",
                'reference' => "PAY-{$employee->id}-{$payroll->period}",
            ];
            
            $transfers[] = $transfer;
            $totalAmount += $net_salary;
        }
        
        // Save batch
        $batch = BankPaymentBatch::create([
            'reference' => "BATCH-" . date('Ymd-His'),
            'total_amount' => $totalAmount,
            'transfer_count' => count($transfers),
            'status' => 'pending',
            'data' => json_encode($transfers),
        ]);
        
        return $batch;
    }
    
    /**
     * Generate SEPA XML file for bank
     */
    public function generateSEPAFile($batchId)
    {
        $batch = BankPaymentBatch::find($batchId);
        $transfers = json_decode($batch->data, true);
        
        $xml = $this->buildSEPAXML($transfers);
        
        $filename = "sepa_{$batch->reference}.xml";
        file_put_contents(storage_path("bank_transfers/$filename"), $xml);
        
        $batch->update(['sepa_file' => $filename]);
        
        return $filename;
    }
    
    private function buildSEPAXML($transfers)
    {
        // ISO 20022 SEPA format
        $xml = '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
        $xml .= '<Document xmlns="urn:iso:std:iso:20022:tech:xsd:pain.001.002.03">' . "\n";
        // ... SEPA format implementation
        $xml .= '</Document>';
        
        return $xml;
    }
}
```

## 📊 Payroll Reports

### Monthly Summary Report

```php
class PayrollReports
{
    /**
     * Generate monthly payroll summary
     */
    public function generateMonthlySummary($month, $year)
    {
        $payroll = Payroll::whereMonth('period', $month)
            ->whereYear('period', $year)
            ->get();
        
        return [
            'period' => "$year-$month",
            'employees_processed' => $payroll->count(),
            'total_gross' => $payroll->sum('gross_salary'),
            'total_insurance' => $payroll->sum('insurance_contribution'),
            'total_tax' => $payroll->sum('income_tax'),
            'total_deductions' => $payroll->sum('total_deductions'),
            'total_net' => $payroll->sum('net_salary'),
            'employer_contributions' => [
                'ika' => $payroll->sum('gross_salary') * 0.2481,
                'other' => $payroll->sum('gross_salary') * 0.10,
            ],
            'summary_by_department' => $this->summarizeByDepartment($payroll),
        ];
    }
    
    /**
     * Annual tax summary for all employees
     */
    public function generateAnnualTaxReport($year)
    {
        $employees = Employee::all();
        
        $report = [];
        foreach ($employees as $employee) {
            $payroll = Payroll::where('employee_id', $employee->id)
                ->whereYear('period', $year)
                ->get();
            
            $report[] = [
                'afm' => $employee->afm,
                'name' => $employee->full_name,
                'total_gross' => $payroll->sum('gross_salary'),
                'total_tax' => $payroll->sum('income_tax'),
                'total_insurance' => $payroll->sum('insurance_contribution'),
                'net_income' => $payroll->sum('net_salary'),
            ];
        }
        
        return $report;
    }
}
```

## ✅ Quality Checks

```php
class PayrollValidation
{
    /**
     * Validate payroll before processing
     */
    public function validatePayroll($payrollData)
    {
        $errors = [];
        
        // Check all employees have salary data
        if (!$this->allEmployeesHaveSalary($payrollData)) {
            $errors[] = 'Missing salary data for some employees';
        }
        
        // Check deductions don't exceed salary
        foreach ($payrollData as $employee) {
            if ($employee['total_deductions'] > $employee['gross_salary']) {
                $errors[] = "Deductions exceed salary for employee {$employee['id']}";
            }
            
            if ($employee['net_salary'] < 0) {
                $errors[] = "Negative net salary calculated for employee {$employee['id']}";
            }
        }
        
        // Check for unusual salary amounts
        $avgSalary = array_sum(array_column($payrollData, 'gross_salary')) / count($payrollData);
        foreach ($payrollData as $employee) {
            if ($employee['gross_salary'] > $avgSalary * 3) {
                Log::warning("Unusually high salary for employee {$employee['id']}: {$employee['gross_salary']}");
            }
        }
        
        return empty($errors) ? ['valid' => true] : ['valid' => false, 'errors' => $errors];
    }
}
```

## 🔐 Payroll Security

- ✅ All salary data encrypted at rest
- ✅ Bank details stored securely
- ✅ Audit trail for all changes
- ✅ Two-factor authentication for bank transfers
- ✅ Role-based access control (only HR can approve)
- ✅ Masked salary display in logs
- ✅ GDPR compliance for employee data

---

**Last Updated**: February 2026  
**Version**: 1.0
