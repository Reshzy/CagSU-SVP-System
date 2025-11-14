# CAGSU Small Value Procurement System

A comprehensive Purchase Request and Procurement Management System built for Cagayan State University - Sanchez Mira Campus.

[![Laravel](https://img.shields.io/badge/Laravel-12.x-red.svg)](https://laravel.com)
[![PHP](https://img.shields.io/badge/PHP-8.2%2B-blue.svg)](https://php.net)
[![License](https://img.shields.io/badge/License-MIT-green.svg)](LICENSE)

---

## Overview

The CAGSU SVP System is a complete end-to-end procurement management solution designed to streamline the purchase request workflow from creation to completion. Built with Laravel 12.x, the system implements best practices in government procurement while maintaining compliance with Philippine procurement laws.

### Key Features

- **📋 Purchase Request Management** - PPMP-integrated request creation and tracking
- **✅ Multi-Level Approval Workflow** - CEO → Budget Office → BAC evaluation → Approval
- **📄 Automatic BAC Resolution Generation** - PHPWord-powered document creation
- **💰 Budget Tracking & Earmarking** - Real-time budget monitoring and allocation
- **🏢 Supplier Management** - Quotation comparison and supplier evaluation
- **📊 Document Management** - Secure storage with version control
- **🔐 Role-Based Access Control** - 8 distinct user roles with granular permissions
- **📧 Automated Notifications** - Status updates and action reminders

---

## Technology Stack

### Backend
- **Framework:** Laravel 12.x
- **Language:** PHP 8.2+
- **Database:** MySQL 5.7+ / MariaDB 10.3+
- **Document Generation:** PHPWord 1.4
- **Number Conversion:** NumberToWords 2.12

### Frontend
- **Templating:** Blade
- **CSS Framework:** Tailwind CSS 3.x
- **JavaScript:** Vanilla JS + Alpine.js
- **Build Tool:** Vite

### Development Tools
- Composer 2.x
- Node.js 18.x+
- XAMPP (Apache + MySQL)

---

## Quick Start

### Prerequisites
- PHP 8.2 or higher
- Composer 2.x
- Node.js 18.x+
- MySQL 5.7+ or MariaDB 10.3+

### Installation

1. **Clone or extract the project**
   ```bash
   cd C:\xampp\htdocs\CapstoneLatest
   ```

2. **Install dependencies**
   ```bash
   composer install
   npm install
   ```

3. **Configure environment**
   ```bash
   cp .env.example .env
   php artisan key:generate
   ```

4. **Setup database**
   - Create database: `cagsu_svp_system`
   - Import SQL: `cagsu_svp_system_backup.sql`
   - Or run migrations: `php artisan migrate --seed`

5. **Build assets**
   ```bash
   npm run build
   ```

6. **Start server**
   ```bash
   php artisan serve
   ```

7. **Access application**
   - URL: `http://localhost:8000`
   - Default login: `ceo@cagsu.edu.ph` / `password`

**For detailed installation instructions, see [PROJECT_TRANSFER_GUIDE.md](PROJECT_TRANSFER_GUIDE.md)**

---

## System Workflow

```
┌─────────────────────────────────────────────────────────────────┐
│                    PURCHASE REQUEST WORKFLOW                    │
└─────────────────────────────────────────────────────────────────┘

1. User Creates PR
   └─> Selects items from PPMP catalog
   └─> Status: ceo_approval

2. CEO Approval
   └─> Reviews and approves PR
   └─> Status: budget_office_review

3. Budget Office Earmarking ⚡ NEW FEATURE
   └─> Generates Earmark ID (EM01-MMDDYY-####)
   └─> Auto-creates BAC Resolution (SV-YYYY-MM-####.docx)
   └─> Sets funding source and budget code
   └─> Status: bac_evaluation

4. BAC Evaluation
   └─> Downloads/regenerates resolution document
   └─> Collects supplier quotations
   └─> Evaluates and recommends winner
   └─> Status: bac_approved

5. Purchase Order Generation
   └─> Supply office creates PO
   └─> Status: po_approved

6. Delivery & Completion
   └─> Goods received and inspected
   └─> Status: completed
```

---

## BAC Resolution System (New)

### Automatic Document Generation

The system now features **automatic BAC Resolution document generation** using PHPWord:

- **Auto-generation:** When Budget Office earmarks funds, resolution is instantly created
- **Format:** Professional 2-page Word documents with official formatting
- **Resolution Numbers:** Auto-generated in `SV-YYYY-MM-####` format
- **Earmark IDs:** Auto-generated in `EM01-MMDDYY-####` format
- **Features:**
  - Download resolution as DOCX
  - Regenerate with updated data
  - Includes header/footer images (letterhead)
  - Proper signatures and certifications
  - Compliant with procurement regulations

### Setup Requirements

Place official header and footer images:
```
public/images/
  ├── header.png  (6.5" × 1", 300 DPI)
  └── footer.png  (6.5" × 1", 300 DPI)
```

Generated documents stored in: `storage/app/resolutions/`

---

## User Roles & Permissions

| Role | Primary Responsibilities |
|------|-------------------------|
| **CEO** | Final approval authority, executive oversight |
| **Budget Officer** | Fund earmarking, budget allocation, resolution generation |
| **BAC Secretariat** | Quotation management, resolution handling, evaluation |
| **BAC Chair/Members** | Bid evaluation, award recommendations |
| **Supply Officer** | Inventory, purchase orders, delivery tracking |
| **Department Head** | Department oversight, PR approval |
| **Accounting** | Financial records, disbursement vouchers |
| **End User** | PR creation, request tracking |

---

## System Architecture

### MVC Pattern
```
┌─────────────┐
│   Routes    │ ← web.php defines URL endpoints
└──────┬──────┘
       │
┌──────▼──────┐
│ Controllers │ ← Handle HTTP requests
└──────┬──────┘
       │
┌──────▼──────┐
│  Services   │ ← Business logic (BacResolutionService, etc.)
└──────┬──────┘
       │
┌──────▼──────┐
│   Models    │ ← Database interactions (Eloquent ORM)
└──────┬──────┘
       │
┌──────▼──────┐
│   Views     │ ← Blade templates (HTML)
└─────────────┘
```

### Key Services
- **BacResolutionService** - Generates BAC resolution documents
- **WorkflowRouter** - Manages approval workflow transitions
- **BudgetService** - Handles budget calculations and tracking

---

## Database Schema

### Core Tables
- `users` - User accounts and authentication
- `departments` - Organizational structure
- `purchase_requests` - Main PR records with earmark_id and resolution_number
- `purchase_request_items` - PR line items linked to PPMP
- `ppmp_items` - Pre-approved procurement items catalog
- `workflow_approvals` - Approval tracking by step
- `documents` - File storage with polymorphic relations
- `suppliers` - Vendor information
- `quotations` - Supplier price quotes
- `purchase_orders` - Generated POs
- `disbursement_vouchers` - Payment records

For detailed schema, see `SYSTEM_ARCHITECTURE.md`

---

## Documentation

- **[PROJECT_TRANSFER_GUIDE.md](PROJECT_TRANSFER_GUIDE.md)** - Complete setup and transfer instructions
- **[SYSTEM_ARCHITECTURE.md](SYSTEM_ARCHITECTURE.md)** - Technical architecture details
- **[WORKFLOW_REROUTE_SUMMARY.md](WORKFLOW_REROUTE_SUMMARY.md)** - Workflow documentation
- **[README_FOR_TEACHER.md](README_FOR_TEACHER.md)** - Presentation guide

---

## Development

### Running Development Server
```bash
# Start Laravel server
php artisan serve

# Watch for asset changes
npm run dev

# Run queue worker (for notifications)
php artisan queue:work
```

### Database Migrations
```bash
# Run all pending migrations
php artisan migrate

# Rollback last migration
php artisan migrate:rollback

# Fresh install with seed data
php artisan migrate:fresh --seed
```

### Clearing Caches
```bash
# Clear all caches
php artisan optimize:clear

# Or individually
php artisan config:clear
php artisan cache:clear
php artisan view:clear
php artisan route:clear
```

---

## Testing

### Login Credentials (Development)

| Email | Password | Role |
|-------|----------|------|
| ceo@cagsu.edu.ph | password | CEO |
| budget@cagsu.edu.ph | password | Budget Officer |
| bac@cagsu.edu.ph | password | BAC Secretariat |
| supply@cagsu.edu.ph | password | Supply Officer |
| depthead@cagsu.edu.ph | password | Department Head |
| user@cagsu.edu.ph | password | End User |

**⚠️ Change all passwords before production deployment!**

---

## Compliance

This system is designed to comply with:
- **Republic Act No. 9184** - Government Procurement Reform Act
- **2016 Revised IRR of RA 9184** - Implementing Rules and Regulations
- **COA Circulars** - Commission on Audit procurement guidelines
- **DBM Budget Circulars** - Department of Budget and Management

---

## Security Features

- ✅ Password hashing (bcrypt)
- ✅ CSRF protection
- ✅ SQL injection prevention (Eloquent ORM)
- ✅ XSS protection (Blade escaping)
- ✅ Role-based access control
- ✅ Session management
- ✅ File upload validation
- ✅ Audit logging

---

## Performance

### Optimization Tips
```bash
# Cache configuration for production
php artisan config:cache
php artisan route:cache
php artisan view:cache

# Optimize Composer autoloader
composer install --optimize-autoloader --no-dev

# Build production assets
npm run build
```

---

## Troubleshooting

### Common Issues

**Database connection failed**
- Verify MySQL is running
- Check credentials in `.env`
- Ensure database exists

**Assets not loading**
```bash
npm run build
php artisan view:clear
```

**Permission errors**
```bash
chmod -R 775 storage bootstrap/cache
```

**Resolution generation fails**
- Check PHPWord is installed: `composer show phpoffice/phpword`
- Verify storage permissions: `storage/app/resolutions/`
- Check logs: `storage/logs/laravel.log`

For more troubleshooting, see [PROJECT_TRANSFER_GUIDE.md](PROJECT_TRANSFER_GUIDE.md#troubleshooting)

---

## Contributing

This project is developed for Cagayan State University. For modifications or enhancements:

1. Follow Laravel coding standards
2. Write clear commit messages
3. Update documentation
4. Test thoroughly before deployment

---

## License

This project is proprietary software developed for Cagayan State University - Sanchez Mira Campus.

---

## Support

For questions, issues, or support:
- Check documentation in this repository
- Review Laravel logs: `storage/logs/laravel.log`
- Consult Laravel documentation: [https://laravel.com/docs](https://laravel.com/docs)

---

## Changelog

### Version 2.0 (Current)
- ✨ Added automatic BAC Resolution generation with PHPWord
- ✨ Added earmark ID auto-generation
- ✨ Added resolution download and regeneration
- ✨ Added header/footer image support for documents
- 🔄 Updated workflow: CEO → Budget → BAC
- 🔧 Improved document management system
- 📝 Enhanced documentation

### Version 1.0
- Initial release with core procurement features
- PPMP integration
- Multi-level workflow
- Supplier management

---

**System Status:** ✅ Production Ready

Built with ❤️ for Cagayan State University - Sanchez Mira Campus
