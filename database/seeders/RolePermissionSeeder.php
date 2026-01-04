<?php

namespace Database\Seeders;

use App\Models\Department;
use App\Models\User;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class RolePermissionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Create Permissions
        $permissions = [
            // Purchase Request Permissions
            'create-purchase-request',
            'view-purchase-request',
            'edit-purchase-request',
            'approve-purchase-request',
            'reject-purchase-request',
            'assign-pr-control-number',

            // Budget & Earmarking Permissions
            'view-budget-info',
            'create-earmark',
            'approve-earmark',
            'validate-budget',

            // BAC (Bids & Awards Committee) Permissions
            'view-bac-documents',
            'create-bac-resolution',
            'evaluate-quotations',
            'approve-abstract-quotation',
            'conduct-bac-meeting',
            'award-contract',

            // Supplier Management Permissions
            'manage-suppliers',
            'view-supplier-info',
            'request-quotations',
            'evaluate-supplier-performance',

            // Purchase Order Permissions
            'create-purchase-order',
            'approve-purchase-order',
            'send-po-to-supplier',
            'track-delivery',
            'accept-delivery',

            // Document Management Permissions
            'upload-documents',
            'view-documents',
            'approve-documents',
            'archive-documents',

            // Workflow Management Permissions
            'view-workflow-status',
            'manage-approvals',
            'assign-tasks',
            'escalate-issues',

            // Reporting & Analytics Permissions
            'view-reports',
            'create-reports',
            'view-analytics',
            'export-data',

            // System Administration Permissions
            'manage-users',
            'manage-roles',
            'manage-permissions',
            'system-configuration',
            'view-audit-logs',

            // Accounting Permissions
            'process-payments',
            'view-financial-data',
            'create-disbursement-voucher',
            'validate-costs',
        ];

        foreach ($permissions as $permission) {
            Permission::create(['name' => $permission]);
        }

        // Create Roles and Assign Permissions

        // 1. System Admin - Full Access
        $systemAdmin = Role::create(['name' => 'System Admin']);
        $systemAdmin->givePermissionTo(Permission::all());

        // 2. End User - Basic Access
        $endUser = Role::create(['name' => 'End User']);
        $endUser->givePermissionTo([
            'create-purchase-request',
            'view-purchase-request',
            'view-workflow-status',
            'upload-documents',
            'view-documents',
        ]);

        // 3. Dean - College Head with PPMP Management
        $dean = Role::create(['name' => 'Dean']);
        $dean->givePermissionTo([
            'create-purchase-request',
            'view-purchase-request',
            'view-workflow-status',
            'upload-documents',
            'view-documents',
            'view-budget-info',
            'view-reports',
        ]);

        // 4. Supply Officer - PR Management & Coordination
        $supplyOfficer = Role::create(['name' => 'Supply Officer']);
        $supplyOfficer->givePermissionTo([
            'view-purchase-request',
            'edit-purchase-request',
            'assign-pr-control-number',
            'create-purchase-order',
            'send-po-to-supplier',
            'track-delivery',
            'accept-delivery',
            'manage-suppliers',
            'view-supplier-info',
            'request-quotations',
            'upload-documents',
            'view-documents',
            'view-workflow-status',
            'manage-approvals',
            'view-reports',
        ]);

        // 5. Budget Office - Earmarking & Budget Validation
        $budgetOffice = Role::create(['name' => 'Budget Office']);
        $budgetOffice->givePermissionTo([
            'view-purchase-request',
            'view-budget-info',
            'create-earmark',
            'approve-earmark',
            'validate-budget',
            'validate-costs',
            'view-workflow-status',
            'view-documents',
            'view-reports',
        ]);

        // 6. BAC Chair - Lead BAC Activities
        $bacChair = Role::create(['name' => 'BAC Chair']);
        $bacChair->givePermissionTo([
            'view-purchase-request',
            'view-bac-documents',
            'create-bac-resolution',
            'evaluate-quotations',
            'approve-abstract-quotation',
            'conduct-bac-meeting',
            'award-contract',
            'view-supplier-info',
            'evaluate-supplier-performance',
            'view-workflow-status',
            'manage-approvals',
            'upload-documents',
            'view-documents',
            'approve-documents',
            'view-reports',
        ]);

        // 7. BAC Members - Participate in BAC Activities
        $bacMembers = Role::create(['name' => 'BAC Members']);
        $bacMembers->givePermissionTo([
            'view-purchase-request',
            'view-bac-documents',
            'evaluate-quotations',
            'conduct-bac-meeting',
            'view-supplier-info',
            'view-workflow-status',
            'view-documents',
            'view-reports',
        ]);

        // 8. BAC Secretariat - Administrative Support for BAC
        $bacSecretariat = Role::create(['name' => 'BAC Secretariat']);
        $bacSecretariat->givePermissionTo([
            'view-purchase-request',
            'view-bac-documents',
            'create-bac-resolution',
            'evaluate-quotations',
            'conduct-bac-meeting',
            'view-supplier-info',
            'request-quotations',
            'view-workflow-status',
            'upload-documents',
            'view-documents',
            'create-reports',
            'view-reports',
        ]);

        // 9. Canvassing Unit - Supplier Outreach
        $canvassingUnit = Role::create(['name' => 'Canvassing Unit']);
        $canvassingUnit->givePermissionTo([
            'view-purchase-request',
            'manage-suppliers',
            'view-supplier-info',
            'request-quotations',
            'evaluate-supplier-performance',
            'view-workflow-status',
            'upload-documents',
            'view-documents',
            'view-reports',
        ]);

        // 10. Executive Officer - Final Approvals
        $executiveOfficer = Role::create(['name' => 'Executive Officer']);
        // Treat Executive Officer as admin-equivalent: grant all permissions
        $executiveOfficer->givePermissionTo(Permission::all());

        // 10. Accounting Office - Payment Processing
        $accountingOffice = Role::create(['name' => 'Accounting Office']);
        $accountingOffice->givePermissionTo([
            'view-purchase-request',
            'process-payments',
            'view-financial-data',
            'create-disbursement-voucher',
            'validate-costs',
            'view-workflow-status',
            'view-documents',
            'view-reports',
        ]);

        // 11. Supplier - Limited Portal Access
        $supplier = Role::create(['name' => 'Supplier']);
        $supplier->givePermissionTo([
            'view-purchase-request', // Only assigned to them
            'view-supplier-info', // Their own info
            'upload-documents', // Quotations and supporting docs
            'view-documents', // Their own documents
            'track-delivery', // Their delivery status
        ]);

        // Create Departments
        // $departments = [
        //     ['name' => 'Administrative Office', 'code' => 'ADMIN', 'description' => 'Main administrative office'],
        //     ['name' => 'Academic Affairs', 'code' => 'ACAD', 'description' => 'Academic affairs department'],
        //     ['name' => 'Finance Office', 'code' => 'FINANCE', 'description' => 'Financial management and budgeting'],
        //     ['name' => 'Information Technology', 'code' => 'IT', 'description' => 'IT services and support'],
        //     ['name' => 'Human Resources', 'code' => 'HR', 'description' => 'Human resources management'],
        //     ['name' => 'Facilities Management', 'code' => 'FACILITY', 'description' => 'Campus facilities and maintenance'],
        //     ['name' => 'Security Office', 'code' => 'SECURITY', 'description' => 'Campus security services'],
        // ];

        // foreach ($departments as $dept) {
        //     Department::create($dept);
        // }

        // Create System Admin User
        // $adminUser = User::create([
        //     'name' => 'CagSU System Administrator',
        //     'email' => 'admin@cagsu.edu.ph',
        //     'password' => bcrypt('admin123'),
        //     'department_id' => 1,
        //     'employee_id' => 'CAGSU-ADMIN-001',
        //     'position' => 'System Administrator',
        //     'phone' => '+63-123-456-7890',
        //     'is_active' => true,
        //     'email_verified_at' => now(),
        // ]);
        // $adminUser->assignRole('System Admin');

        // Create Sample Supply Officer
        // $supplyUser = User::create([
        //     'name' => 'Supply Officer',
        //     'email' => 'supply@cagsu.edu.ph',
        //     'password' => bcrypt('supply123'),
        //     'department_id' => 1,
        //     'employee_id' => 'CAGSU-SUPPLY-001',
        //     'position' => 'Supply Officer',
        //     'phone' => '+63-123-456-7891',
        //     'is_active' => true,
        //     'email_verified_at' => now(),
        // ]);
        // $supplyUser->assignRole('Supply Officer');

        $this->command->info('Roles, permissions, departments, and sample users created successfully!');
    }
}
