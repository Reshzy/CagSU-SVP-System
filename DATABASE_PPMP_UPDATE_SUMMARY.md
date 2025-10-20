# Database Update for PPMP-Integrated PR System

## Overview
This document summarizes the database changes made to accommodate the revamped Purchase Request (PR) system with PPMP (Project Procurement Management Plan) items integration.

## Changes Made

### 1. New Migration File
**File:** `database/migrations/2025_10_20_060831_update_purchase_request_items_for_ppmp_system.php`

This migration updates the `purchase_request_items` table to support the PPMP integration:

#### Key Changes:
- **Modified `item_category` column**: Changed from a strict ENUM with predefined values to a flexible `VARCHAR` field that can store PPMP category names directly
- This allows storing the actual PPMP category (e.g., "ALCOHOL OR ACETONE BASED ANTISEPTICS", "SOFTWARE", etc.) instead of forcing it into predefined categories
- Made the column nullable to support items without category information

#### Why This Change Was Necessary:
The original system used a rigid enum with values like 'office_supplies', 'equipment', 'materials', etc. However, the PPMP system has its own rich categorization (e.g., "ALCOHOL OR ACETONE BASED ANTISEPTICS", "COMMUNICATION EQUIPMENT", "SEMICONDUCTOR DEVICES AND MATERIALS", etc.). This migration makes the system flexible enough to store these PPMP categories directly.

### 2. Controller Updates
**File:** `app/Http/Controllers/PurchaseRequestController.php`

#### Changes:
1. **Added validation for `item_code`** (line 73):
   ```php
   'items.*.item_code' => ['nullable', 'string', 'max:100'],
   ```

2. **Updated item creation logic** (lines 127-150):
   - Now retrieves and stores the PPMP category from the referenced PPMP item
   - Saves the `item_code` from PPMP items
   - Example:
     ```php
     if (!empty($itemData['ppmp_item_id'])) {
         $ppmpItem = PpmpItem::find($itemData['ppmp_item_id']);
         if ($ppmpItem) {
             $itemCategory = $ppmpItem->category;
         }
     }
     ```

### 3. Form Updates
**File:** `resources/views/purchase_requests/create.blade.php`

#### Changes:
- **Added `item_code` to form submission** (line 614):
  ```html
  <input type="hidden" name="items[${item.id}][item_code]" value="${escapeHtml(item.code || '')}">
  ```
- This ensures that PPMP item codes (e.g., "12191601-AL-E04") are properly stored with each purchase request item

## Database Schema After Migration

### `ppmp_items` table (already exists)
- `id` - Primary key
- `category` - Category name (e.g., "ALCOHOL OR ACETONE BASED ANTISEPTICS")
- `item_code` - Unique item code (e.g., "12191601-AL-E04")
- `item_name` - Item description
- `unit_of_measure` - Unit (e.g., "bottle", "piece")
- `unit_price` - Price per unit
- `specifications` - Detailed specifications
- `is_active` - Boolean flag
- `timestamps`

### `purchase_request_items` table (updated)
- `id` - Primary key
- `purchase_request_id` - Foreign key to purchase_requests
- **`ppmp_item_id`** - Foreign key to ppmp_items (nullable, for PPMP-based items)
- **`item_code`** - Item code from PPMP or custom (nullable)
- `item_name` - Item name (from PPMP or custom)
- `detailed_specifications` - Specifications
- `unit_of_measure` - Unit
- `quantity_requested` - Quantity
- `estimated_unit_cost` - Unit cost (can be customized for certain categories)
- `estimated_total_cost` - Total cost
- **`item_category`** - Now a flexible string field storing PPMP category (nullable)
- ... other fields remain unchanged

## How the System Works

### 1. PPMP Item Selection
- Users browse PPMP items organized by category in the left panel
- Items display their code, name, unit, and price
- Certain categories (Software, Part 2, Other Items) allow price customization

### 2. Adding Items to PR
- When a user clicks "Add to PR", the system:
  1. Stores the `ppmp_item_id` as a reference
  2. Copies the item's code, name, specifications, and unit
  3. Uses the PPMP price or allows custom pricing for eligible categories
  4. Saves the PPMP category name directly

### 3. Data Relationships
```
PurchaseRequest
  └─> PurchaseRequestItem
        ├─> ppmp_item_id (references PpmpItem)
        ├─> item_code (copied from PPMP item)
        ├─> item_name (copied from PPMP item)
        ├─> item_category (PPMP category name)
        └─> estimated_unit_cost (from PPMP or custom)
```

## Running the Migration

To apply these database changes:

### Option 1: Using MySQL (if XAMPP MySQL is running)
```bash
php artisan migrate
```

### Option 2: Using SQLite (if MySQL is not available)
1. Make sure your `.env` file has:
   ```
   DB_CONNECTION=sqlite
   DB_DATABASE=C:\xampp\htdocs\CapstoneLatest\database\database.sqlite
   ```
2. Run:
   ```bash
   php artisan migrate
   ```

### Checking Migration Status
```bash
php artisan migrate:status
```

## Testing the Changes

After running the migration:

1. **Start the application:**
   ```bash
   php artisan serve
   ```

2. **Test PR creation:**
   - Navigate to the Purchase Request creation page
   - Select items from the PPMP catalog
   - Try both fixed-price and custom-price items
   - Submit the PR

3. **Verify data storage:**
   - Check that `ppmp_item_id` is properly linked
   - Verify `item_code` is stored
   - Confirm `item_category` contains the PPMP category name

## Backward Compatibility

- Existing purchase request items without PPMP links will continue to work
- The `item_category` field is nullable, so existing data remains valid
- The system supports both PPMP-based items and custom items

## Files Modified

1. ✅ `database/migrations/2025_10_20_060831_update_purchase_request_items_for_ppmp_system.php` (NEW)
2. ✅ `app/Http/Controllers/PurchaseRequestController.php` (UPDATED)
3. ✅ `resources/views/purchase_requests/create.blade.php` (UPDATED)

## Next Steps

1. **Run the migration** to update your database schema
2. **Test the PR creation flow** with PPMP items
3. **Verify data integrity** by checking the database after creating test PRs
4. **Update any reports or views** that display `item_category` to handle the new flexible format

## Notes

- The migration uses a temporary column approach to safely convert the enum to a varchar, which works with SQLite
- All changes maintain backward compatibility with existing data
- The system now supports the full richness of PPMP categories without forcing them into predefined buckets

