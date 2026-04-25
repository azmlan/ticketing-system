<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class GroupCategorySeeder extends Seeder
{
    public function run(): void
    {
        $now = now();

        // 4 Groups (manager_id set later by UserSeeder after users are created)
        $gSupport  = (string) Str::ulid();
        $gInfra    = (string) Str::ulid();
        $gSoftware = (string) Str::ulid();
        $gAV       = (string) Str::ulid();

        DB::table('groups')->insert([
            ['id' => $gSupport,  'name_ar' => 'الدعم التقني',                 'name_en' => 'Technical Support',        'manager_id' => null, 'is_active' => true, 'created_at' => $now, 'updated_at' => $now],
            ['id' => $gInfra,    'name_ar' => 'البنية التحتية والشبكات',       'name_en' => 'Infrastructure & Networks', 'manager_id' => null, 'is_active' => true, 'created_at' => $now, 'updated_at' => $now],
            ['id' => $gSoftware, 'name_ar' => 'البرمجيات والأنظمة',            'name_en' => 'Software & Systems',        'manager_id' => null, 'is_active' => true, 'created_at' => $now, 'updated_at' => $now],
            ['id' => $gAV,       'name_ar' => 'الوسائط والتقنيات التعليمية',   'name_en' => 'AV & Educational Tech',     'manager_id' => null, 'is_active' => true, 'created_at' => $now, 'updated_at' => $now],
        ]);

        // 12 Categories
        $catComputers  = (string) Str::ulid();
        $catPrinting   = (string) Str::ulid();
        $catPhones     = (string) Str::ulid();
        $catNetwork    = (string) Str::ulid();
        $catServers    = (string) Str::ulid();
        $catPower      = (string) Str::ulid();
        $catOS         = (string) Str::ulid();
        $catAdminSys   = (string) Str::ulid();
        $catSecurity   = (string) Str::ulid();
        $catProjectors = (string) Str::ulid();
        $catAudio      = (string) Str::ulid();
        $catELearning  = (string) Str::ulid();

        DB::table('categories')->insert([
            // Technical Support
            ['id' => $catComputers,  'name_ar' => 'أجهزة الحاسوب والمحيطات',    'name_en' => 'Computers & Peripherals',     'group_id' => $gSupport,  'is_active' => true, 'sort_order' => 1, 'version' => 1, 'created_at' => $now, 'updated_at' => $now],
            ['id' => $catPrinting,   'name_ar' => 'الطباعة والمسح الضوئي',       'name_en' => 'Printing & Scanning',         'group_id' => $gSupport,  'is_active' => true, 'sort_order' => 2, 'version' => 1, 'created_at' => $now, 'updated_at' => $now],
            ['id' => $catPhones,     'name_ar' => 'الهواتف وأجهزة الاتصال',      'name_en' => 'Phones & Communication',      'group_id' => $gSupport,  'is_active' => true, 'sort_order' => 3, 'version' => 1, 'created_at' => $now, 'updated_at' => $now],
            // Infrastructure & Networks
            ['id' => $catNetwork,    'name_ar' => 'الشبكة والإنترنت',            'name_en' => 'Network & Internet',          'group_id' => $gInfra,    'is_active' => true, 'sort_order' => 1, 'version' => 1, 'created_at' => $now, 'updated_at' => $now],
            ['id' => $catServers,    'name_ar' => 'الخوادم والتخزين',             'name_en' => 'Servers & Storage',           'group_id' => $gInfra,    'is_active' => true, 'sort_order' => 2, 'version' => 1, 'created_at' => $now, 'updated_at' => $now],
            ['id' => $catPower,      'name_ar' => 'الكهرباء وأنظمة الطاقة',      'name_en' => 'Power & Electrical Systems',  'group_id' => $gInfra,    'is_active' => true, 'sort_order' => 3, 'version' => 1, 'created_at' => $now, 'updated_at' => $now],
            // Software & Systems
            ['id' => $catOS,         'name_ar' => 'أنظمة التشغيل والتثبيت',      'name_en' => 'OS & Software Installation',  'group_id' => $gSoftware, 'is_active' => true, 'sort_order' => 1, 'version' => 1, 'created_at' => $now, 'updated_at' => $now],
            ['id' => $catAdminSys,   'name_ar' => 'الأنظمة الإدارية والتعليمية', 'name_en' => 'Admin & Academic Systems',    'group_id' => $gSoftware, 'is_active' => true, 'sort_order' => 2, 'version' => 1, 'created_at' => $now, 'updated_at' => $now],
            ['id' => $catSecurity,   'name_ar' => 'الأمن المعلوماتي والحسابات',  'name_en' => 'Security & Account Access',   'group_id' => $gSoftware, 'is_active' => true, 'sort_order' => 3, 'version' => 1, 'created_at' => $now, 'updated_at' => $now],
            // AV & Educational Tech
            ['id' => $catProjectors, 'name_ar' => 'أجهزة العرض والشاشات',        'name_en' => 'Projectors & Displays',       'group_id' => $gAV,       'is_active' => true, 'sort_order' => 1, 'version' => 1, 'created_at' => $now, 'updated_at' => $now],
            ['id' => $catAudio,      'name_ar' => 'أنظمة الصوت والفيديو',         'name_en' => 'Audio & Video Systems',       'group_id' => $gAV,       'is_active' => true, 'sort_order' => 2, 'version' => 1, 'created_at' => $now, 'updated_at' => $now],
            ['id' => $catELearning,  'name_ar' => 'أجهزة التعليم الإلكتروني',    'name_en' => 'E-Learning Devices',          'group_id' => $gAV,       'is_active' => true, 'sort_order' => 3, 'version' => 1, 'created_at' => $now, 'updated_at' => $now],
        ]);

        // ~26 Subcategories
        DB::table('subcategories')->insert([
            // Computers & Peripherals
            ['id' => (string) Str::ulid(), 'category_id' => $catComputers, 'name_ar' => 'عطل في الجهاز',                      'name_en' => 'Device Failure',                  'is_required' => false, 'is_active' => true, 'sort_order' => 1, 'version' => 1, 'created_at' => $now, 'updated_at' => $now],
            ['id' => (string) Str::ulid(), 'category_id' => $catComputers, 'name_ar' => 'طلب استبدال جهاز',                   'name_en' => 'Device Replacement Request',       'is_required' => false, 'is_active' => true, 'sort_order' => 2, 'version' => 1, 'created_at' => $now, 'updated_at' => $now],
            ['id' => (string) Str::ulid(), 'category_id' => $catComputers, 'name_ar' => 'مشكلة في لوحة المفاتيح أو الفأرة',  'name_en' => 'Keyboard or Mouse Issue',         'is_required' => false, 'is_active' => true, 'sort_order' => 3, 'version' => 1, 'created_at' => $now, 'updated_at' => $now],
            ['id' => (string) Str::ulid(), 'category_id' => $catComputers, 'name_ar' => 'شاشة تالفة أو لا تعمل',             'name_en' => 'Damaged or Non-functional Screen', 'is_required' => false, 'is_active' => true, 'sort_order' => 4, 'version' => 1, 'created_at' => $now, 'updated_at' => $now],
            // Printing & Scanning
            ['id' => (string) Str::ulid(), 'category_id' => $catPrinting,  'name_ar' => 'الطابعة لا تطبع',                   'name_en' => 'Printer Not Printing',            'is_required' => false, 'is_active' => true, 'sort_order' => 1, 'version' => 1, 'created_at' => $now, 'updated_at' => $now],
            ['id' => (string) Str::ulid(), 'category_id' => $catPrinting,  'name_ar' => 'احتياج للحبر أو مواد استهلاكية',    'name_en' => 'Ink or Consumables Needed',       'is_required' => false, 'is_active' => true, 'sort_order' => 2, 'version' => 1, 'created_at' => $now, 'updated_at' => $now],
            ['id' => (string) Str::ulid(), 'category_id' => $catPrinting,  'name_ar' => 'ماسح ضوئي لا يعمل',                 'name_en' => 'Scanner Not Working',             'is_required' => false, 'is_active' => true, 'sort_order' => 3, 'version' => 1, 'created_at' => $now, 'updated_at' => $now],
            // Phones & Communication
            ['id' => (string) Str::ulid(), 'category_id' => $catPhones,    'name_ar' => 'هاتف داخلي لا يعمل',               'name_en' => 'Internal Phone Not Working',      'is_required' => false, 'is_active' => true, 'sort_order' => 1, 'version' => 1, 'created_at' => $now, 'updated_at' => $now],
            ['id' => (string) Str::ulid(), 'category_id' => $catPhones,    'name_ar' => 'طلب توصيل خط هاتفي',               'name_en' => 'Phone Line Setup Request',        'is_required' => false, 'is_active' => true, 'sort_order' => 2, 'version' => 1, 'created_at' => $now, 'updated_at' => $now],
            // Network & Internet
            ['id' => (string) Str::ulid(), 'category_id' => $catNetwork,   'name_ar' => 'انقطاع الإنترنت',                   'name_en' => 'Internet Outage',                 'is_required' => false, 'is_active' => true, 'sort_order' => 1, 'version' => 1, 'created_at' => $now, 'updated_at' => $now],
            ['id' => (string) Str::ulid(), 'category_id' => $catNetwork,   'name_ar' => 'بطء في الشبكة',                     'name_en' => 'Slow Network',                    'is_required' => false, 'is_active' => true, 'sort_order' => 2, 'version' => 1, 'created_at' => $now, 'updated_at' => $now],
            ['id' => (string) Str::ulid(), 'category_id' => $catNetwork,   'name_ar' => 'لا يمكن الوصول للشبكة',            'name_en' => 'Cannot Connect to Network',       'is_required' => false, 'is_active' => true, 'sort_order' => 3, 'version' => 1, 'created_at' => $now, 'updated_at' => $now],
            ['id' => (string) Str::ulid(), 'category_id' => $catNetwork,   'name_ar' => 'طلب نقطة وصول لاسلكية',            'name_en' => 'Wireless Access Point Request',   'is_required' => false, 'is_active' => true, 'sort_order' => 4, 'version' => 1, 'created_at' => $now, 'updated_at' => $now],
            // Servers & Storage
            ['id' => (string) Str::ulid(), 'category_id' => $catServers,   'name_ar' => 'خادم لا يستجيب',                    'name_en' => 'Server Not Responding',           'is_required' => false, 'is_active' => true, 'sort_order' => 1, 'version' => 1, 'created_at' => $now, 'updated_at' => $now],
            ['id' => (string) Str::ulid(), 'category_id' => $catServers,   'name_ar' => 'مساحة تخزين ممتلئة',               'name_en' => 'Storage Full',                    'is_required' => false, 'is_active' => true, 'sort_order' => 2, 'version' => 1, 'created_at' => $now, 'updated_at' => $now],
            // Power & Electrical
            ['id' => (string) Str::ulid(), 'category_id' => $catPower,     'name_ar' => 'انقطاع الكهرباء في قسم',           'name_en' => 'Power Outage in Section',         'is_required' => false, 'is_active' => true, 'sort_order' => 1, 'version' => 1, 'created_at' => $now, 'updated_at' => $now],
            ['id' => (string) Str::ulid(), 'category_id' => $catPower,     'name_ar' => 'جهاز UPS لا يعمل',                  'name_en' => 'UPS Not Working',                 'is_required' => false, 'is_active' => true, 'sort_order' => 2, 'version' => 1, 'created_at' => $now, 'updated_at' => $now],
            // OS & Software Installation
            ['id' => (string) Str::ulid(), 'category_id' => $catOS,        'name_ar' => 'تثبيت برنامج',                      'name_en' => 'Software Installation',           'is_required' => false, 'is_active' => true, 'sort_order' => 1, 'version' => 1, 'created_at' => $now, 'updated_at' => $now],
            ['id' => (string) Str::ulid(), 'category_id' => $catOS,        'name_ar' => 'تحديث نظام التشغيل',               'name_en' => 'OS Update',                       'is_required' => false, 'is_active' => true, 'sort_order' => 2, 'version' => 1, 'created_at' => $now, 'updated_at' => $now],
            ['id' => (string) Str::ulid(), 'category_id' => $catOS,        'name_ar' => 'إزالة برامج خبيثة',                'name_en' => 'Malware Removal',                 'is_required' => false, 'is_active' => true, 'sort_order' => 3, 'version' => 1, 'created_at' => $now, 'updated_at' => $now],
            // Admin & Academic Systems
            ['id' => (string) Str::ulid(), 'category_id' => $catAdminSys,  'name_ar' => 'مشكلة في نظام الإدارة الأكاديمية', 'name_en' => 'Academic Management System Issue', 'is_required' => false, 'is_active' => true, 'sort_order' => 1, 'version' => 1, 'created_at' => $now, 'updated_at' => $now],
            ['id' => (string) Str::ulid(), 'category_id' => $catAdminSys,  'name_ar' => 'طلب صلاحية وصول',                  'name_en' => 'Access Permission Request',        'is_required' => true,  'is_active' => true, 'sort_order' => 2, 'version' => 1, 'created_at' => $now, 'updated_at' => $now],
            // Security & Account Access
            ['id' => (string) Str::ulid(), 'category_id' => $catSecurity,  'name_ar' => 'نسيان كلمة المرور',                'name_en' => 'Forgotten Password',              'is_required' => false, 'is_active' => true, 'sort_order' => 1, 'version' => 1, 'created_at' => $now, 'updated_at' => $now],
            ['id' => (string) Str::ulid(), 'category_id' => $catSecurity,  'name_ar' => 'قفل الحساب',                        'name_en' => 'Account Locked',                  'is_required' => false, 'is_active' => true, 'sort_order' => 2, 'version' => 1, 'created_at' => $now, 'updated_at' => $now],
            ['id' => (string) Str::ulid(), 'category_id' => $catSecurity,  'name_ar' => 'اشتباه في اختراق أمني',            'name_en' => 'Suspected Security Breach',       'is_required' => false, 'is_active' => true, 'sort_order' => 3, 'version' => 1, 'created_at' => $now, 'updated_at' => $now],
            // Projectors & Displays
            ['id' => (string) Str::ulid(), 'category_id' => $catProjectors,'name_ar' => 'جهاز العرض لا يعمل',               'name_en' => 'Projector Not Working',           'is_required' => false, 'is_active' => true, 'sort_order' => 1, 'version' => 1, 'created_at' => $now, 'updated_at' => $now],
            ['id' => (string) Str::ulid(), 'category_id' => $catProjectors,'name_ar' => 'شاشة عرض تالفة',                   'name_en' => 'Damaged Display Screen',          'is_required' => false, 'is_active' => true, 'sort_order' => 2, 'version' => 1, 'created_at' => $now, 'updated_at' => $now],
            // Audio & Video Systems
            ['id' => (string) Str::ulid(), 'category_id' => $catAudio,     'name_ar' => 'مكبرات صوت لا تعمل',               'name_en' => 'Speakers Not Working',            'is_required' => false, 'is_active' => true, 'sort_order' => 1, 'version' => 1, 'created_at' => $now, 'updated_at' => $now],
            ['id' => (string) Str::ulid(), 'category_id' => $catAudio,     'name_ar' => 'نظام الفيديو لا يعمل',             'name_en' => 'Video System Not Working',        'is_required' => false, 'is_active' => true, 'sort_order' => 2, 'version' => 1, 'created_at' => $now, 'updated_at' => $now],
            // E-Learning Devices
            ['id' => (string) Str::ulid(), 'category_id' => $catELearning, 'name_ar' => 'جهاز لوحي لا يعمل',               'name_en' => 'Tablet Not Working',              'is_required' => false, 'is_active' => true, 'sort_order' => 1, 'version' => 1, 'created_at' => $now, 'updated_at' => $now],
            ['id' => (string) Str::ulid(), 'category_id' => $catELearning, 'name_ar' => 'مشكلة في منصة التعلم الإلكتروني', 'name_en' => 'E-Learning Platform Issue',       'is_required' => false, 'is_active' => true, 'sort_order' => 2, 'version' => 1, 'created_at' => $now, 'updated_at' => $now],
        ]);
    }
}
