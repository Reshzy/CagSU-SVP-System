# CAGSU SVP System - Project Transfer Guide

## Table of Contents
1. [System Requirements](#system-requirements)
2. [Prerequisites Installation](#prerequisites-installation)
3. [Project Setup](#project-setup)
4. [Database Configuration](#database-configuration)
5. [Environment Configuration](#environment-configuration)
6. [Dependencies Installation](#dependencies-installation)
7. [BAC Resolution System Setup](#bac-resolution-system-setup)
8. [Storage & Permissions](#storage--permissions)
9. [Running the Application](#running-the-application)
10. [Login Credentials](#login-credentials)
11. [Troubleshooting](#troubleshooting)
12. [Quick Command Reference](#quick-command-reference)

---

## System Requirements

### Minimum Requirements
- **OS:** Windows 10/11, macOS 10.15+, or Linux (Ubuntu 20.04+)
- **PHP:** 8.2 or higher
- **MySQL:** 5.7+ or MariaDB 10.3+
- **Node.js:** 18.x or higher
- **Composer:** 2.x
- **Disk Space:** 500MB minimum
- **RAM:** 4GB minimum (8GB recommended)

### Check Your System
```bash
# Check PHP version
php -v

# Check Composer version
composer --version

# Check Node.js version
node -v

# Check npm version
npm -v
```

---

## Prerequisites Installation

### 1. Install XAMPP (Windows)
1. Download from [https://www.apachefriends.org/](https://www.apachefriends.org/)
2. Install to `C:\xampp` (default location)
3. Ensure PHP 8.2+ is included

### 2. Install Composer
1. Download from [https://getcomposer.org/download/](https://getcomposer.org/download/)
2. Run installer and follow prompts
3. Verify: `composer --version`

### 3. Install Node.js & npm
1. Download from [https://nodejs.org/](https://nodejs.org/) (LTS version)
2. Run installer
3. Verify: `node -v` and `npm -v`

---

## Project Setup

### Step 1: Extract Project Files

**For Windows (XAMPP):**
```
C:\xampp\htdocs\CapstoneLatest\
```

**For macOS/Linux:**
```
/var/www/html/CapstoneLatest/
```

### Step 2: Open Terminal/Command Prompt
Navigate to project directory:
```bash
cd C:\xampp\htdocs\CapstoneLatest
```

---

## Database Configuration

### Option A: Import SQL Backup (Recommended)

1. **Start XAMPP Services**
   - Open XAMPP Control Panel
   - Start Apache and MySQL

2. **Open phpMyAdmin**
   - Go to `http://localhost/phpmyadmin`

3. **Create Database**
   - Click "New" in the left sidebar
   - Database name: `cagsu_svp_system`
   - Collation: `utf8mb4_unicode_ci`
   - Click "Create"

4. **Import SQL File**
   - Click on `cagsu_svp_system` database
   - Click "Import" tab
   - Choose file: `cagsu_svp_system_backup.sql`
   - Click "Go"
   - Wait for success message

### Option B: Run Migrations (Alternative)

```bash
# Run migrations
php artisan migrate

# Seed database with initial data
php artisan db:seed
```

---

## Environment Configuration

### Step 1: Configure .env File

The `.env` file should already exist. Verify these settings:

```env
APP_NAME="CAGSU SVP System"
APP_ENV=local
APP_DEBUG=true
APP_URL=http://localhost/CapstoneLatest/public

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=cagsu_svp_system
DB_USERNAME=root
DB_PASSWORD=

SESSION_DRIVER=database
QUEUE_CONNECTION=database
```

### Step 2: Generate Application Key

```bash
php artisan key:generate
```

### Step 3: Clear Configuration Cache

```bash
php artisan config:clear
php artisan cache:clear
php artisan view:clear
```

---

## Dependencies Installation

### Step 1: Install PHP Dependencies

```bash
composer install
```

**Common Issues:**
- If you get memory errors, run: `composer install --no-scripts`
- If extensions are missing, enable them in `php.ini`

### Step 2: Install Node Dependencies

```bash
npm install
```

### Step 3: Build Frontend Assets

```bash
# For development
npm run dev

# For production
npm run build
```

---

## BAC Resolution System Setup

The system includes an **automatic BAC Resolution document generator** using PHPWord.

### Features
- Auto-generates resolution documents when Budget Office earmarks PRs
- Resolution number format: `SV-YYYY-MM-####` (e.g., SV-2025-11-0001)
- Earmark ID format: `EM01-MMDDYY-####` (e.g., EM01-111425-0001)
- Professional 2-page Word documents with signatures
- Download and regenerate capabilities

### Installation Verification

PHPWord packages are already in `composer.json`. Verify installation:

```bash
composer show phpoffice/phpword
composer show kwn/number-to-words
```

Both should show version information.

### Header & Footer Images Setup

**IMPORTANT:** For resolution documents to include header/footer:

1. **Create images directory** (if not exists):
   ```bash
   mkdir public/images
   ```

2. **Place image files:**
   - `public/images/header.png` - Official header with logo/letterhead
   - `public/images/footer.png` - Official footer

3. **Image Specifications:**
   - Format: PNG (recommended) or JPG
   - Header size: 6.5" × 1" (recommended 1950 × 300 pixels)
   - Footer size: 6.5" × 1" (recommended 1950 × 300 pixels)
   - Resolution: 300 DPI minimum

4. **Test Resolution Generation:**
   - Login as Budget Officer
   - Earmark a purchase request
   - Check `storage/app/resolutions/` for generated DOCX file

**Note:** If images are missing, resolutions will still generate but without header/footer.

### Resolution Storage

Generated documents are stored in:
```
storage/app/resolutions/
  ├── SV-2025-11-0001.docx
  ├── SV-2025-11-0002.docx
  └── ...
```

---

## Storage & Permissions

### Step 1: Create Storage Link

```bash
php artisan storage:link
```

This creates a symbolic link from `public/storage` to `storage/app/public`.

### Step 2: Set Permissions (Linux/macOS)

```bash
chmod -R 775 storage
chmod -R 775 bootstrap/cache
```

### Step 3: Ensure Directories Exist

```bash
# Create resolutions directory
mkdir -p storage/app/resolutions

# Create temp directory for document processing
mkdir -p storage/app/temp

# Set permissions
chmod -R 775 storage/app
```

---

## Running the Application

### Option 1: Laravel Development Server (Recommended)

```bash
php artisan serve
```

Access at: `http://localhost:8000`

### Option 2: XAMPP Apache Server

1. Ensure Apache is running in XAMPP
2. Access at: `http://localhost/CapstoneLatest/public`

### Option 3: Run with Queue Worker

For real-time notifications and background jobs:

```bash
# Terminal 1: Start server
php artisan serve

# Terminal 2: Start queue worker
php artisan queue:work --tries=3
```

---

## Login Credentials

### Default User Accounts

| Role | Email | Password | Access Level |
|------|-------|----------|--------------|
| CEO | ceo@cagsu.edu.ph | password | Final approval, executive dashboard |
| Budget Officer | budget@cagsu.edu.ph | password | Budget earmarking, fund allocation |
| BAC Secretariat | bac@cagsu.edu.ph | password | Quotation management, resolutions |
| Supply Officer | supply@cagsu.edu.ph | password | Inventory, purchase orders |
| Department Head | depthead@cagsu.edu.ph | password | PR approval, department oversight |
| End User | user@cagsu.edu.ph | password | Create PRs, track requests |

**Security Note:** Change all passwords immediately after deployment!

---

## Troubleshooting

### Database Connection Errors

**Problem:** `SQLSTATE[HY000] [2002] Connection refused`

**Solution:**
1. Verify MySQL is running in XAMPP
2. Check database credentials in `.env`
3. Ensure database `cagsu_svp_system` exists

### Composer Memory Limit Error

**Problem:** `PHP Fatal error: Allowed memory size exhausted`

**Solution:**
```bash
php -d memory_limit=-1 /path/to/composer install
```

### Permission Denied Errors

**Problem:** `The stream or file "storage/logs/laravel.log" could not be opened`

**Solution:**
```bash
# Windows (Run as Administrator)
icacls storage /grant Users:F /T
icacls bootstrap/cache /grant Users:F /T

# Linux/macOS
sudo chmod -R 775 storage bootstrap/cache
sudo chown -R www-data:www-data storage bootstrap/cache
```

### Missing PHP Extensions

**Problem:** `extension not found: pdo_mysql, gd, zip, etc.`

**Solution:**
1. Open `php.ini` in XAMPP (or system PHP)
2. Uncomment these lines (remove `;`):
   ```ini
   extension=pdo_mysql
   extension=gd
   extension=zip
   extension=fileinfo
   extension=mbstring
   ```
3. Restart Apache/PHP-FPM

### Assets Not Loading (404 errors)

**Problem:** CSS/JS files return 404

**Solution:**
```bash
npm run build
php artisan view:clear
```

### Resolution Generation Fails

**Problem:** BAC resolution not generating

**Solution:**
1. Check PHPWord is installed: `composer show phpoffice/phpword`
2. Verify storage permissions: `chmod -R 775 storage/app`
3. Check logs: `storage/logs/laravel.log`
4. Ensure temp directory exists: `mkdir -p storage/app/temp`

### Header/Footer Images Not Showing

**Problem:** Generated resolutions missing header/footer

**Solution:**
1. Verify images exist: `public/images/header.png` and `footer.png`
2. Check file permissions: `chmod 644 public/images/*.png`
3. Regenerate resolution from BAC interface

---

## Quick Command Reference

### Daily Development

```bash
# Start development server
php artisan serve

# Watch and rebuild assets
npm run dev

# Clear all caches
php artisan optimize:clear
```

### Database Management

```bash
# Run migrations
php artisan migrate

# Rollback last migration
php artisan migrate:rollback

# Refresh database with seed data
php artisan migrate:fresh --seed

# Check migration status
php artisan migrate:status
```

### Cache Management

```bash
# Clear all caches
php artisan cache:clear
php artisan config:clear
php artisan view:clear
php artisan route:clear

# Cache for production
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

### Queue & Jobs

```bash
# Start queue worker
php artisan queue:work

# Process jobs once and exit
php artisan queue:work --once

# Restart queue workers
php artisan queue:restart
```

### System Information

```bash
# Show Laravel version and environment
php artisan about

# List all routes
php artisan route:list

# List all Artisan commands
php artisan list
```

### Maintenance Mode

```bash
# Enable maintenance mode
php artisan down

# Disable maintenance mode
php artisan up
```

---

## Workflow Overview

### Purchase Request Process

1. **User Creates PR** (status: `ceo_approval`)
   - Selects items from PPMP catalog
   - Submits for approval

2. **CEO Reviews** (status: `budget_office_review` if approved)
   - Approves or rejects PR
   - Forwards to Budget Office

3. **Budget Office Earmarks** (status: `bac_evaluation`)
   - **Generates earmark ID** (e.g., EM01-111425-0001)
   - **Auto-generates BAC Resolution** (e.g., SV-2025-11-0001.docx)
   - Sets funding source and budget code
   - Forwards to BAC

4. **BAC Evaluation** (status: `bac_approved` when complete)
   - Views/downloads resolution document
   - Manages supplier quotations
   - Recommends winning bid
   - Can regenerate resolution if needed

5. **Purchase Order** (status: `po_approved`)
   - Supply office generates PO
   - Supplier fulfills order

6. **Completion** (status: `completed`)
   - Items received and distributed

---

## Additional Resources

- **System Architecture:** See `SYSTEM_ARCHITECTURE.md`
- **Workflow Details:** See `WORKFLOW_REROUTE_SUMMARY.md`
- **Presentation Guide:** See `README_FOR_TEACHER.md`
- **Laravel Documentation:** [https://laravel.com/docs](https://laravel.com/docs)
- **PHPWord Documentation:** [https://phpword.readthedocs.io](https://phpword.readthedocs.io)

---

## Support & Maintenance

### Log Files

Check logs for errors:
```bash
tail -f storage/logs/laravel.log
```

### Database Backup

Regular backups are crucial:
```bash
# Export database
mysqldump -u root cagsu_svp_system > backup_$(date +%Y%m%d).sql

# Import database
mysql -u root cagsu_svp_system < backup_20250115.sql
```

### System Health Check

```bash
# Check system status
php artisan about

# Test database connection
php artisan db:show

# Verify queue is working
php artisan queue:monitor
```

---

## Security Checklist

Before going live:

- [ ] Change all default passwords
- [ ] Set `APP_ENV=production` in `.env`
- [ ] Set `APP_DEBUG=false` in `.env`
- [ ] Update `APP_URL` to production domain
- [ ] Configure proper SSL certificate
- [ ] Set up regular database backups
- [ ] Configure email settings for notifications
- [ ] Review user permissions and roles
- [ ] Enable Laravel's maintenance mode during updates

---

## Project Structure

```
CapstoneLatest/
├── app/
│   ├── Http/Controllers/       # Request handlers
│   ├── Models/                 # Database models
│   ├── Services/              # Business logic
│   │   └── BacResolutionService.php  # Resolution generator
│   └── Notifications/         # Email/system notifications
├── database/
│   ├── migrations/            # Database schema
│   └── seeders/              # Initial data
├── public/
│   ├── images/               # Header/footer images (IMPORTANT!)
│   └── index.php             # Application entry point
├── resources/
│   ├── views/                # Blade templates
│   └── js/                   # Frontend JavaScript
├── routes/
│   └── web.php              # Application routes
├── storage/
│   ├── app/
│   │   ├── resolutions/     # Generated BAC resolutions
│   │   └── temp/            # Temporary files
│   └── logs/                # Application logs
└── .env                     # Environment configuration
```

---

**Successfully transferred?** The system should now be fully operational. Test by logging in as different user roles and creating a test purchase request through the complete workflow including BAC resolution generation.

**Questions or issues?** Check the troubleshooting section or review Laravel logs at `storage/logs/laravel.log`.

