<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class TicketSeeder extends Seeder
{
    private int $ticketCounter = 0;
    private array $u   = [];
    private array $cat = [];
    private array $sub = [];
    private array $grp = [];
    private array $loc = [];
    private array $dep = [];

    public function run(): void
    {
        $this->u   = cache()->get('seed:users', []);
        $this->cat = DB::table('categories')->pluck('id', 'name_en')->all();
        $this->sub = DB::table('subcategories')->pluck('id', 'name_en')->all();
        $this->grp = DB::table('groups')->pluck('id', 'name_en')->all();
        $this->loc = DB::table('locations')->pluck('id', 'name_en')->all();
        $this->dep = DB::table('departments')->pluck('id', 'name_en')->all();

        DB::table('ticket_counters')->updateOrInsert(['id' => 1], ['last_number' => 0]);

        $this->scenarioA();
        $this->scenarioB();
        $this->scenarioC();
        $this->scenarioD();
        $this->scenarioE();
        $this->scenarioF();

        DB::table('ticket_counters')->where('id', 1)->update(['last_number' => $this->ticketCounter]);
    }

    // ── Helpers ───────────────────────────────────────────────────────────

    private function ticket(array $data): string
    {
        $this->ticketCounter++;
        $id = (string) Str::ulid();
        $now = now();
        DB::table('tickets')->insert(array_merge([
            'id'              => $id,
            'display_number'  => sprintf('TKT-%07d', $this->ticketCounter),
            'priority'        => null,
            'subcategory_id'  => null,
            'assigned_to'     => null,
            'location_id'     => null,
            'department_id'   => null,
            'close_reason'    => null,
            'close_reason_text' => null,
            'incident_origin' => 'web',
            'resolved_at'     => null,
            'closed_at'       => null,
            'cancelled_at'    => null,
            'created_at'      => $now,
            'updated_at'      => $now,
        ], $data, ['id' => $id]));
        return $id;
    }

    private function sla(string $ticketId, string $priority, string $resolutionStatus = 'on_track', bool $clockRunning = true, int $elapsed = 0): string
    {
        $id  = (string) Str::ulid();
        $now = now();
        $pol = DB::table('sla_policies')->where('priority', $priority)->first();
        DB::table('ticket_sla')->insert([
            'id'                         => $id,
            'ticket_id'                  => $ticketId,
            'response_target_minutes'    => $pol?->response_target_minutes,
            'resolution_target_minutes'  => $pol?->resolution_target_minutes,
            'response_elapsed_minutes'   => 0,
            'resolution_elapsed_minutes' => $elapsed,
            'response_met_at'            => null,
            'response_status'            => 'on_track',
            'resolution_status'          => $resolutionStatus,
            'last_clock_start'           => $clockRunning ? $now : null,
            'is_clock_running'           => $clockRunning,
            'created_at'                 => $now,
            'updated_at'                 => $now,
        ]);
        return $id;
    }

    private function slaNoTarget(string $ticketId): void
    {
        $now = now();
        DB::table('ticket_sla')->insert([
            'id'                         => (string) Str::ulid(),
            'ticket_id'                  => $ticketId,
            'response_target_minutes'    => null,
            'resolution_target_minutes'  => null,
            'response_elapsed_minutes'   => 0,
            'resolution_elapsed_minutes' => 0,
            'response_met_at'            => null,
            'response_status'            => 'on_track',
            'resolution_status'          => 'on_track',
            'last_clock_start'           => now(),
            'is_clock_running'           => true,
            'created_at'                 => $now,
            'updated_at'                 => $now,
        ]);
    }

    private function pauseLog(string $slaId, string $pauseStatus = 'on_hold', bool $resumed = false): void
    {
        $pausedAt  = now()->subMinutes(60);
        $resumedAt = $resumed ? $pausedAt->copy()->addMinutes(45) : null;
        DB::table('sla_pause_logs')->insert([
            'id'               => (string) Str::ulid(),
            'ticket_sla_id'    => $slaId,
            'paused_at'        => $pausedAt,
            'resumed_at'       => $resumedAt,
            'pause_status'     => $pauseStatus,
            'duration_minutes' => $resumed ? 45 : null,
            'created_at'       => now(),
            'updated_at'       => now(),
        ]);
    }

    private function comment(string $ticketId, string $userId, string $body, bool $internal = false): void
    {
        DB::table('comments')->insert([
            'id'         => (string) Str::ulid(),
            'ticket_id'  => $ticketId,
            'user_id'    => $userId,
            'body'       => $body,
            'is_internal'=> $internal,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function transfer(string $ticketId, string $fromId, string $toId, string $status, ?string $respondedAt = null): void
    {
        DB::table('transfer_requests')->insert([
            'id'           => (string) Str::ulid(),
            'ticket_id'    => $ticketId,
            'from_user_id' => $fromId,
            'to_user_id'   => $toId,
            'status'       => $status,
            'responded_at' => $respondedAt,
            'created_at'   => now(),
            'updated_at'   => now(),
        ]);
    }

    private function conditionReport(string $ticketId, string $techId, array $data): void
    {
        DB::table('condition_reports')->insert(array_merge([
            'id'                 => (string) Str::ulid(),
            'ticket_id'          => $ticketId,
            'location_id'        => null,
            'tech_id'            => $techId,
            'status'             => 'pending',
            'reviewed_by'        => null,
            'reviewed_at'        => null,
            'review_notes'       => null,
            'created_at'         => now(),
            'updated_at'         => now(),
        ], $data));
    }

    private function maintenanceRequest(string $ticketId, array $data): void
    {
        DB::table('maintenance_requests')->insert(array_merge([
            'id'                   => (string) Str::ulid(),
            'ticket_id'            => $ticketId,
            'generated_file_path'  => 'maintenance/' . strtolower((string) Str::ulid()) . '.pdf',
            'generated_locale'     => 'ar',
            'submitted_file_path'  => null,
            'submitted_at'         => null,
            'status'               => 'pending',
            'reviewed_by'          => null,
            'reviewed_at'          => null,
            'review_notes'         => null,
            'rejection_count'      => 0,
            'created_at'           => now(),
            'updated_at'           => now(),
        ], $data));
    }

    private function csat(string $ticketId, string $requesterId, string $techId, array $data): void
    {
        DB::table('csat_ratings')->insert(array_merge([
            'id'              => (string) Str::ulid(),
            'ticket_id'       => $ticketId,
            'requester_id'    => $requesterId,
            'tech_id'         => $techId,
            'rating'          => null,
            'comment'         => null,
            'status'          => 'pending',
            'expires_at'      => now()->addDays(7),
            'submitted_at'    => null,
            'dismissed_count' => 0,
            'created_at'      => now(),
            'updated_at'      => now(),
        ], $data));
    }

    private function notifLog(string $recipientId, string $type, string $ticketId, string $subject): void
    {
        DB::table('notification_logs')->insert([
            'id'             => (string) Str::ulid(),
            'recipient_id'   => $recipientId,
            'ticket_id'      => $ticketId,
            'type'           => $type,
            'channel'        => 'email',
            'subject'        => $subject,
            'body_preview'   => null,
            'status'         => 'sent',
            'sent_at'        => now(),
            'failure_reason' => null,
            'attempts'       => 1,
            'created_at'     => now(),
            'updated_at'     => now(),
        ]);
    }

    // ── Scenario A: Awaiting Assignment ───────────────────────────────────

    private function scenarioA(): void
    {
        $u = $this->u;

        $t = $this->ticket(['subject' => 'الطابعة في مكتب 204 لا تطبع', 'description' => '<p>الطابعة HP LaserJet في مكتب رقم 204 في المبنى الجنوبي توقفت عن الطباعة منذ الصباح. تظهر رسالة خطأ \'Paper Jam\' رغم عدم وجود أي ورقة عالقة.</p>', 'status' => 'awaiting_assignment', 'category_id' => $this->cat['Printing & Scanning'], 'subcategory_id' => $this->sub['Printer Not Printing'], 'group_id' => $this->grp['Technical Support'], 'requester_id' => $u['uMariam'], 'location_id' => $this->loc['South Wing'], 'department_id' => $this->dep['Academic Affairs']]);
        $this->slaNoTarget($t);
        $this->notifLog($u['uMariam'], 'ticket_created', $t, 'تم استلام طلبك - ' . sprintf('TKT-%07d', $this->ticketCounter));

        $t = $this->ticket(['subject' => 'شاشة الحاسوب في قاعة 101 لا تعمل', 'description' => '<p>شاشة الحاسوب في قاعة المحاضرات 101 لا تعمل منذ بداية اليوم. جُربت إعادة التشغيل دون جدوى.</p>', 'status' => 'awaiting_assignment', 'category_id' => $this->cat['Computers & Peripherals'], 'subcategory_id' => $this->sub['Damaged or Non-functional Screen'], 'group_id' => $this->grp['Technical Support'], 'requester_id' => $u['uTurki'], 'location_id' => $this->loc['Main Building'], 'department_id' => $this->dep['Academic Affairs']]);
        $this->slaNoTarget($t);

        $t = $this->ticket(['subject' => 'لا يمكن الوصول إلى شبكة الإنترنت في الجناح الشمالي', 'description' => '<p>منذ ساعتين وجميع أجهزة قسمنا في الجناح الشمالي غير قادرة على الاتصال بالإنترنت. الشبكة الداخلية تعمل بشكل طبيعي لكن الإنترنت الخارجي منقطع.</p>', 'status' => 'awaiting_assignment', 'category_id' => $this->cat['Network & Internet'], 'subcategory_id' => $this->sub['Internet Outage'], 'group_id' => $this->grp['Infrastructure & Networks'], 'requester_id' => $u['uShaima'], 'location_id' => $this->loc['North Wing'], 'department_id' => $this->dep['Facilities & Services']]);
        $this->slaNoTarget($t);

        $t = $this->ticket(['subject' => 'طلب تثبيت برنامج Microsoft Office على جهازي', 'description' => '<p>أحتاج إلى تثبيت Microsoft Office 365 على جهازي الجديد. لم يتم تثبيته عند الإعداد الأولي.</p>', 'status' => 'awaiting_assignment', 'category_id' => $this->cat['OS & Software Installation'], 'subcategory_id' => $this->sub['Software Installation'], 'group_id' => $this->grp['Software & Systems'], 'requester_id' => $u['uLamia'], 'location_id' => $this->loc['Executive Admin Tower'], 'department_id' => $this->dep['Administrative & Financial']]);
        $this->slaNoTarget($t);

        $t = $this->ticket(['subject' => 'جهاز العرض في قاعة المؤتمرات لا يتصل بالكمبيوتر', 'description' => '<p>جهاز البروجيكتور في قاعة المؤتمرات الرئيسية لا يستجيب عند توصيله بأي جهاز كمبيوتر. تم التحقق من الكابلات وكلها سليمة.</p>', 'status' => 'awaiting_assignment', 'category_id' => $this->cat['Projectors & Displays'], 'subcategory_id' => $this->sub['Projector Not Working'], 'group_id' => $this->grp['AV & Educational Tech'], 'requester_id' => $u['uNajla'], 'location_id' => $this->loc['Main Building'], 'department_id' => $this->dep['Library & Learning Resources']]);
        $this->slaNoTarget($t);
    }

    // ── Scenario B: In Progress ───────────────────────────────────────────

    private function scenarioB(): void
    {
        $u = $this->u;

        $t = $this->ticket(['subject' => 'بطء شديد في الشبكة في مختبر الحاسوب', 'description' => '<p>سرعة الشبكة في مختبر الحاسوب رقم 3 بطيئة جداً خلال ساعات الذروة. المشكلة تؤثر على جميع الطلاب.</p>', 'status' => 'in_progress', 'priority' => 'high', 'category_id' => $this->cat['Network & Internet'], 'subcategory_id' => $this->sub['Slow Network'], 'group_id' => $this->grp['Infrastructure & Networks'], 'assigned_to' => $u['uTech4'], 'requester_id' => $u['uHanan'], 'location_id' => $this->loc['Labs Complex'], 'department_id' => $this->dep['Academic Affairs']]);
        $this->sla($t, 'high');
        $this->comment($t, $u['uTech4'], '<p>تم الفحص الأولي، يبدو أن مفتاح الشبكة overloaded. سيتم فحص إعدادات QoS.</p>', true);
        $this->comment($t, $u['uTech4'], '<p>تم استلام طلبك وبدأنا بالتحقيق في المشكلة. سنعود بتحديث قريباً.</p>', false);
        $this->notifLog($u['uHanan'], 'ticket_created', $t, 'تم استلام طلبك - ' . sprintf('TKT-%07d', $this->ticketCounter));
        $this->notifLog($u['uTech4'], 'ticket_assigned', $t, 'تم تعيين تذكرة جديدة لك - ' . sprintf('TKT-%07d', $this->ticketCounter));

        $t = $this->ticket(['subject' => 'الحساب مقفل ولا أستطيع الدخول', 'description' => '<p>تم قفل حسابي في نظام الجامعة عند محاولة تسجيل الدخول اليوم. أحتاج فك القفل بشكل عاجل لأن لدي اجتماع مهم.</p>', 'status' => 'in_progress', 'priority' => 'critical', 'category_id' => $this->cat['Security & Account Access'], 'subcategory_id' => $this->sub['Account Locked'], 'group_id' => $this->grp['Software & Systems'], 'assigned_to' => $u['uTech6'], 'requester_id' => $u['uRami'], 'location_id' => $this->loc['North Wing'], 'department_id' => $this->dep['Administrative & Financial']]);
        $this->sla($t, 'critical');
        $this->comment($t, $u['uTech6'], '<p>تم التحقق من الحساب. محاولات فاشلة متعددة. سيتم إعادة تعيين كلمة المرور وفتح القفل.</p>', true);
        $this->comment($t, $u['uTech6'], '<p>جاري العمل على فك قفل حسابك. ستتلقى رسالة إلكترونية فور الانتهاء.</p>', false);
        $this->notifLog($u['uRami'], 'ticket_assigned_to_you', $t, 'تم تعيين فني لتذكرتك');

        $t = $this->ticket(['subject' => 'طلب نقطة وصول لاسلكية لقاعة الاجتماعات', 'description' => '<p>قاعة الاجتماعات في الطابق الثالث لا تغطيها شبكة الواي فاي. نحتاج إضافة نقطة وصول جديدة قبل نهاية الأسبوع.</p>', 'status' => 'in_progress', 'priority' => 'medium', 'category_id' => $this->cat['Network & Internet'], 'subcategory_id' => $this->sub['Wireless Access Point Request'], 'group_id' => $this->grp['Infrastructure & Networks'], 'assigned_to' => $u['uTech5'], 'requester_id' => $u['uSaad'], 'location_id' => $this->loc['Main Building'], 'department_id' => $this->dep['Administrative & Financial']]);
        $this->sla($t, 'medium');
        $this->comment($t, $u['uTech5'], '<p>تم الكشف على الموقع. يحتاج كابل Cat6 جديد بطول 15 متر. قدّر التنفيذ يومين.</p>', true);

        $t = $this->ticket(['subject' => 'الطابعة المشتركة في الطابق الثاني لا تستجيب', 'description' => '<p>الطابعة Canon المشتركة في ممر الطابق الثاني لا تستجيب لأي طلبات طباعة منذ الأمس.</p>', 'status' => 'in_progress', 'priority' => 'medium', 'category_id' => $this->cat['Printing & Scanning'], 'subcategory_id' => $this->sub['Printer Not Printing'], 'group_id' => $this->grp['Technical Support'], 'assigned_to' => $u['uTech3'], 'requester_id' => $u['uWalid'], 'location_id' => $this->loc['Main Building'], 'department_id' => $this->dep['Library & Learning Resources']]);
        $this->sla($t, 'medium', 'warning', true, 1200);
        $this->comment($t, $u['uTech3'], '<p>تم زيارة الموقع. المشكلة في إعدادات الشبكة للطابعة. سيتم إصلاحها اليوم.</p>', false);

        $t = $this->ticket(['subject' => 'جهاز الحاسوب لا يتعرف على القرص الصلب', 'description' => '<p>عند تشغيل الجهاز يظهر خطأ \'No Boot Device Found\'. يبدو أن القرص الصلب تالف.</p>', 'status' => 'in_progress', 'priority' => 'high', 'category_id' => $this->cat['Computers & Peripherals'], 'subcategory_id' => $this->sub['Device Failure'], 'group_id' => $this->grp['Technical Support'], 'assigned_to' => $u['uTech1'], 'requester_id' => $u['uMajid'], 'location_id' => $this->loc['South Wing'], 'department_id' => $this->dep['Facilities & Services']]);
        $this->sla($t, 'high', 'warning', true, 420);
        $this->comment($t, $u['uTech1'], '<p>تم تشخيص المشكلة: القرص الصلب تالف بالكامل. يحتاج استبدال. سيتم طلب قطعة غيار.</p>', true);

        $t = $this->ticket(['subject' => 'مشكلة في نظام إدارة التعلم Moodle', 'description' => '<p>لا يمكنني رفع الملفات على منصة Moodle. تظهر رسالة خطأ \'Upload Failed\' مع كل محاولة.</p>', 'status' => 'in_progress', 'priority' => 'medium', 'category_id' => $this->cat['Admin & Academic Systems'], 'subcategory_id' => $this->sub['Academic Management System Issue'], 'group_id' => $this->grp['Software & Systems'], 'assigned_to' => $u['uTech6'], 'requester_id' => $u['uMariam'], 'location_id' => $this->loc['Main Building'], 'department_id' => $this->dep['Academic Affairs']]);
        $this->sla($t, 'medium');

        $t = $this->ticket(['subject' => 'مكبرات الصوت في قاعة المحاضرات 205 لا تعمل', 'description' => '<p>مكبرات الصوت في قاعة 205 صامتة تماماً. تم التحقق من مستوى الصوت وإعدادات الجهاز والمشكلة مستمرة.</p>', 'status' => 'in_progress', 'priority' => 'low', 'category_id' => $this->cat['Audio & Video Systems'], 'subcategory_id' => $this->sub['Speakers Not Working'], 'group_id' => $this->grp['AV & Educational Tech'], 'assigned_to' => $u['uTech2'], 'requester_id' => $u['uDana'], 'location_id' => $this->loc['Main Building'], 'department_id' => $this->dep['Academic Affairs']]);
        $this->sla($t, 'low');

        $t = $this->ticket(['subject' => 'طلب صلاحيات وصول لنظام الميزانية', 'description' => '<p>بموجب توجيهات المدير، أحتاج إلى صلاحية قراءة في نظام الميزانية الجديد لإعداد التقارير الفصلية.</p>', 'status' => 'in_progress', 'priority' => 'low', 'category_id' => $this->cat['Admin & Academic Systems'], 'subcategory_id' => $this->sub['Access Permission Request'], 'group_id' => $this->grp['Software & Systems'], 'assigned_to' => $u['uTech6'], 'requester_id' => $u['uGhada'], 'location_id' => $this->loc['Main Building'], 'department_id' => $this->dep['Information Technology']]);
        $this->sla($t, 'low');
    }

    // ── Scenario C: On Hold ───────────────────────────────────────────────

    private function scenarioC(): void
    {
        $u = $this->u;

        $t = $this->ticket(['subject' => 'خادم قاعدة البيانات يتعطل بشكل متكرر', 'description' => '<p>خادم قاعدة البيانات الرئيسي يُعيد تشغيل نفسه كل 4-6 ساعات تقريباً. المشكلة بدأت منذ 3 أيام.</p>', 'status' => 'on_hold', 'priority' => 'critical', 'category_id' => $this->cat['Servers & Storage'], 'subcategory_id' => $this->sub['Server Not Responding'], 'group_id' => $this->grp['Infrastructure & Networks'], 'assigned_to' => $u['uTech5'], 'requester_id' => $u['uSaad'], 'location_id' => $this->loc['Main Building'], 'department_id' => $this->dep['Administrative & Financial']]);
        $slaId = $this->sla($t, 'critical', 'warning', false, 100);
        $this->pauseLog($slaId, 'on_hold');
        $this->comment($t, $u['uTech5'], '<p>تم تحليل السجلات. المشكلة محتملة في وحدة الذاكرة RAM. في انتظار وصول قطع الغيار من المورد.</p>', false);
        $this->comment($t, $u['uTech5'], '<p>طلب الشراء مرفوع، ETA يومين عمل. التذكرة ستُستأنف عند استلام القطع.</p>', true);

        $t = $this->ticket(['subject' => 'UPS في غرفة الخوادم لا يعمل', 'description' => '<p>جهاز UPS في غرفة الخوادم في الطابق السفلي يُصدر صوت إنذار ويبدو أن البطارية منتهية.</p>', 'status' => 'on_hold', 'priority' => 'high', 'category_id' => $this->cat['Power & Electrical Systems'], 'subcategory_id' => $this->sub['UPS Not Working'], 'group_id' => $this->grp['Infrastructure & Networks'], 'assigned_to' => $u['uTech4'], 'requester_id' => $u['uHussein'], 'location_id' => $this->loc['North Wing'], 'department_id' => $this->dep['Library & Learning Resources']]);
        $slaId = $this->sla($t, 'high', 'on_track', false, 60);
        $this->pauseLog($slaId, 'on_hold');
        $this->comment($t, $u['uTech4'], '<p>تم فحص الجهاز. يحتاج بطارية جديدة. بانتظار الموافقة على طلب الشراء.</p>', false);
        $this->comment($t, $u['uTech4'], '<p>طلب شراء رقم PO-2024-0312 مرفوع للإدارة المالية.</p>', true);

        $t = $this->ticket(['subject' => 'فيروس على جهاز موظف قسم المالية', 'description' => '<p>جهاز الحاسوب الخاص بي يتصرف بشكل غريب ويفتح نوافذ وإعلانات دون إذن. أعتقد أن هناك فيروساً.</p>', 'status' => 'on_hold', 'priority' => 'high', 'category_id' => $this->cat['Security & Account Access'], 'subcategory_id' => $this->sub['Suspected Security Breach'], 'group_id' => $this->grp['Software & Systems'], 'assigned_to' => $u['uTech6'], 'requester_id' => $u['uLamia'], 'location_id' => $this->loc['Executive Admin Tower'], 'department_id' => $this->dep['Administrative & Financial']]);
        $slaId = $this->sla($t, 'high', 'on_track', false, 30);
        $this->pauseLog($slaId, 'on_hold');
        $this->comment($t, $u['uTech6'], '<p>تم عزل الجهاز عن الشبكة احترازياً. جاري الفحص الكامل. سيستغرق ذلك بعض الوقت.</p>', false);
        $this->comment($t, $u['uTech6'], '<p>تم اكتشاف Trojan. الجهاز بحاجة لإعادة تهيئة كاملة. في انتظار موافقة المستخدم على مسح البيانات.</p>', true);
    }

    // ── Scenario D: Escalation Flow ───────────────────────────────────────

    private function scenarioD(): void
    {
        $u = $this->u;

        // D-1: awaiting_approval
        $t = $this->ticket(['subject' => 'تلف كامل في وحدة إمداد الطاقة للخادم', 'description' => '<p>وحدة الطاقة PSU في الخادم الرئيسي احترقت وتحتاج إلى استبدال عاجل بقطعة متخصصة.</p>', 'status' => 'awaiting_approval', 'priority' => 'critical', 'category_id' => $this->cat['Servers & Storage'], 'subcategory_id' => $this->sub['Server Not Responding'], 'group_id' => $this->grp['Infrastructure & Networks'], 'assigned_to' => $u['uTech5'], 'requester_id' => $u['uSaad'], 'location_id' => $this->loc['Main Building']]);
        $slaId = $this->sla($t, 'critical', 'warning', false, 105);
        $this->pauseLog($slaId, 'awaiting_approval');
        $this->conditionReport($t, $u['uTech5'], ['report_type' => 'hardware', 'report_date' => now()->subDay()->format('Y-m-d'), 'current_condition' => 'وحدة إمداد الطاقة (PSU) تالفة بالكامل بسبب ارتفاع مفاجئ في الجهد الكهربائي. الخادم غير قادر على التشغيل.', 'condition_analysis' => 'فحص شامل أثبت أن دائرة PSU محترقة. لا يمكن إصلاحها ويلزم استبدالها بقطعة أصلية من المورد.', 'required_action' => 'شراء وتركيب وحدة PSU جديدة من المورد المعتمد (Dell PowerEdge). تقدير التكلفة: 1,200 ريال.', 'status' => 'pending']);
        $this->comment($t, $u['uTech5'], '<p>تم إعداد تقرير الحالة. في انتظار موافقة المختص.</p>', true);

        // D-2: action_required
        $t = $this->ticket(['subject' => 'مفتاح شبكة رئيسي تالف — انقطاع كامل للشبكة في مبنى المختبرات', 'description' => '<p>مفتاح الشبكة الرئيسي في مجمع المختبرات تعطل مسبباً انقطاعاً كاملاً للشبكة في المبنى بأكمله.</p>', 'status' => 'action_required', 'priority' => 'critical', 'category_id' => $this->cat['Network & Internet'], 'subcategory_id' => $this->sub['Internet Outage'], 'group_id' => $this->grp['Infrastructure & Networks'], 'assigned_to' => $u['uTech4'], 'requester_id' => $u['uHanan'], 'location_id' => $this->loc['Labs Complex']]);
        $slaId = $this->sla($t, 'critical', 'breached', false, 200);
        $this->pauseLog($slaId, 'on_hold', true);
        $this->pauseLog($slaId, 'action_required');
        $this->conditionReport($t, $u['uTech4'], ['report_type' => 'network', 'report_date' => now()->subDays(3)->format('Y-m-d'), 'current_condition' => 'مفتاح الشبكة الرئيسي (Core Switch) بالمختبرات تالف بسبب عطل في البورد الداخلي. 200+ جهاز متأثر.', 'condition_analysis' => 'تشخيص مفصل أكد تلف الدائرة الرئيسية. المفتاح Cisco Catalyst 2960X. لا يمكن الإصلاح الجزئي.', 'required_action' => 'استبدال فوري بمفتاح Cisco Catalyst 2960X جديد. التكلفة التقديرية: 8,500 ريال. يستلزم موافقة إدارية رسمية.', 'status' => 'approved', 'reviewed_by' => $u['uApprover'], 'reviewed_at' => now()->subDays(2), 'review_notes' => 'تمت الموافقة. يُرجى إعداد مستندات الصيانة وإرسالها لمقدم الطلب لتوقيعها.']);
        $this->maintenanceRequest($t, ['status' => 'pending', 'generated_locale' => 'ar']);
        $this->comment($t, $u['uApprover'], '<p>تمت الموافقة على تقرير الحالة. يرجى تنزيل مستند الصيانة وتوقيعه ورفعه مجدداً.</p>', false);
        $this->notifLog($u['uHanan'], 'escalation_approved', $t, 'مطلوب إجراء: يرجى توقيع مستند الصيانة');

        // D-3: awaiting_final_approval
        $t = $this->ticket(['subject' => 'تلف في نظام التبريد لغرفة الخوادم', 'description' => '<p>نظام تكييف غرفة الخوادم توقف عن العمل ودرجات الحرارة ترتفع بشكل مقلق.</p>', 'status' => 'awaiting_final_approval', 'priority' => 'high', 'category_id' => $this->cat['Power & Electrical Systems'], 'subcategory_id' => $this->sub['UPS Not Working'], 'group_id' => $this->grp['Infrastructure & Networks'], 'assigned_to' => $u['uTech4'], 'requester_id' => $u['uSaad'], 'location_id' => $this->loc['Main Building']]);
        $slaId = $this->sla($t, 'high', 'warning', false, 430);
        $this->pauseLog($slaId, 'action_required', true);
        $this->pauseLog($slaId, 'awaiting_final_approval');
        $this->conditionReport($t, $u['uTech4'], ['report_type' => 'hardware', 'report_date' => now()->subDays(6)->format('Y-m-d'), 'current_condition' => 'وحدة التكييف الخاصة بغرفة الخوادم تعطلت بالكامل. درجة الحرارة وصلت 42 درجة مئوية وهي خطرة.', 'condition_analysis' => 'تعطل ضاغط الكمبريسور الرئيسي. الجهاز قديم (2016) وتكلفة إصلاحه أعلى من 70% من قيمة جهاز جديد.', 'required_action' => 'استبدال وحدة التكييف بالكامل بوحدة Samsung 5-طن متخصصة للغرف التقنية. التكلفة: 22,000 ريال.', 'status' => 'approved', 'reviewed_by' => $u['uApprover'], 'reviewed_at' => now()->subDays(5), 'review_notes' => 'تمت الموافقة على الإجراء المقترح.']);
        $this->maintenanceRequest($t, ['status' => 'submitted', 'submitted_file_path' => 'maintenance/signed/' . strtolower((string) Str::ulid()) . '.pdf', 'submitted_at' => now()->subDay()]);
        $this->comment($t, $u['uSaad'], '<p>تم رفع المستند الموقع.</p>', false);
    }

    // ── Scenario E: Terminal States ───────────────────────────────────────

    private function scenarioE(): void
    {
        $u = $this->u;

        // E-1: Resolved, CSAT submitted rating=5
        $t = $this->ticket(['subject' => 'استعادة الملفات المحذوفة من مجلد المشاركة', 'description' => '<p>تم حذف مجلد مهم بشكل غير مقصود من مجلد الشبكة المشترك. أحتاج استعادته.</p>', 'status' => 'resolved', 'priority' => 'medium', 'category_id' => $this->cat['Servers & Storage'], 'subcategory_id' => $this->sub['Storage Full'], 'group_id' => $this->grp['Infrastructure & Networks'], 'assigned_to' => $u['uTech5'], 'requester_id' => $u['uWalid'], 'location_id' => $this->loc['Main Building'], 'resolved_at' => now()->subDays(3)]);
        $this->sla($t, 'medium', 'on_track', false, 180);
        $this->comment($t, $u['uTech5'], '<p>تم استعادة المجلد بالكامل من النسخ الاحتياطية. تأكد من البيانات وأخبرني إن وجدت أي نقص.</p>', false);
        $this->csat($t, $u['uWalid'], $u['uTech5'], ['status' => 'submitted', 'rating' => 5, 'comment' => 'خدمة ممتازة وسريعة، شكراً جزيلاً', 'submitted_at' => now()->subDays(2), 'expires_at' => now()->addDays(4)]);
        $this->notifLog($u['uWalid'], 'ticket_resolved', $t, 'تم حل تذكرتك');
        $this->notifLog($u['uWalid'], 'csat_prompt', $t, 'يرجى تقييم تجربتك مع خدمة الدعم التقني');

        // E-2: Resolved, CSAT submitted rating=3
        $t = $this->ticket(['subject' => 'تحديث نظام Windows على أجهزة قسم المكتبة', 'description' => '<p>تحديث Windows 11 فشل على 5 أجهزة في قسم المكتبة وتظهر أخطاء متعددة.</p>', 'status' => 'resolved', 'priority' => 'low', 'category_id' => $this->cat['OS & Software Installation'], 'subcategory_id' => $this->sub['OS Update'], 'group_id' => $this->grp['Software & Systems'], 'assigned_to' => $u['uTech6'], 'requester_id' => $u['uNajla'], 'location_id' => $this->loc['Main Building'], 'resolved_at' => now()->subDays(10)]);
        $this->sla($t, 'low', 'on_track', false, 600);
        $this->csat($t, $u['uNajla'], $u['uTech6'], ['status' => 'submitted', 'rating' => 3, 'comment' => 'تم الحل لكن استغرق وقتاً طويلاً', 'submitted_at' => now()->subDays(9), 'expires_at' => now()->subDays(3)]);

        // E-3: Resolved, CSAT pending
        $t = $this->ticket(['subject' => 'إعادة ضبط كلمة مرور Wi-Fi للضيوف', 'description' => '<p>كلمة مرور شبكة الواي فاي الخاصة بالضيوف انتهت صلاحيتها. أحتاج تجديدها قبل حفل التخرج غداً.</p>', 'status' => 'resolved', 'priority' => 'high', 'category_id' => $this->cat['Network & Internet'], 'subcategory_id' => $this->sub['Cannot Connect to Network'], 'group_id' => $this->grp['Infrastructure & Networks'], 'assigned_to' => $u['uTech4'], 'requester_id' => $u['uDana'], 'location_id' => $this->loc['Main Building'], 'resolved_at' => now()->subDay()]);
        $this->sla($t, 'high', 'on_track', false, 45);
        $this->csat($t, $u['uDana'], $u['uTech4'], ['status' => 'pending', 'expires_at' => now()->addDays(6)]);

        // E-4: Resolved, CSAT expired, SLA breached
        $t = $this->ticket(['subject' => 'انقطاع الكهرباء الكامل عن مبنى الجناح الجنوبي', 'description' => '<p>انقطعت الكهرباء عن مبنى الجناح الجنوبي بالكامل منذ الصباح. عدد كبير من الموظفين متأثرون.</p>', 'status' => 'resolved', 'priority' => 'critical', 'category_id' => $this->cat['Power & Electrical Systems'], 'subcategory_id' => $this->sub['Power Outage in Section'], 'group_id' => $this->grp['Infrastructure & Networks'], 'assigned_to' => $u['uTech4'], 'requester_id' => $u['uMajid'], 'location_id' => $this->loc['South Wing'], 'resolved_at' => now()->subDays(20), 'created_at' => now()->subDays(22), 'updated_at' => now()->subDays(20)]);
        $this->sla($t, 'critical', 'breached', false, 300);
        $this->csat($t, $u['uMajid'], $u['uTech4'], ['status' => 'expired', 'expires_at' => now()->subDays(13)]);

        // E-5: Closed — duplicate
        $t = $this->ticket(['subject' => 'الإنترنت بطيء في الجناح الشمالي', 'description' => '<p>سرعة الإنترنت في قسمنا بالجناح الشمالي شبه معدومة منذ الأمس.</p>', 'status' => 'closed', 'priority' => 'medium', 'category_id' => $this->cat['Network & Internet'], 'subcategory_id' => $this->sub['Slow Network'], 'group_id' => $this->grp['Infrastructure & Networks'], 'requester_id' => $u['uShaima'], 'location_id' => $this->loc['North Wing'], 'close_reason' => 'duplicate', 'closed_at' => now()->subDays(5)]);
        $this->sla($t, 'medium', 'on_track', false, 0);

        // E-6: Closed — requester_unresponsive
        $t = $this->ticket(['subject' => 'مشكلة في طباعة المستندات', 'description' => '<p>لا أستطيع طباعة أي مستند. المشكلة بدأت منذ أسبوع.</p>', 'status' => 'closed', 'priority' => 'low', 'category_id' => $this->cat['Printing & Scanning'], 'subcategory_id' => $this->sub['Printer Not Printing'], 'group_id' => $this->grp['Technical Support'], 'assigned_to' => $u['uTech3'], 'requester_id' => $u['uWalid'], 'close_reason' => 'requester_unresponsive', 'closed_at' => now()->subDays(8)]);
        $this->sla($t, 'low', 'on_track', false, 120);
        $this->comment($t, $u['uTech3'], '<p>حاولنا التواصل معك 3 مرات دون رد. سيتم إغلاق التذكرة.</p>', false);

        // E-7: Closed — other
        $t = $this->ticket(['subject' => 'طلب تركيب كاميرا مراقبة في ممر الطابق الثالث', 'description' => '<p>يرجى تركيب كاميرا مراقبة إضافية في ممر الطابق الثالث بمبنى الإدارة.</p>', 'status' => 'closed', 'priority' => 'low', 'category_id' => $this->cat['Computers & Peripherals'], 'subcategory_id' => $this->sub['Device Replacement Request'], 'group_id' => $this->grp['Technical Support'], 'requester_id' => $u['uGhada'], 'close_reason' => 'other', 'close_reason_text' => 'طلب تركيب كاميرات المراقبة يقع ضمن اختصاص قسم الأمن وليس تقنية المعلومات. يُرجى التواصل مع إدارة الأمن والسلامة مباشرةً.', 'closed_at' => now()->subDays(12)]);
        $this->sla($t, 'low', 'on_track', false, 0);

        // E-8: Cancelled, no assignment
        $t = $this->ticket(['subject' => 'تثبيت برنامج AutoCAD', 'description' => '<p>أحتاج تثبيت برنامج AutoCAD 2024 على جهازي لمشروع طارئ.</p>', 'status' => 'cancelled', 'category_id' => $this->cat['OS & Software Installation'], 'subcategory_id' => $this->sub['Software Installation'], 'group_id' => $this->grp['Software & Systems'], 'requester_id' => $u['uYousef'], 'cancelled_at' => now()->subDays(7)]);
        $this->slaNoTarget($t);
        $this->comment($t, $u['uYousef'], '<p>تم إلغاء الطلب، تمكنت من استخدام جهاز زميلي.</p>', false);

        // E-9: Cancelled after assignment
        $t = $this->ticket(['subject' => 'مشكلة في عدم قبول كلمة المرور', 'description' => '<p>كلمة المرور الجديدة التي تم إنشاؤها بعد إعادة التعيين لا تعمل.</p>', 'status' => 'cancelled', 'priority' => 'medium', 'category_id' => $this->cat['Security & Account Access'], 'subcategory_id' => $this->sub['Forgotten Password'], 'group_id' => $this->grp['Software & Systems'], 'assigned_to' => $u['uTech6'], 'requester_id' => $u['uAsma'], 'cancelled_at' => now()->subDays(4)]);
        $this->sla($t, 'medium', 'on_track', false, 30);
        $this->comment($t, $u['uAsma'], '<p>حُلّت المشكلة بمفردي، شكراً.</p>', false);
    }

    // ── Scenario F: Transfer Requests ─────────────────────────────────────

    private function scenarioF(): void
    {
        $u = $this->u;

        // F-1: Pending transfer
        $t = $this->ticket(['subject' => 'لوحة مفاتيح معطوبة وتكتب حروف غلط', 'description' => '<p>عدة مفاتيح على لوحة المفاتيح تكتب حروفاً غير صحيحة، مما يصعّل كتابة كلمة المرور.</p>', 'status' => 'in_progress', 'priority' => 'low', 'category_id' => $this->cat['Computers & Peripherals'], 'subcategory_id' => $this->sub['Keyboard or Mouse Issue'], 'group_id' => $this->grp['Technical Support'], 'assigned_to' => $u['uTech1'], 'requester_id' => $u['uTurki']]);
        $this->sla($t, 'low');
        $this->transfer($t, $u['uTech1'], $u['uTech2'], 'pending');
        $this->notifLog($u['uTech2'], 'transfer_request', $t, 'طلب تحويل تذكرة جديد');
        $this->notifLog($u['uTech1'], 'transfer_request_result', $t, 'طلب التحويل الخاص بك بانتظار القبول');

        // F-2: Accepted transfer (ticket now assigned to tech2)
        $t = $this->ticket(['subject' => 'ماسح ضوئي لا يتعرف على الأوراق', 'description' => '<p>الماسح الضوئي في مكتب السكرتارية لا يتعرف على الأوراق الموضوعة فيه.</p>', 'status' => 'in_progress', 'priority' => 'medium', 'category_id' => $this->cat['Printing & Scanning'], 'subcategory_id' => $this->sub['Scanner Not Working'], 'group_id' => $this->grp['Technical Support'], 'assigned_to' => $u['uTech2'], 'requester_id' => $u['uGhada']]);
        $this->sla($t, 'medium');
        $this->transfer($t, $u['uTech3'], $u['uTech2'], 'accepted', now()->subDay()->toDateTimeString());

        // F-3: Rejected transfer (original tech2 still assigned)
        $t = $this->ticket(['subject' => 'هاتف المكتب لا يُصدر رنيناً عند الاستقبال', 'description' => '<p>هاتف المكتب يُظهر المكالمات الواردة على الشاشة لكن لا يُصدر أي صوت.</p>', 'status' => 'in_progress', 'priority' => 'low', 'category_id' => $this->cat['Phones & Communication'], 'subcategory_id' => $this->sub['Internal Phone Not Working'], 'group_id' => $this->grp['Technical Support'], 'assigned_to' => $u['uTech2'], 'requester_id' => $u['uHussein']]);
        $this->sla($t, 'low');
        $this->transfer($t, $u['uTech2'], $u['uTech3'], 'rejected', now()->subDays(2)->toDateTimeString());
    }
}
