# CAGSU Supply, Purchase, and Voucher System

## Project Overview

This is a complete Purchase Request and Procurement System for Cagayan de Oro City Government Office (CAGSU).

### System Architecture
- **Framework:** Laravel 10.x
- **Frontend:** Blade Templates with Tailwind CSS
- **Database:** MySQL
- **Authentication:** Laravel built-in with role-based access control

### Key Features

✅ **Purchase Request Management**
- Create and submit purchase requests
- PPMP (Procurement Plan) integration with pre-approved items
- Budget tracking and verification
- Document attachment support

✅ **Multi-Level Workflow Approval**
- Department Head review and approval
- Budget Officer budget verification
- BAC (Bids and Awards Committee) evaluation
- CEO final approval

✅ **Supplier Management**
- Supplier registration and management
- Quotation management
- Supplier messaging system
- Performance tracking

✅ **Inventory Management**
- Receipt tracking
- Stock management
- Dispatch voucher system

✅ **Reporting & Analytics**
- Purchase request reports
- Budget utilization reports
- Supplier performance reports
- Department-wise analytics

✅ **User Management**
- Role-based access control
- Department-specific dashboards
- Activity logs and audit trails

---

## User Roles

1. **System Administrator**
   - Full system access
   - User management
   - System configuration

2. **CEO**
   - Final approval authority
   - Global system oversight
   - Executive reports

3. **Department Head**
   - Department oversight
   - Purchase request approval
   - Budget allocation

4. **Budget Officer**
   - Budget verification
   - Financial planning
   - Budget reports

5. **BAC Member**
   - Bid evaluation
   - Quotation analysis
   - Contract preparation

6. **Supply Officer**
   - Inventory management
   - Receipt processing
   - Dispatch management

7. **Accounting**
   - Financial records
   - Payment processing
   - Voucher management

8. **Supply Officer (Delegation)**
   - BAC member designation
   - Alternate responsibilities

---

## Technology Stack

### Backend
- PHP 8.1+
- Laravel 10.x
- MySQL Database
- Laravel Sanctum (API authentication)

### Frontend
- Blade Templates
- Tailwind CSS
- JavaScript (Vanilla)
- Alpine.js (for interactivity)

### Development Tools
- Composer (PHP dependency management)
- npm (Node.js package management)
- XAMPP (local development server)

---

## Installation

Please refer to **INSTALLATION_INSTRUCTIONS.md** for detailed setup instructions.

### Quick Start:
1. Install XAMPP
2. Extract project to `C:\xampp\htdocs\CapstoneLatest\`
3. Start Apache and MySQL in XAMPP
4. Import `cagsu_svp_system_backup.sql` to phpMyAdmin
5. Run `composer install` and `npm install`
6. Access at `http://localhost/CapstoneLatest/public`

---

## Database Structure

### Core Tables:
- `users` - User accounts with roles
- `departments` - Organizational departments
- `purchase_requests` - Main PR data
- `purchase_request_items` - PR line items
- `ppmp_items` - Pre-approved procurement items
- `suppliers` - Vendor information
- `quotations` - Supplier quotes
- `workflow_approvals` - Approval tracking
- `disbursement_vouchers` - Payment records
- `inventory_receipts` - Goods received

---

## Security Features

✅ Role-based access control
✅ Session management
✅ CSRF protection
✅ Password hashing (bcrypt)
✅ SQL injection prevention (Eloquent ORM)
✅ XSS protection (Blade templating)
✅ File upload validation
✅ Audit logging

---

## Workflow Process

### Standard Purchase Request Flow:

1. **User Creates PR**
   - Selects department and purpose
   - Adds items from PPMP catalog
   - Submits for approval

2. **Department Head Review**
   - Reviews PR details
   - Approves or rejects
   - Can add comments

3. **Budget Officer Verification**
   - Checks budget availability
   - Verifies account codes
   - Approves if within budget

4. **BAC Evaluation**
   - Opens bidding/quotation process
   - Reviews supplier quotes
   - Recommends supplier

5. **CEO Final Approval**
   - Reviews entire process
   - Grants final approval
   - System generates PO

6. **Order Processing**
   - Supplier delivery
   - Receipt and inspection
   - Payment processing

---

## Presentation Tips

### For Your Teacher:

1. **Demonstrate the Complete Workflow:**
   - Create a purchase request as a regular user
   - Show the approval process through each role
   - Display how the system tracks each step

2. **Highlight Key Features:**
   - PPMP integration (pre-approved items)
   - Real-time budget tracking
   - Automated workflow routing
   - Document management

3. **Show Different Perspectives:**
   - Switch between user roles to show role-specific dashboards
   - Demonstrate how each role sees only what they need

4. **Technical Excellence:**
   - Point out clean code structure
   - Show MVC architecture
   - Demonstrate security best practices
   - Highlight scalable database design

### Sample Demo Script:

1. **Login as Department User**
   - Create a purchase request
   - Add items from PPMP
   - Show budget calculation

2. **Login as Department Head**
   - Show pending approvals
   - Review and approve PR
   - Add approval comments

3. **Login as Budget Officer**
   - Verify budget availability
   - Show budget dashboard
   - Approve budget allocation

4. **Login as BAC Member**
   - View approved PRs
   - Show quotation management
   - Demonstrate evaluation process

5. **Login as CEO**
   - Show executive dashboard
   - Demonstrate final approval
   - Show system-wide statistics

6. **Login as Admin**
   - Show user management
   - Demonstrate system configuration
   - Show reports and analytics

---

## System Highlights

### 1. Smart PPMP Integration
- Pre-populated item catalog
- Category-based organization
- Fixed and custom pricing support
- Budget pre-calculation

### 2. Automated Workflow
- Intelligent routing based on department
- Automatic notifications
- Status tracking at each stage
- Comment and feedback system

### 3. Budget Management
- Real-time budget tracking
- Account code verification
- Spending projections
- Budget allocation monitoring

### 4. Supplier Relations
- Centralized supplier database
- Quotation comparison
- Performance tracking
- Communication history

### 5. Document Management
- Secure file storage
- Version control
- Access logging
- Multi-document support

---

## Compliance & Standards

This system complies with:
- P.D. No. 1445 (Government Accounting)
- COA Circulars on Procurement
- Republic Act 9184 (Government Procurement Reform Act)
- DBM National Budget Memorandum Circulars

---

## Future Enhancements

- Email notifications
- Mobile app support
- Advanced analytics dashboard
- Barcode/QR code integration
- Automated budget alerts
- Multi-branch support

---

## Support Documentation

- `SYSTEM_ARCHITECTURE.md` - Technical architecture details
- `WORKFLOW_REROUTE_SUMMARY.md` - Workflow documentation
- `DATABASE_PPMP_UPDATE_SUMMARY.md` - Database schema
- `QUICK_SETUP_GUIDE.md` - Quick setup instructions
- `MIGRATION_QUICK_REFERENCE.md` - Migration guide

---

## Contact

For questions or technical support regarding this system, please refer to the installation instructions or contact the development team.

---

**Good luck with your presentation!** 🎓📊

