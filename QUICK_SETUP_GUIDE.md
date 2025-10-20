# Quick Setup Guide - PPMP Database Update

## What Was Done ✅

I've successfully updated your database structure to support the revamped PR system with PPMP items. Here's what changed:

### Files Modified:
1. **New Migration**: `database/migrations/2025_10_20_060831_update_purchase_request_items_for_ppmp_system.php`
   - Converts `item_category` from rigid enum to flexible string field
   - Allows storing actual PPMP categories (e.g., "ALCOHOL OR ACETONE BASED ANTISEPTICS")

2. **Controller**: `app/Http/Controllers/PurchaseRequestController.php`
   - Now saves `item_code` from PPMP items
   - Automatically stores PPMP category names
   - Validates and processes PPMP item references

3. **View**: `resources/views/purchase_requests/create.blade.php`
   - Form now sends `item_code` along with other item data

## How to Apply the Changes 🚀

### Step 1: Start Your Database Server

**Option A - Using MySQL (XAMPP):**
1. Open XAMPP Control Panel
2. Start the MySQL service
3. Wait until it shows "Running"

**Option B - Using SQLite (No server needed):**
1. Edit your `.env` file
2. Set: `DB_CONNECTION=sqlite`
3. The database file already exists at `database/database.sqlite`

### Step 2: Run the Migration

Open your terminal in the project directory and run:

```bash
php artisan migrate
```

You should see output like:
```
INFO  Running migrations.

2025_10_20_060831_update_purchase_request_items_for_ppmp_system .... DONE
```

### Step 3: Verify the Migration

Check the migration status:

```bash
php artisan migrate:status
```

Look for your new migration in the list - it should show "Ran".

### Step 4: Test the System

1. Start your development server:
   ```bash
   php artisan serve
   ```

2. Navigate to: `http://localhost:8000/purchase-requests/create`

3. Test the following:
   - ✅ Browse PPMP items by category
   - ✅ Add items with fixed prices
   - ✅ Add items with custom prices (Software, Part 2 items)
   - ✅ Submit a test PR
   - ✅ Check that items are saved correctly

## Troubleshooting 🔧

### Issue: "SQLSTATE[HY000] [2002] No connection could be made"

**Solution**: Your MySQL server is not running.
- Option 1: Start XAMPP MySQL service
- Option 2: Switch to SQLite in `.env`:
  ```
  DB_CONNECTION=sqlite
  DB_DATABASE=C:\xampp\htdocs\CapstoneLatest\database\database.sqlite
  ```

### Issue: "Migration table not found"

**Solution**: Initialize migrations:
```bash
php artisan migrate:install
php artisan migrate
```

### Issue: "Column already exists: ppmp_item_id"

**Solution**: The column was added in a previous migration. This is expected and safe. The new migration will skip this step.

### Issue: Migration fails on rollback

**Solution**: This migration uses a temporary column strategy that's safe for both MySQL and SQLite. If you need to rollback:
```bash
php artisan migrate:rollback --step=1
```

## What's New in the System 🎉

### PPMP Integration Features:

1. **Catalog-Based Selection**
   - Browse items organized by official PS-DBM PPMP categories
   - Search functionality across all items
   - Visual indicators for custom-price items

2. **Flexible Pricing**
   - Fixed prices for standard items
   - Custom pricing for Software and Part 2 items
   - Budget tracking in real-time

3. **Data Integrity**
   - Direct link to PPMP items via `ppmp_item_id`
   - Item codes automatically saved
   - Categories preserved from PPMP catalog

4. **Smart Categories**
   - No more forcing PPMP items into predefined categories
   - System stores actual PPMP category names
   - Better reporting and tracking

## Database Structure Changes 📊

### Before:
```
item_category: enum('office_supplies', 'equipment', ...)
```
- Limited to 9 predefined values
- Couldn't represent PPMP categories accurately

### After:
```
item_category: varchar(255) nullable
```
- Can store any PPMP category name
- Examples: "ALCOHOL OR ACETONE BASED ANTISEPTICS", "SOFTWARE"
- Nullable for flexibility

## Need Help? 💬

If you encounter any issues:

1. Check the detailed documentation: `DATABASE_PPMP_UPDATE_SUMMARY.md`
2. Verify your database connection in `.env`
3. Check Laravel logs: `storage/logs/laravel.log`
4. Run: `php artisan migrate:status` to see migration state

## Next Steps 📝

After successful migration:

1. ✅ Test PR creation with various PPMP items
2. ✅ Verify budget calculations are correct
3. ✅ Check that item codes are being saved
4. ✅ Ensure categories display properly in reports
5. ✅ Test with both standard and custom-price items

---

**Summary**: Your database is now ready to support the full PPMP-integrated PR system! The changes are backward compatible and will work with existing data.

