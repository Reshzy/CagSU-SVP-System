# Dynamic Resolution Signatures Implementation

## Overview
Successfully implemented dynamic signature selection for BAC resolutions with dropdown selection from BAC users, manual name input option, and prefix/suffix fields (Dr., Ph.D., etc.) for all required signatories.

## Implementation Date
November 16, 2025

## Required Signatories (8 total)
1. BAC Chairman
2. BAC Vice Chairman
3. BAC Member (x3)
4. Head, BAC Secretariat
5. CEO

## Features Implemented

### 1. Database Structure
- **`bac_signatories` table**: Stores pre-configured BAC signatories
  - user_id (foreign key to users table)
  - position (enum: bac_chairman, bac_vice_chairman, bac_member, head_bac_secretariat, ceo)
  - prefix (optional: Dr., Atty., Engr., Prof., etc.)
  - suffix (optional: Ph.D., M.A., CPA, etc.)
  - is_active (boolean flag)
  
- **`resolution_signatories` table**: Stores selected signatories per resolution
  - purchase_request_id (foreign key)
  - position (enum for all 7 positions including 3 BAC members)
  - user_id (nullable - for dropdown selections)
  - name (nullable - for manual entries)
  - prefix (optional)
  - suffix (optional)

### 2. Models
- **`BacSignatory`**: Manages pre-configured signatories
  - Relationship with User model
  - Scopes for active status and position filtering
  - Accessor for formatted full name with prefix/suffix
  - Accessor for human-readable position names

- **`ResolutionSignatory`**: Manages per-resolution signatories
  - Relationships with PurchaseRequest and User models
  - Accessors for formatted names and display names
  - Support for both selected users and manual entries

### 3. Controllers

#### `BacSignatoryController`
- Full CRUD operations for managing BAC signatories
- index: List all signatories with pagination
- create: Form to add new signatory
- store: Save new signatory with validation
- edit: Form to edit existing signatory
- update: Update signatory with validation
- destroy: Remove signatory
- Access restricted to System Admin and BAC Chair roles

#### `BacProcurementMethodController` (Updated)
- edit(): Loads BAC signatories for form
- update(): 
  - Validates signatory data
  - Saves signatories to database
  - Prepares signatory data for resolution service
  - Passes signatories to resolution generation

#### `BacQuotationController` (Updated)
- manage(): Loads BAC signatories for regeneration modal
- regenerateResolution():
  - Accepts optional signatory data
  - Validates and saves updated signatories
  - Regenerates resolution with new signatures

### 4. Service Layer

#### `BacResolutionService` (Updated)
- Modified `generateResolution()` to accept optional signatory data parameter
- Added `loadSignatories()` method:
  - Accepts signatories from parameter
  - Falls back to database-stored signatories
  - Uses default values if none available
- Added `formatSignatoryName()` helper to format names with prefix/suffix
- Updated signature methods:
  - `addBACSignatures()`: Uses dynamic signatory data
  - `addSecretariatSignature()`: Uses dynamic signatory data
  - `addApproval()`: Uses dynamic CEO signatory data

### 5. Views

#### Management Views
- `resources/views/bac/signatories/index.blade.php`: List all signatories
  - Displays full name with titles
  - Shows position and active status
  - Edit and delete actions
  
- `resources/views/bac/signatories/create.blade.php`: Add new signatory
  - User dropdown (filtered to BAC-related roles)
  - Position selection
  - Prefix dropdown (Dr., Atty., Engr., Prof., Mr., Ms., Mrs.)
  - Suffix input field
  - Active status checkbox
  
- `resources/views/bac/signatories/edit.blade.php`: Edit existing signatory
  - Pre-filled form with current values
  - Same fields as create form

#### Signatory Selection Component
- `resources/views/bac/partials/signatory_form.blade.php`: Reusable form component
  - For each of 8 positions:
    - Radio buttons: "Select from list" or "Manual entry"
    - Dropdown with pre-configured signatories and all BAC users
    - Manual text input field
    - Prefix dropdown
    - Suffix input field
  - JavaScript to toggle between selection modes
  - Validation error display

#### Updated Views
- `resources/views/bac/procurement_method/edit.blade.php`:
  - Includes signatory form before procurement method submission
  - Users select all signatories during initial resolution generation
  
- `resources/views/bac/quotations/manage.blade.php`:
  - Replaced simple regenerate button with modal
  - Modal contains full signatory selection form
  - Pre-fills with existing signatories
  - Allows updating signatories during regeneration

### 6. Routes
```php
// BAC Signatory Management (Admin/BAC Chair only)
Route::middleware('role:System Admin|BAC Chair')->prefix('bac/signatories')->name('bac.signatories.')->group(function () {
    Route::get('/', [BacSignatoryController::class, 'index'])->name('index');
    Route::get('/create', [BacSignatoryController::class, 'create'])->name('create');
    Route::post('/', [BacSignatoryController::class, 'store'])->name('store');
    Route::get('/{signatory}/edit', [BacSignatoryController::class, 'edit'])->name('edit');
    Route::put('/{signatory}', [BacSignatoryController::class, 'update'])->name('update');
    Route::delete('/{signatory}', [BacSignatoryController::class, 'destroy'])->name('destroy');
});
```

## User Workflow

### 1. Managing BAC Signatories (Admin/BAC Chair)
1. Navigate to `/bac/signatories`
2. Click "Add New Signatory"
3. Select user from dropdown
4. Choose position
5. Optionally add prefix (Dr., Atty., etc.)
6. Optionally add suffix (Ph.D., M.A., etc.)
7. Set active status
8. Save signatory

### 2. Setting Procurement Method (First Time)
1. BAC member navigates to procurement method form
2. Fills in procurement method and remarks
3. Scrolls to signatory section
4. For each of 8 positions:
   - Chooses "Select from list" or "Manual entry"
   - If selecting: Picks from dropdown
   - If manual: Types full name
   - Adds prefix/suffix as needed
5. Submits form
6. Resolution auto-generates with selected signatories

### 3. Regenerating Resolution
1. BAC member opens quotation management page
2. Clicks "Regenerate" button
3. Modal opens with signatory form
4. Form pre-fills with existing signatories
5. User can update any signatory information
6. Submits to regenerate resolution
7. New resolution document generated with updated signatures

## Technical Details

### Validation Rules
- Procurement method and signatories required on first generation
- Signatories optional on regeneration (uses existing if not provided)
- Input mode (select/manual) required for each position
- User ID required if "select" mode
- Name required if "manual" mode
- Prefix and suffix optional, max 50 characters

### Database Relationships
```
PurchaseRequest
  └─> hasMany ResolutionSignatory
        ├─> belongsTo User (nullable)
        └─> belongsTo PurchaseRequest

BacSignatory
  └─> belongsTo User

User
  ├─> hasMany BacSignatory
  └─> hasMany ResolutionSignatory
```

### Data Flow
1. User submits form with signatory selections
2. Controller validates input
3. Controller saves to `resolution_signatories` table
4. Controller prepares signatory array for service
5. Service receives array and formats names
6. Service generates resolution with formatted signatures
7. Resolution saved to storage
8. Document record created in database

## Benefits

### For Administrators
- Centralized management of BAC signatories
- Pre-configure common signatories for quick selection
- Track active/inactive signatories
- Maintain consistent formatting with prefixes/suffixes

### For BAC Users
- Quick selection from dropdown
- Flexibility to manually enter names when needed
- Easy to update signatories when regenerating
- Automatic formatting of names with titles

### For the System
- Audit trail of signatories used in each resolution
- Historical record of who signed what
- Flexibility to accommodate personnel changes
- Support for both active members and manual entries

## Migration Commands

```bash
# Migrations already run successfully
php artisan migrate

# Created tables:
# - bac_signatories
# - resolution_signatories
```

## Files Created/Modified

### New Files (16)
1. `database/migrations/2025_11_16_125003_create_bac_signatories_table.php`
2. `database/migrations/2025_11_16_125022_create_resolution_signatories_table.php`
3. `app/Models/BacSignatory.php`
4. `app/Models/ResolutionSignatory.php`
5. `app/Http/Controllers/BacSignatoryController.php`
6. `resources/views/bac/signatories/index.blade.php`
7. `resources/views/bac/signatories/create.blade.php`
8. `resources/views/bac/signatories/edit.blade.php`
9. `resources/views/bac/partials/signatory_form.blade.php`

### Modified Files (6)
1. `app/Services/BacResolutionService.php`
   - Added signatory parameter to generateResolution()
   - Added loadSignatories() method
   - Added formatSignatoryName() helper
   - Updated all signature methods

2. `app/Http/Controllers/BacProcurementMethodController.php`
   - Updated edit() to pass BAC signatories
   - Updated update() to validate and save signatories
   - Added saveSignatories() helper
   - Added prepareSignatoryData() helper

3. `app/Http/Controllers/BacQuotationController.php`
   - Updated manage() to load signatories
   - Updated regenerateResolution() to accept signatories
   - Added saveSignatories() helper
   - Added prepareSignatoryData() helper

4. `app/Models/PurchaseRequest.php`
   - Added resolutionSignatories() relationship

5. `resources/views/bac/procurement_method/edit.blade.php`
   - Added signatory form inclusion

6. `resources/views/bac/quotations/manage.blade.php`
   - Replaced regenerate button with modal trigger
   - Added regeneration modal with signatory form

7. `routes/web.php`
   - Added BAC signatory management routes

## Testing Checklist

- [x] Migrations run successfully
- [x] Routes registered correctly
- [x] No linter errors
- [ ] Test BAC signatory CRUD operations
- [ ] Test procurement method form with signatories
- [ ] Test resolution generation with custom signatories
- [ ] Test resolution regeneration with updated signatories
- [ ] Verify generated resolution contains correct signatures
- [ ] Test prefix/suffix formatting in generated document
- [ ] Test both dropdown selection and manual entry modes
- [ ] Verify database stores signatories correctly
- [ ] Test role-based access control for signatory management

## Notes

- All signatories have both dropdown and manual entry options for maximum flexibility
- Prefix/suffix fields are optional but recommended for proper title formatting
- The system maintains backward compatibility - resolutions can still be generated with default signatories if none are specified
- Active/inactive flag allows administrators to temporarily disable signatories without deleting them
- The implementation follows Laravel best practices with proper validation, relationships, and separation of concerns

