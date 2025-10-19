# Quick Migration Reference

## Essential Commands

### 1. Database Export (Choose One)

```bash
# phpMyAdmin: Export → Custom → SQL format → Save as cagsu_svp_system_backup.sql

# Command Line (with password)
mysqldump -u root -p --routines --triggers --single-transaction cagsu_svp_system > cagsu_svp_system_backup.sql

# Command Line (no password - default XAMPP)
mysqldump -u root --routines --triggers --single-transaction cagsu_svp_system > cagsu_svp_system_backup.sql
```

### 2. Create Archive (Windows - 7-Zip)

```bash
cd C:\xampp\htdocs\
7z a -t7z CapstoneLatest_Migration.7z CapstoneLatest\ -x@CapstoneLatest\exclude_list.txt -mx=9
```

### 3. Target System Setup

```bash
# Extract files to web directory
# Navigate to project directory

# Install dependencies
composer install --no-dev --optimize-autoloader
npm install
npm run build

# Setup environment
cp .env.example .env
# Edit .env with your database settings
php artisan key:generate

# Import database
mysql -u username -p cagsu_svp_system < cagsu_svp_system_backup.sql

# Final setup
php artisan storage:link
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

### 4. File Permissions (Linux/macOS)

```bash
sudo chown -R www-data:www-data /path/to/project
sudo chmod -R 775 storage bootstrap/cache
```

## Files to Transfer

-   ✅ `CapstoneLatest_Migration.7z` (or .zip/.tar.gz)
-   ✅ `cagsu_svp_system_backup.sql`

## Key Exclusions

-   ❌ All .sql, .html, .zip files
-   ❌ node_modules/, vendor/
-   ❌ .env file (recreate on target)
-   ❌ storage/logs/, cache directories
-   ✅ **Keep all .png files** (as requested)

## Critical .env Settings

```env
APP_URL=http://your-domain.com
DB_DATABASE=cagsu_svp_system
DB_USERNAME=your_username
DB_PASSWORD=your_password
```
