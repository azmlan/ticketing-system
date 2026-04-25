<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class ReferenceDataSeeder extends Seeder
{
    public function run(): void
    {
        $now = now();

        // Departments
        DB::table('departments')->insert([
            ['id' => (string) Str::ulid(), 'name_ar' => 'تقنية المعلومات', 'name_en' => 'Information Technology', 'is_active' => true, 'sort_order' => 1, 'created_at' => $now, 'updated_at' => $now],
            ['id' => (string) Str::ulid(), 'name_ar' => 'الشؤون الأكاديمية', 'name_en' => 'Academic Affairs', 'is_active' => true, 'sort_order' => 2, 'created_at' => $now, 'updated_at' => $now],
            ['id' => (string) Str::ulid(), 'name_ar' => 'الشؤون الإدارية والمالية', 'name_en' => 'Administrative & Financial', 'is_active' => true, 'sort_order' => 3, 'created_at' => $now, 'updated_at' => $now],
            ['id' => (string) Str::ulid(), 'name_ar' => 'إدارة المرافق والخدمات', 'name_en' => 'Facilities & Services', 'is_active' => true, 'sort_order' => 4, 'created_at' => $now, 'updated_at' => $now],
            ['id' => (string) Str::ulid(), 'name_ar' => 'المكتبة والمصادر التعليمية', 'name_en' => 'Library & Learning Resources', 'is_active' => true, 'sort_order' => 5, 'created_at' => $now, 'updated_at' => $now],
        ]);

        // Locations
        DB::table('locations')->insert([
            ['id' => (string) Str::ulid(), 'name_ar' => 'المبنى الرئيسي', 'name_en' => 'Main Building', 'is_active' => true, 'sort_order' => 1, 'created_at' => $now, 'updated_at' => $now],
            ['id' => (string) Str::ulid(), 'name_ar' => 'مبنى الجناح الشمالي', 'name_en' => 'North Wing', 'is_active' => true, 'sort_order' => 2, 'created_at' => $now, 'updated_at' => $now],
            ['id' => (string) Str::ulid(), 'name_ar' => 'مبنى الجناح الجنوبي', 'name_en' => 'South Wing', 'is_active' => true, 'sort_order' => 3, 'created_at' => $now, 'updated_at' => $now],
            ['id' => (string) Str::ulid(), 'name_ar' => 'مجمع المختبرات', 'name_en' => 'Labs Complex', 'is_active' => true, 'sort_order' => 4, 'created_at' => $now, 'updated_at' => $now],
            ['id' => (string) Str::ulid(), 'name_ar' => 'مبنى الإدارة العليا', 'name_en' => 'Executive Admin Tower', 'is_active' => true, 'sort_order' => 5, 'created_at' => $now, 'updated_at' => $now],
        ]);

        // SLA Policies
        DB::table('sla_policies')->insert([
            ['id' => (string) Str::ulid(), 'priority' => 'low',      'response_target_minutes' => 480,  'resolution_target_minutes' => 2880, 'use_24x7' => false, 'created_at' => $now, 'updated_at' => $now],
            ['id' => (string) Str::ulid(), 'priority' => 'medium',   'response_target_minutes' => 240,  'resolution_target_minutes' => 1440, 'use_24x7' => false, 'created_at' => $now, 'updated_at' => $now],
            ['id' => (string) Str::ulid(), 'priority' => 'high',     'response_target_minutes' => 60,   'resolution_target_minutes' => 480,  'use_24x7' => false, 'created_at' => $now, 'updated_at' => $now],
            ['id' => (string) Str::ulid(), 'priority' => 'critical', 'response_target_minutes' => 15,   'resolution_target_minutes' => 120,  'use_24x7' => true,  'created_at' => $now, 'updated_at' => $now],
        ]);

        // Response Templates
        DB::table('response_templates')->insert([
            // Internal
            ['id' => (string) Str::ulid(), 'title_ar' => 'تصعيد إلى الفريق التقني', 'title_en' => 'Escalate to Tech Team', 'body_ar' => '<p>تم تصعيد هذه التذكرة إلى الفريق التقني المختص. سيتم التواصل مع مقدم الطلب في أقرب وقت.</p>', 'body_en' => '<p>This ticket has been escalated to the specialized technical team. The requester will be contacted shortly.</p>', 'is_internal' => true, 'is_active' => true, 'created_at' => $now, 'updated_at' => $now],
            ['id' => (string) Str::ulid(), 'title_ar' => 'طلب معلومات إضافية', 'title_en' => 'Request Additional Information', 'body_ar' => '<p>نحتاج معلومات إضافية لإتمام معالجة هذه التذكرة. يرجى التواصل مع مقدم الطلب.</p>', 'body_en' => '<p>Additional information is needed to process this ticket. Please contact the requester.</p>', 'is_internal' => true, 'is_active' => true, 'created_at' => $now, 'updated_at' => $now],
            ['id' => (string) Str::ulid(), 'title_ar' => 'ملاحظة داخلية — تكرار المشكلة', 'title_en' => 'Internal Note — Recurring Issue', 'body_ar' => '<p>هذه المشكلة تكررت أكثر من مرة. يُنصح بإجراء فحص شامل للبنية التحتية.</p>', 'body_en' => '<p>This issue has recurred multiple times. A full infrastructure audit is recommended.</p>', 'is_internal' => true, 'is_active' => true, 'created_at' => $now, 'updated_at' => $now],
            ['id' => (string) Str::ulid(), 'title_ar' => 'تحويل للمورد الخارجي', 'title_en' => 'Refer to External Vendor', 'body_ar' => '<p>تتجاوز هذه المشكلة نطاق دعمنا الداخلي وتحتاج إلى تدخل المورد.</p>', 'body_en' => '<p>This issue is beyond our internal support scope and requires vendor intervention.</p>', 'is_internal' => true, 'is_active' => true, 'created_at' => $now, 'updated_at' => $now],
            // Public
            ['id' => (string) Str::ulid(), 'title_ar' => 'تم استلام طلبك', 'title_en' => 'Your Request Has Been Received', 'body_ar' => '<p>شكراً لتواصلك مع فريق تقنية المعلومات. تم استلام طلبك وسيتم معالجته في أقرب وقت.</p>', 'body_en' => '<p>Thank you for contacting the IT support team. Your request has been received and will be processed shortly.</p>', 'is_internal' => false, 'is_active' => true, 'created_at' => $now, 'updated_at' => $now],
            ['id' => (string) Str::ulid(), 'title_ar' => 'تم حل المشكلة — يرجى التأكيد', 'title_en' => 'Issue Resolved — Please Confirm', 'body_ar' => '<p>يسعدنا إعلامك بأنه تم حل المشكلة. يرجى إخبارنا في حال استمرار المشكلة خلال 48 ساعة.</p>', 'body_en' => '<p>We are pleased to inform you that the issue has been resolved. Please let us know within 48 hours if the problem persists.</p>', 'is_internal' => false, 'is_active' => true, 'created_at' => $now, 'updated_at' => $now],
            ['id' => (string) Str::ulid(), 'title_ar' => 'التذكرة قيد المعالجة', 'title_en' => 'Ticket Under Review', 'body_ar' => '<p>تذكرتك قيد المعالجة حالياً من قِبل الفريق التقني. سيتم إعلامك بأي تحديثات.</p>', 'body_en' => '<p>Your ticket is currently being reviewed by the technical team. You will be notified of any updates.</p>', 'is_internal' => false, 'is_active' => true, 'created_at' => $now, 'updated_at' => $now],
            ['id' => (string) Str::ulid(), 'title_ar' => 'مشكلة تقنية معروفة — جاري العمل على حلها', 'title_en' => 'Known Technical Issue — Being Addressed', 'body_ar' => '<p>نحن على علم بهذه المشكلة التقنية ويعمل فريقنا على إيجاد حل في أسرع وقت ممكن.</p>', 'body_en' => '<p>We are aware of this technical issue and our team is working to resolve it as quickly as possible.</p>', 'is_internal' => false, 'is_active' => true, 'created_at' => $now, 'updated_at' => $now],
        ]);
    }
}
