<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        $now      = now();
        $promoted = now()->subMonths(3);
        $password = Hash::make('Password@123');

        $depts    = DB::table('departments')->orderBy('sort_order')->pluck('id', 'name_en');
        $locs     = DB::table('locations')->orderBy('sort_order')->pluck('id', 'name_en');
        $groups   = DB::table('groups')->pluck('id', 'name_en');
        $perms    = DB::table('permissions')->pluck('id', 'key');

        $dIT   = $depts['Information Technology'];
        $dAcad = $depts['Academic Affairs'];
        $dAdm  = $depts['Administrative & Financial'];
        $dFac  = $depts['Facilities & Services'];
        $dLib  = $depts['Library & Learning Resources'];

        $lMain  = $locs['Main Building'];
        $lNorth = $locs['North Wing'];
        $lSouth = $locs['South Wing'];
        $lLabs  = $locs['Labs Complex'];
        $lExec  = $locs['Executive Admin Tower'];

        $gSupport  = $groups['Technical Support'];
        $gInfra    = $groups['Infrastructure & Networks'];
        $gSoftware = $groups['Software & Systems'];
        $gAV       = $groups['AV & Educational Tech'];

        // ── Fixed named users ─────────────────────────────────────────────
        $uSuperUser  = (string) Str::ulid();
        $uITManager  = (string) Str::ulid();
        $uApprover   = (string) Str::ulid();
        $uGM1        = (string) Str::ulid(); // GM Technical Support
        $uGM2        = (string) Str::ulid(); // GM Infrastructure
        $uTech1      = (string) Str::ulid(); // Faisal
        $uTech2      = (string) Str::ulid(); // Reem
        $uTech3      = (string) Str::ulid(); // Abdullah
        $uTech4      = (string) Str::ulid(); // Bandar
        $uTech5      = (string) Str::ulid(); // Hana
        $uTech6      = (string) Str::ulid(); // Omar

        // Requesters
        $uMariam   = (string) Str::ulid();
        $uTurki    = (string) Str::ulid();
        $uLamia    = (string) Str::ulid();
        $uSaad     = (string) Str::ulid();
        $uShaima   = (string) Str::ulid();
        $uMajid    = (string) Str::ulid();
        $uNajla    = (string) Str::ulid();
        $uWalid    = (string) Str::ulid();
        $uHanan    = (string) Str::ulid();
        $uRami     = (string) Str::ulid();
        $uGhada    = (string) Str::ulid();
        $uYousef   = (string) Str::ulid();
        $uAsma     = (string) Str::ulid();
        $uHussein  = (string) Str::ulid();
        $uDana     = (string) Str::ulid();

        DB::table('users')->insert([
            // Super user
            ['id' => $uSuperUser, 'full_name' => 'أحمد الشمري',   'email' => 'admin@university.edu.sa',       'password' => $password, 'employee_number' => 'EMP-0001', 'department_id' => $dIT,  'location_id' => $lExec,  'locale' => 'ar', 'is_tech' => false, 'is_super_user' => true,  'email_verified_at' => $now, 'created_at' => $now, 'updated_at' => $now],
            // IT Manager
            ['id' => $uITManager, 'full_name' => 'سارة القحطاني',  'email' => 'itmanager@university.edu.sa',   'password' => $password, 'employee_number' => 'EMP-0002', 'department_id' => $dIT,  'location_id' => $lMain,  'locale' => 'ar', 'is_tech' => true,  'is_super_user' => false, 'email_verified_at' => $now, 'created_at' => $now, 'updated_at' => $now],
            // Approver
            ['id' => $uApprover,  'full_name' => 'محمد العتيبي',   'email' => 'approver@university.edu.sa',    'password' => $password, 'employee_number' => 'EMP-0003', 'department_id' => $dAdm, 'location_id' => $lExec,  'locale' => 'ar', 'is_tech' => false, 'is_super_user' => false, 'email_verified_at' => $now, 'created_at' => $now, 'updated_at' => $now],
            // Group Managers
            ['id' => $uGM1,       'full_name' => 'خالد الدوسري',   'email' => 'gm.support@university.edu.sa',  'password' => $password, 'employee_number' => 'EMP-0010', 'department_id' => $dIT,  'location_id' => $lMain,  'locale' => 'ar', 'is_tech' => true,  'is_super_user' => false, 'email_verified_at' => $now, 'created_at' => $now, 'updated_at' => $now],
            ['id' => $uGM2,       'full_name' => 'نورة الزهراني',   'email' => 'gm.infra@university.edu.sa',    'password' => $password, 'employee_number' => 'EMP-0011', 'department_id' => $dIT,  'location_id' => $lNorth, 'locale' => 'ar', 'is_tech' => true,  'is_super_user' => false, 'email_verified_at' => $now, 'created_at' => $now, 'updated_at' => $now],
            // Technicians
            ['id' => $uTech1,     'full_name' => 'فيصل الغامدي',   'email' => 'tech1@university.edu.sa',       'password' => $password, 'employee_number' => 'EMP-0020', 'department_id' => $dIT,  'location_id' => $lMain,  'locale' => 'ar', 'is_tech' => true,  'is_super_user' => false, 'email_verified_at' => $now, 'created_at' => $now, 'updated_at' => $now],
            ['id' => $uTech2,     'full_name' => 'ريم الحربي',      'email' => 'tech2@university.edu.sa',       'password' => $password, 'employee_number' => 'EMP-0021', 'department_id' => $dIT,  'location_id' => $lNorth, 'locale' => 'ar', 'is_tech' => true,  'is_super_user' => false, 'email_verified_at' => $now, 'created_at' => $now, 'updated_at' => $now],
            ['id' => $uTech3,     'full_name' => 'عبدالله المطيري', 'email' => 'tech3@university.edu.sa',       'password' => $password, 'employee_number' => 'EMP-0022', 'department_id' => $dIT,  'location_id' => $lLabs,  'locale' => 'ar', 'is_tech' => true,  'is_super_user' => false, 'email_verified_at' => $now, 'created_at' => $now, 'updated_at' => $now],
            ['id' => $uTech4,     'full_name' => 'بندر القرني',     'email' => 'tech4@university.edu.sa',       'password' => $password, 'employee_number' => 'EMP-0023', 'department_id' => $dIT,  'location_id' => $lMain,  'locale' => 'ar', 'is_tech' => true,  'is_super_user' => false, 'email_verified_at' => $now, 'created_at' => $now, 'updated_at' => $now],
            ['id' => $uTech5,     'full_name' => 'هنا السبيعي',     'email' => 'tech5@university.edu.sa',       'password' => $password, 'employee_number' => 'EMP-0024', 'department_id' => $dIT,  'location_id' => $lNorth, 'locale' => 'ar', 'is_tech' => true,  'is_super_user' => false, 'email_verified_at' => $now, 'created_at' => $now, 'updated_at' => $now],
            ['id' => $uTech6,     'full_name' => 'عمر الشهري',      'email' => 'tech6@university.edu.sa',       'password' => $password, 'employee_number' => 'EMP-0025', 'department_id' => $dIT,  'location_id' => $lSouth, 'locale' => 'ar', 'is_tech' => true,  'is_super_user' => false, 'email_verified_at' => $now, 'created_at' => $now, 'updated_at' => $now],
            // Requesters
            ['id' => $uMariam,    'full_name' => 'مريم الصالح',     'email' => 'mariam@university.edu.sa',      'password' => $password, 'employee_number' => 'EMP-0030', 'department_id' => $dAcad, 'location_id' => $lMain,  'locale' => 'ar', 'is_tech' => false, 'is_super_user' => false, 'email_verified_at' => $now, 'created_at' => $now, 'updated_at' => $now],
            ['id' => $uTurki,     'full_name' => 'تركي الرشيدي',    'email' => 'turki@university.edu.sa',       'password' => $password, 'employee_number' => 'EMP-0031', 'department_id' => $dAcad, 'location_id' => $lSouth, 'locale' => 'ar', 'is_tech' => false, 'is_super_user' => false, 'email_verified_at' => $now, 'created_at' => $now, 'updated_at' => $now],
            ['id' => $uLamia,     'full_name' => 'لمياء العنزي',    'email' => 'lamia@university.edu.sa',       'password' => $password, 'employee_number' => 'EMP-0032', 'department_id' => $dAdm,  'location_id' => $lExec,  'locale' => 'ar', 'is_tech' => false, 'is_super_user' => false, 'email_verified_at' => $now, 'created_at' => $now, 'updated_at' => $now],
            ['id' => $uSaad,      'full_name' => 'سعد البقمي',      'email' => 'saad@university.edu.sa',        'password' => $password, 'employee_number' => 'EMP-0033', 'department_id' => $dAdm,  'location_id' => $lMain,  'locale' => 'ar', 'is_tech' => false, 'is_super_user' => false, 'email_verified_at' => $now, 'created_at' => $now, 'updated_at' => $now],
            ['id' => $uShaima,    'full_name' => 'شيماء الشمراني',  'email' => 'shaima@university.edu.sa',      'password' => $password, 'employee_number' => 'EMP-0034', 'department_id' => $dFac,  'location_id' => $lNorth, 'locale' => 'ar', 'is_tech' => false, 'is_super_user' => false, 'email_verified_at' => $now, 'created_at' => $now, 'updated_at' => $now],
            ['id' => $uMajid,     'full_name' => 'ماجد الجهني',     'email' => 'majid@university.edu.sa',       'password' => $password, 'employee_number' => 'EMP-0035', 'department_id' => $dFac,  'location_id' => $lSouth, 'locale' => 'ar', 'is_tech' => false, 'is_super_user' => false, 'email_verified_at' => $now, 'created_at' => $now, 'updated_at' => $now],
            ['id' => $uNajla,     'full_name' => 'نجلاء المالكي',   'email' => 'najla@university.edu.sa',       'password' => $password, 'employee_number' => 'EMP-0036', 'department_id' => $dLib,  'location_id' => $lMain,  'locale' => 'ar', 'is_tech' => false, 'is_super_user' => false, 'email_verified_at' => $now, 'created_at' => $now, 'updated_at' => $now],
            ['id' => $uWalid,     'full_name' => 'وليد السهلي',     'email' => 'walid@university.edu.sa',       'password' => $password, 'employee_number' => 'EMP-0037', 'department_id' => $dLib,  'location_id' => $lLabs,  'locale' => 'en', 'is_tech' => false, 'is_super_user' => false, 'email_verified_at' => $now, 'created_at' => $now, 'updated_at' => $now],
            ['id' => $uHanan,     'full_name' => 'حنان الثبيتي',    'email' => 'hanan@university.edu.sa',       'password' => $password, 'employee_number' => 'EMP-0038', 'department_id' => $dAcad, 'location_id' => $lLabs,  'locale' => 'ar', 'is_tech' => false, 'is_super_user' => false, 'email_verified_at' => $now, 'created_at' => $now, 'updated_at' => $now],
            ['id' => $uRami,      'full_name' => 'رامي الحازمي',    'email' => 'rami@university.edu.sa',        'password' => $password, 'employee_number' => 'EMP-0039', 'department_id' => $dAdm,  'location_id' => $lNorth, 'locale' => 'ar', 'is_tech' => false, 'is_super_user' => false, 'email_verified_at' => $now, 'created_at' => $now, 'updated_at' => $now],
            ['id' => $uGhada,     'full_name' => 'غادة المطرفي',    'email' => 'ghada@university.edu.sa',       'password' => $password, 'employee_number' => 'EMP-0040', 'department_id' => $dIT,   'location_id' => $lMain,  'locale' => 'ar', 'is_tech' => false, 'is_super_user' => false, 'email_verified_at' => $now, 'created_at' => $now, 'updated_at' => $now],
            ['id' => $uYousef,    'full_name' => 'يوسف القرعاوي',   'email' => 'yousef@university.edu.sa',      'password' => $password, 'employee_number' => 'EMP-0041', 'department_id' => $dAcad, 'location_id' => $lSouth, 'locale' => 'en', 'is_tech' => false, 'is_super_user' => false, 'email_verified_at' => $now, 'created_at' => $now, 'updated_at' => $now],
            ['id' => $uAsma,      'full_name' => 'أسماء الزيد',     'email' => 'asma@university.edu.sa',        'password' => $password, 'employee_number' => 'EMP-0042', 'department_id' => $dFac,  'location_id' => $lLabs,  'locale' => 'ar', 'is_tech' => false, 'is_super_user' => false, 'email_verified_at' => $now, 'created_at' => $now, 'updated_at' => $now],
            ['id' => $uHussein,   'full_name' => 'حسين المحمدي',    'email' => 'hussein@university.edu.sa',     'password' => $password, 'employee_number' => 'EMP-0043', 'department_id' => $dLib,  'location_id' => $lNorth, 'locale' => 'ar', 'is_tech' => false, 'is_super_user' => false, 'email_verified_at' => $now, 'created_at' => $now, 'updated_at' => $now],
            ['id' => $uDana,      'full_name' => 'دانة البريكي',    'email' => 'dana@university.edu.sa',        'password' => $password, 'employee_number' => 'EMP-0044', 'department_id' => $dAcad, 'location_id' => $lExec,  'locale' => 'ar', 'is_tech' => false, 'is_super_user' => false, 'email_verified_at' => $now, 'created_at' => $now, 'updated_at' => $now],
        ]);

        // ── TechProfiles ──────────────────────────────────────────────────
        DB::table('tech_profiles')->insert([
            ['id' => (string) Str::ulid(), 'user_id' => $uITManager, 'specialization' => 'IT Management',       'job_title_ar' => 'مدير تقنية المعلومات',            'job_title_en' => 'IT Manager',                        'internal_notes' => null, 'promoted_at' => $promoted, 'promoted_by' => $uSuperUser, 'created_at' => $now, 'updated_at' => $now],
            ['id' => (string) Str::ulid(), 'user_id' => $uGM1,       'specialization' => 'Hardware Support',    'job_title_ar' => 'مدير مجموعة الدعم التقني',        'job_title_en' => 'Technical Support Group Manager',   'internal_notes' => null, 'promoted_at' => $promoted, 'promoted_by' => $uITManager, 'created_at' => $now, 'updated_at' => $now],
            ['id' => (string) Str::ulid(), 'user_id' => $uGM2,       'specialization' => 'Network Infrastructure','job_title_ar' => 'مدير مجموعة البنية التحتية',    'job_title_en' => 'Infrastructure Group Manager',      'internal_notes' => null, 'promoted_at' => $promoted, 'promoted_by' => $uITManager, 'created_at' => $now, 'updated_at' => $now],
            ['id' => (string) Str::ulid(), 'user_id' => $uTech1,     'specialization' => 'Hardware Repair',     'job_title_ar' => 'فني دعم تقني',                   'job_title_en' => 'IT Support Technician',             'internal_notes' => null, 'promoted_at' => $promoted, 'promoted_by' => $uITManager, 'created_at' => $now, 'updated_at' => $now],
            ['id' => (string) Str::ulid(), 'user_id' => $uTech2,     'specialization' => 'End User Support',    'job_title_ar' => 'فني دعم تقني',                   'job_title_en' => 'IT Support Technician',             'internal_notes' => null, 'promoted_at' => $promoted, 'promoted_by' => $uITManager, 'created_at' => $now, 'updated_at' => $now],
            ['id' => (string) Str::ulid(), 'user_id' => $uTech3,     'specialization' => 'Printer & Peripherals','job_title_ar' => 'فني دعم تقني',                  'job_title_en' => 'IT Support Technician',             'internal_notes' => null, 'promoted_at' => $promoted, 'promoted_by' => $uITManager, 'created_at' => $now, 'updated_at' => $now],
            ['id' => (string) Str::ulid(), 'user_id' => $uTech4,     'specialization' => 'Network Admin',       'job_title_ar' => 'فني دعم تقني',                   'job_title_en' => 'IT Support Technician',             'internal_notes' => null, 'promoted_at' => $promoted, 'promoted_by' => $uITManager, 'created_at' => $now, 'updated_at' => $now],
            ['id' => (string) Str::ulid(), 'user_id' => $uTech5,     'specialization' => 'Server Admin',        'job_title_ar' => 'فني دعم تقني',                   'job_title_en' => 'IT Support Technician',             'internal_notes' => null, 'promoted_at' => $promoted, 'promoted_by' => $uITManager, 'created_at' => $now, 'updated_at' => $now],
            ['id' => (string) Str::ulid(), 'user_id' => $uTech6,     'specialization' => 'Systems Admin',       'job_title_ar' => 'فني دعم تقني',                   'job_title_en' => 'IT Support Technician',             'internal_notes' => null, 'promoted_at' => $promoted, 'promoted_by' => $uITManager, 'created_at' => $now, 'updated_at' => $now],
        ]);

        // ── Group memberships ─────────────────────────────────────────────
        DB::table('group_user')->insert([
            ['group_id' => $gSupport,  'user_id' => $uGM1],
            ['group_id' => $gSupport,  'user_id' => $uTech1],
            ['group_id' => $gSupport,  'user_id' => $uTech2],
            ['group_id' => $gSupport,  'user_id' => $uTech3],
            ['group_id' => $gInfra,    'user_id' => $uGM2],
            ['group_id' => $gInfra,    'user_id' => $uTech4],
            ['group_id' => $gInfra,    'user_id' => $uTech5],
            ['group_id' => $gSoftware, 'user_id' => $uTech6],
        ]);

        // ── Set group managers ────────────────────────────────────────────
        DB::table('groups')->where('id', $gSupport)->update(['manager_id' => $uGM1]);
        DB::table('groups')->where('id', $gInfra)->update(['manager_id' => $uGM2]);

        // ── Permissions ───────────────────────────────────────────────────
        $allPermIds = $perms->values()->map(fn ($id) => ['user_id' => null, 'permission_id' => $id, 'granted_by' => $uSuperUser, 'granted_at' => $now]);

        $grant = function (string $userId, array $keys) use ($perms, $uSuperUser, $now) {
            $rows = [];
            foreach ($keys as $key) {
                if ($permId = $perms->get($key)) {
                    $rows[] = ['user_id' => $userId, 'permission_id' => $permId, 'granted_by' => $uSuperUser, 'granted_at' => $now];
                }
            }
            if ($rows) {
                DB::table('permission_user')->insert($rows);
            }
        };

        $allKeys = $perms->keys()->all();

        $grant($uSuperUser,  $allKeys);
        $grant($uITManager,  $allKeys);
        $grant($uApprover,   ['escalation.approve', 'ticket.view-all', 'user.view-directory']);
        $grant($uGM1,        ['ticket.view-all', 'ticket.assign', 'group.manage-members', 'user.view-directory']);
        $grant($uGM2,        ['ticket.view-all', 'ticket.assign', 'group.manage-members', 'user.view-directory']);
        $grant($uTech1,      ['ticket.view-all', 'user.view-directory']);
        $grant($uTech2,      ['ticket.view-all', 'user.view-directory']);
        $grant($uTech3,      ['ticket.view-all', 'user.view-directory']);
        $grant($uTech4,      ['ticket.view-all', 'user.view-directory']);
        $grant($uTech5,      ['ticket.view-all', 'user.view-directory']);
        $grant($uTech6,      ['ticket.view-all', 'user.view-directory']);

        // Store IDs for TicketSeeder to pick up
        cache()->forever('seed:users', compact(
            'uSuperUser', 'uITManager', 'uApprover',
            'uGM1', 'uGM2',
            'uTech1', 'uTech2', 'uTech3', 'uTech4', 'uTech5', 'uTech6',
            'uMariam', 'uTurki', 'uLamia', 'uSaad', 'uShaima',
            'uMajid', 'uNajla', 'uWalid', 'uHanan', 'uRami',
            'uGhada', 'uYousef', 'uAsma', 'uHussein', 'uDana'
        ));
    }
}
