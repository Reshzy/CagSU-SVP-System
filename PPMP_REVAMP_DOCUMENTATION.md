# PPMP Process Revamp Documentation

## Overview

The PPMP (Project Procurement Management Plan) process has been completely revamped to separate the Annual Procurement Plan (APP) from department-specific PPMPs. This document describes the new workflow, architecture, and implementation details.

## Key Changes

### Before (Old System)
- PPMP items were stored directly in the `ppmp_items` table
- Items were college-specific from the start
- No separation between university-wide catalog and department plans
- Purchase Requests created directly from PPMP items

### After (New System)
- **APP Items** - University-wide catalog imported by Supply Officer
- **PPMP** - Department-specific annual plans created from APP
- **PPMP Items** - Items selected from APP with quarterly quantities
- **Purchase Requests** - Created only from validated PPMP items

## Architecture

### Database Structure

```
app_items (University-wide APP)
    ├── id
    ├── fiscal_year
    ├── category
    ├── item_code (unique per fiscal year)
    ├── item_name
    ├── unit_of_measure
    ├── unit_price
    ├── specifications
    └── is_active

ppmps (Department PPMPs)
    ├── id
    ├── department_id (FK)
    ├── fiscal_year
    ├── status (draft/validated)
    ├── total_estimated_cost
    ├── validated_at
    └── validated_by (FK)

ppmp_items (Items in Department PPMP)
    ├── id
    ├── ppmp_id (FK)
    ├── app_item_id (FK)
    ├── q1_quantity
    ├── q2_quantity
    ├── q3_quantity
    ├── q4_quantity
    ├── total_quantity
    ├── estimated_unit_cost
    └── estimated_total_cost
```

### Relationships

- `app_items` → `ppmp_items` (one-to-many)
- `departments` → `ppmps` (one-to-many)
- `ppmps` → `ppmp_items` (one-to-many)
- `ppmp_items` → `purchase_request_items` (one-to-many)

## Workflow

### 1. APP Import (Supply Officer)

**Command:** `php artisan app:import {file?} {--year=}`

**Process:**
1. Supply Officer uploads APP CSV file
2. System imports items into `app_items` table
3. Items are marked as university-wide (no department assignment)
4. Existing items with same code + fiscal year are updated

**Route:** `/supply/app/import`

### 2. PPMP Creation (Department User)

**Process:**
1. Department user navigates to PPMP dashboard
2. System creates or retrieves PPMP for current fiscal year
3. User selects items from APP catalog
4. User specifies quantities per quarter (Q1-Q4)
5. System calculates total cost automatically
6. User saves PPMP (status: draft)

**Routes:**
- `/ppmp` - View PPMP dashboard
- `/ppmp/create` - Create/edit PPMP

**Budget Validation:**
- System checks if PPMP total ≤ allocated budget
- Real-time calculation in UI
- Server-side validation on save

### 3. PPMP Validation (Department User)

**Process:**
1. User reviews completed PPMP
2. Clicks "Validate PPMP" button
3. System performs budget check
4. If within budget: status → validated, timestamp recorded
5. If exceeds budget: error message, remains draft

**Route:** `POST /ppmp/{ppmp}/validate`

### 4. Purchase Request Creation (Department User)

**Process:**
1. User creates PR from validated PPMP items only
2. System displays only items from department's validated PPMP
3. User selects items and quantities
4. System warns if quantities exceed PPMP quarterly limits
5. PR submitted for approval workflow

**Route:** `/purchase-requests/create`

**Quarterly Tracking:**
- System tracks quantities used per quarter
- Warns when PR exceeds planned quarterly amounts
- Does not block PR creation (warning only)

## Key Features

### 1. Budget Protection

The `PpmpBudgetValidator` service ensures:
- PPMP cannot be validated if total exceeds allocated budget
- Real-time budget status display
- Automatic calculation of utilization percentage

### 2. Quarterly Planning

The `PpmpQuarterlyTracker` service provides:
- Quarter-based quantity tracking (Q1-Q4)
- Usage summary per quarter
- Remaining quantity calculations
- PR quantity validation against PPMP

### 3. Flexible Editing

- Departments can edit PPMP anytime (even after validation)
- Changes recalculate total cost
- Re-validation required if budget exceeded

### 4. One PPMP per Year

- Unique constraint: `[department_id, fiscal_year]`
- System automatically creates or retrieves PPMP
- No duplicate PPMPs per department per year

## API Endpoints

### Supply Officer - APP Management

```
GET  /supply/app              - View APP items
GET  /supply/app/import       - Show import form
POST /supply/app/import       - Process CSV import
```

### Department Users - PPMP Management

```
GET  /ppmp                    - View PPMP dashboard
GET  /ppmp/create             - Create/edit PPMP
POST /ppmp                    - Save PPMP
GET  /ppmp/{ppmp}/edit        - Edit PPMP
PUT  /ppmp/{ppmp}             - Update PPMP
POST /ppmp/{ppmp}/validate    - Validate PPMP
GET  /ppmp/{ppmp}/summary     - View PPMP summary
```

## Services

### PpmpBudgetValidator

**Methods:**
- `validatePpmpAgainstBudget(Ppmp $ppmp): bool`
- `calculatePpmpTotal(array $items): float`
- `getAvailableBudget(Department $dept, int $fiscalYear): float`
- `getBudgetStatus(Ppmp $ppmp): array`

### PpmpQuarterlyTracker

**Methods:**
- `getRemainingQuantity(PpmpItem $item, ?int $quarter): int`
- `canCreatePR(PpmpItem $item, int $qty, ?int $quarter): bool`
- `trackPrAgainstPpmp(PurchaseRequest $pr): array`
- `getUsageSummary(PpmpItem $item): array`
- `validatePrQuantities(PurchaseRequest $pr): array`

## Models

### AppItem

**Key Methods:**
- `active()` - Scope for active items
- `forFiscalYear($year)` - Scope for fiscal year
- `byCategory($category)` - Scope for category
- `getCategories(?int $fiscalYear)` - Get all categories

### Ppmp

**Key Methods:**
- `calculateTotalCost()` - Calculate total from items
- `isWithinBudget()` - Check against allocated budget
- `validate(int $userId)` - Validate PPMP
- `getOrCreateForDepartment($deptId, $year)` - Get or create PPMP

### PpmpItem

**Key Methods:**
- `getTotalQuantity()` - Sum of Q1-Q4
- `getQuarterlyQuantity(int $quarter)` - Get quantity for quarter
- `getRemainingQuantity(?int $quarter)` - Calculate remaining
- `calculateEstimatedTotalCost()` - Total cost calculation

## Testing

### Feature Tests

**PpmpCreationTest:**
- User can view PPMP index
- User can create PPMP with APP items
- PPMP calculates totals correctly

**PpmpBudgetValidationTest:**
- PPMP within budget can be validated
- PPMP exceeding budget cannot be validated
- Budget validator service works correctly
- Budget status provides correct information

### Running Tests

```bash
php artisan test --filter=Ppmp
```

## Migration Guide

### For Existing Installations

1. **Backup Data**
   ```bash
   php artisan db:backup
   ```

2. **Run Migrations**
   ```bash
   php artisan migrate
   ```
   
   This will:
   - Create `app_items` table
   - Create `ppmps` table
   - Drop and recreate `ppmp_items` table
   - Update `purchase_request_items` foreign key

3. **Import APP**
   ```bash
   php artisan app:import "APP-CSE 2025 Form CICS.csv" --year=2025
   ```

4. **Seed Sample Data (Optional)**
   ```bash
   php artisan db:seed --class=AppItemSeeder
   ```

5. **Department Setup**
   - Each department creates their PPMP from APP
   - Users select items and set quarterly quantities
   - Validate PPMP before creating PRs

## Configuration

### Budget Settings

Budget allocation is managed through:
- Budget Office sets allocated budget per department
- Route: `/budget/departments/{department}/edit`
- Model: `DepartmentBudget`

### Fiscal Year

The system uses the current year by default:
```php
$fiscalYear = date('Y');
```

To change fiscal year logic, update:
- `AppItemController`
- `PpmpController`
- `PurchaseRequestController`

## Troubleshooting

### PPMP Cannot Be Validated

**Cause:** Total cost exceeds allocated budget

**Solution:**
1. Check budget status on PPMP dashboard
2. Remove items or reduce quantities
3. Contact Budget Office to increase allocation

### PR Creation Shows "No PPMP"

**Cause:** Department has no validated PPMP

**Solution:**
1. Navigate to `/ppmp`
2. Create PPMP if doesn't exist
3. Validate PPMP
4. Return to PR creation

### APP Import Fails

**Cause:** CSV format incorrect or file not found

**Solution:**
1. Verify CSV file path
2. Check CSV format matches expected structure
3. Review import command output for errors

## Future Enhancements

Potential improvements for future versions:

1. **PPMP Approval Workflow** - Add approval step before validation
2. **Multi-Year Planning** - Support planning across multiple fiscal years
3. **PPMP Templates** - Save and reuse PPMP configurations
4. **Advanced Reporting** - Detailed PPMP vs PR utilization reports
5. **Budget Alerts** - Notifications when approaching budget limits
6. **Bulk Operations** - Import PPMP items from previous year

## Support

For questions or issues:
- Review this documentation
- Check test files for usage examples
- Consult Laravel Boost documentation search
- Contact system administrator

---

**Last Updated:** January 2, 2026
**Version:** 1.0
**Author:** PPMP Revamp Team

