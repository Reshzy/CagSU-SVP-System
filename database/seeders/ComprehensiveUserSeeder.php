<?php

namespace Database\Seeders;

use App\Models\Department;
use App\Models\Position;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class ComprehensiveUserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $password = Hash::make('password123'); // Default password for all users

        // Get the Administrative Office department (or first department)
        $adminDept = Department::where('code', 'ADMIN')->first() ?? Department::first();

        // Get all positions
        $positions = Position::pluck('id', 'name')->toArray();

        // 1. System Administrator
        $systemAdmin = User::updateOrCreate(
            ['email' => 'sysadmin@cagsu.edu.ph'],
            [
                'name' => 'System Administrator',
                'password' => $password,
                'department_id' => $adminDept->id,
                'employee_id' => 'CAGSU-SA-001',
                'position_id' => $positions['System Administrator'] ?? null,
                'phone' => '+63-917-111-0001',
                'is_active' => true,
                'approval_status' => 'approved',
                'email_verified_at' => now(),
            ]
        );
        $systemAdmin->syncRoles(['System Admin']);

        // 2. Supply Officer
        $supplyOfficer = User::updateOrCreate(
            ['email' => 'supply@cagsu.edu.ph'],
            [
                'name' => 'Ronnie S. Agcaoili',
                'password' => $password,
                'department_id' => $adminDept->id,
                'employee_id' => 'CAGSU-SO-001',
                'position_id' => $positions['Supply Officer'] ?? null,
                'phone' => '+63-917-222-0002',
                'is_active' => true,
                'approval_status' => 'approved',
                'email_verified_at' => now(),
            ]
        );
        $supplyOfficer->syncRoles(['Supply Officer']);

        // 3. Budget Officer
        $budgetOfficer = User::updateOrCreate(
            ['email' => 'budget@cagsu.edu.ph'],
            [
                'name' => 'Catalina B. Talosig',
                'password' => $password,
                'department_id' => $adminDept->id,
                'employee_id' => 'CAGSU-BO-001',
                'position_id' => $positions['Budget Officer'] ?? null,
                'phone' => '+63-917-333-0003',
                'is_active' => true,
                'approval_status' => 'approved',
                'email_verified_at' => now(),
            ]
        );
        $budgetOfficer->syncRoles(['Budget Office']);

        // 4. Executive Officer
        $executiveOfficer = User::updateOrCreate(
            ['email' => 'executive@cagsu.edu.ph'],
            [
                'name' => 'Rodel Francisco T. Alegado',
                'password' => $password,
                'department_id' => $adminDept->id,
                'employee_id' => 'CAGSU-EO-001',
                'position_id' => $positions['Executive Officer'] ?? null,
                'phone' => '+63-917-444-0004',
                'is_active' => true,
                'approval_status' => 'approved',
                'email_verified_at' => now(),
            ]
        );
        $executiveOfficer->syncRoles(['Executive Officer']);

        // 5. BAC Chairman (Chairman)
        $bacChairman = User::updateOrCreate(
            ['email' => 'bac.chairman@cagsu.edu.ph'],
            [
                'name' => 'Christopher R. Garingan',
                'password' => $password,
                'department_id' => $adminDept->id,
                'employee_id' => 'CAGSU-BAC-CHAIR-001',
                'position_id' => $positions['BAC Chairman'] ?? null,
                'phone' => '+63-917-555-0005',
                'is_active' => true,
                'approval_status' => 'approved',
                'email_verified_at' => now(),
            ]
        );
        $bacChairman->syncRoles(['BAC Chair']);

        // 6. BAC Vice Chairman
        $bacViceChairman = User::updateOrCreate(
            ['email' => 'bac.vicechairman@cagsu.edu.ph'],
            [
                'name' => 'Allan O. De La Cruz',
                'password' => $password,
                'department_id' => $adminDept->id,
                'employee_id' => 'CAGSU-BAC-VICE-001',
                'position_id' => $positions['BAC Chairman'] ?? null, // Same position, different role
                'phone' => '+63-917-555-0006',
                'is_active' => true,
                'approval_status' => 'approved',
                'email_verified_at' => now(),
            ]
        );
        $bacViceChairman->syncRoles(['BAC Chair']); // Vice Chairman uses same role

        // 7-9. BAC Members (3 members)
        $bacMembers = [
            [
                'name' => 'Valentin M. Apostol',
                'email' => 'bac.member1@cagsu.edu.ph',
                'employee_id' => 'CAGSU-BAC-MEM-001',
                'phone' => '+63-917-666-0007',
            ],
            [
                'name' => 'Chris Ian T. Rodriguez',
                'email' => 'bac.member2@cagsu.edu.ph',
                'employee_id' => 'CAGSU-BAC-MEM-002',
                'phone' => '+63-917-666-0008',
            ],
            [
                'name' => 'Melvin S. Atayan',
                'email' => 'bac.member3@cagsu.edu.ph',
                'employee_id' => 'CAGSU-BAC-MEM-003',
                'phone' => '+63-917-666-0009',
            ],
        ];

        foreach ($bacMembers as $memberData) {
            $member = User::updateOrCreate(
                ['email' => $memberData['email']],
                [
                    'name' => $memberData['name'],
                    'password' => $password,
                    'department_id' => $adminDept->id,
                    'employee_id' => $memberData['employee_id'],
                    'position_id' => $positions['BAC Member'] ?? null,
                    'phone' => $memberData['phone'],
                    'is_active' => true,
                    'approval_status' => 'approved',
                    'email_verified_at' => now(),
                ]
            );
            $member->syncRoles(['BAC Members']);
        }

        // 10. BAC Secretary
        $bacSecretary = User::updateOrCreate(
            ['email' => 'bac.secretary@cagsu.edu.ph'],
            [
                'name' => 'Chanda T. Aquino',
                'password' => $password,
                'department_id' => $adminDept->id,
                'employee_id' => 'CAGSU-BAC-SEC-001',
                'position_id' => $positions['BAC Secretary'] ?? null,
                'phone' => '+63-917-777-0010',
                'is_active' => true,
                'approval_status' => 'approved',
                'email_verified_at' => now(),
            ]
        );
        $bacSecretary->syncRoles(['BAC Secretariat']);

        // 11. Accounting Officer
        $accountingOfficer = User::updateOrCreate(
            ['email' => 'accounting@cagsu.edu.ph'],
            [
                'name' => 'Fely Jane R. Reyes',
                'password' => $password,
                'department_id' => $adminDept->id,
                'employee_id' => 'CAGSU-ACC-001',
                'position_id' => $positions['Accounting Officer'] ?? null,
                'phone' => '+63-917-888-0011',
                'is_active' => true,
                'approval_status' => 'approved',
                'email_verified_at' => now(),
            ]
        );
        $accountingOfficer->syncRoles(['Accounting Office']);

        // 12. Canvassing Officer
        $canvassingOfficer = User::updateOrCreate(
            ['email' => 'canvassing@cagsu.edu.ph'],
            [
                'name' => 'Chito D. Temporal',
                'password' => $password,
                'department_id' => $adminDept->id,
                'employee_id' => 'CAGSU-CANV-001',
                'position_id' => $positions['Canvassing Officer'] ?? null,
                'phone' => '+63-917-999-0012',
                'is_active' => true,
                'approval_status' => 'approved',
                'email_verified_at' => now(),
            ]
        );
        $canvassingOfficer->syncRoles(['Canvassing Unit']);

        // 13-22. College Users (Deans/End Users for each college)
        $colleges = [
            ['name' => 'Calayan Extension', 'code' => 'CALEXT', 'dean' => 'Dullit, Rex S., MSA', 'email' => 'calayanextension.sanchezmira@csu.edu.ph', 'description' => 'Remote learning and academic outreach'],
            ['name' => 'College of Agriculture', 'code' => 'CA', 'dean' => 'Ms. May M. Leaño', 'email' => 'maycmartinez03@csu.edu.ph', 'description' => 'Agricultural science and sustainable farming'],
            ['name' => 'College of Business, Entrepreneurship, and Accountancy', 'code' => 'CBEA', 'dean' => 'Mr. Rey D. Viloria, CPA', 'email' => 'cbea.sanchezmira@csu.edu.ph', 'description' => 'Business, entrepreneurship, and financial management'],
            ['name' => 'College of Criminal Justice Education', 'code' => 'CCJE', 'dean' => 'Dr. Jose Sheriff O. Panelo', 'email' => 'ccje.csusm@csu.edu.ph', 'description' => 'Law enforcement and criminal justice studies'],
            ['name' => 'College of Engineering', 'code' => 'COE', 'dean' => 'Engr. Marvin D. Adorio', 'email' => 'coe.sanchezmira@csu.edu.ph', 'description' => 'Engineering education and innovation'],
            ['name' => 'College of Hospitality Management', 'code' => 'CHM', 'dean' => 'Ms. Angela B. Tuliao', 'email' => 'angelabtuliao@csu.edu.ph', 'description' => 'Hospitality, tourism, and service excellence'],
            ['name' => 'College of Industrial Technology', 'code' => 'CIT', 'dean' => 'Ms. Jane Gladys A. Monje', 'email' => 'cit.sanchezmira@csu.edu.ph', 'description' => 'Technical skills and industrial innovation'],
            ['name' => 'College of Information and Computing Sciences', 'code' => 'CICS', 'dean' => 'Dr. Manny S. Alipio', 'email' => 'cics_csusm@csu.edu.ph', 'description' => 'IT, computing, and digital innovation'],
            ['name' => 'College of Teacher Education', 'code' => 'CTED', 'dean' => 'Dr. Verlino D. Baddu', 'email' => 'ctedcsusm@csu.edu.ph', 'description' => 'Teacher training and educational leadership'],
            ['name' => 'Graduate School', 'code' => 'GS', 'dean' => 'Dr. Melba B. Rosales', 'email' => 'graduateschool.sanchezmira@csu.edu.ph', 'description' => 'Advanced studies and research programs'],
        ];

        foreach ($colleges as $index => $collegeData) {
            // Find or create the college department - check by both code and name
            $college = Department::where('code', $collegeData['code'])->first();
            
            // If not found by code, check by name
            if (!$college) {
                $college = Department::where('name', $collegeData['name'])->first();
                
                // If found by name but code differs, update the code
                if ($college && $college->code !== $collegeData['code']) {
                    $college->update([
                        'code' => $collegeData['code'],
                        'description' => $collegeData['description'],
                        'is_active' => true,
                        'is_archived' => false,
                    ]);
                }
            }

            if (!$college) {
                $college = Department::create([
                    'name' => $collegeData['name'],
                    'code' => $collegeData['code'],
                    'description' => $collegeData['description'],
                    'is_active' => true,
                    'is_archived' => false,
                ]);
            }

            // Create Dean for this college
            $dean = User::updateOrCreate(
                ['email' => $collegeData['email']],
                [
                    'name' => $collegeData['dean'],
                    'password' => $password,
                    'department_id' => $college->id,
                    'employee_id' => sprintf('CAGSU-%s-DEAN-001', $collegeData['code']),
                    'position_id' => $positions['Employee'] ?? null,
                    'phone' => sprintf('+63-917-%03d-%04d', 100 + $index, 1000 + $index),
                    'is_active' => true,
                    'approval_status' => 'approved',
                    'email_verified_at' => now(),
                ]
            );
            $dean->syncRoles(['Dean']);
        }

        $this->command->info('✅ Successfully created comprehensive user accounts:');
        $this->command->info('   - 1 System Administrator');
        $this->command->info('   - 1 Supply Officer');
        $this->command->info('   - 1 Budget Officer');
        $this->command->info('   - 1 Executive Officer');
        $this->command->info('   - 2 BAC Chairman (Chairman & Vice Chairman)');
        $this->command->info('   - 3 BAC Members');
        $this->command->info('   - 1 BAC Secretary');
        $this->command->info('   - 1 Accounting Officer');
        $this->command->info('   - 1 Canvassing Officer');
        $this->command->info('   - 10 College Deans');
        $this->command->info('');
        $this->command->info('Default password for all users: password123');
    }
}
