<?php

return [
    'title' => 'التقارير',

    'types' => [
        'ticket_volume'       => 'حجم التذاكر',
        'tickets_by_status'   => 'التذاكر حسب الحالة',
        'tickets_by_category' => 'التذاكر حسب الفئة',
        'tickets_by_priority' => 'التذاكر حسب الأولوية',
        'avg_resolution_time' => 'متوسط وقت الحل',
        'tech_performance'    => 'أداء الفنيين',
        'team_workload'       => 'عبء عمل الفريق',
        'escalation_summary'  => 'ملخص التصعيدات',
        'sla_compliance'      => 'الالتزام بـ SLA',
        'sla_breaches'        => 'انتهاكات SLA',
        'csat_overview'       => 'نظرة عامة على CSAT',
        'csat_by_tech'        => 'CSAT حسب الفني',
    ],

    'filters' => [
        'report_type' => 'نوع التقرير',
        'date_from'   => 'من تاريخ',
        'date_to'     => 'إلى تاريخ',
        'category'    => 'الفئة',
        'priority'    => 'الأولوية',
        'group'       => 'المجموعة',
        'tech'        => 'الفني',
        'status'      => 'الحالة',
        'all'         => 'الكل',
        'run'         => 'تشغيل التقرير',
    ],

    'columns' => [
        'period'             => 'التاريخ',
        'count'              => 'العدد',
        'status'             => 'الحالة',
        'category'           => 'الفئة',
        'priority'           => 'الأولوية',
        'avg_hours'          => 'متوسط ساعات الحل',
        'tech_name'          => 'الفني',
        'resolved_count'     => 'المحلولة',
        'avg_csat'           => 'متوسط تقييم CSAT',
        'sla_compliance_pct' => 'الالتزام بـ SLA',
        'open_count'         => 'التذاكر المفتوحة',
        'triggered'          => 'المُطلقة',
        'approved'           => 'الموافق عليها',
        'rejected'           => 'المرفوضة',
        'total_count'        => 'الإجمالي',
        'within_sla_count'   => 'ضمن SLA',
        'compliance_pct'     => 'نسبة الالتزام',
        'target_hours'       => 'الهدف (ساعة)',
        'actual_hours'       => 'الفعلي (ساعة)',
        'submitted_count'    => 'المُقدَّمة',
        'response_rate'      => 'معدل الاستجابة',
        'avg_rating'         => 'متوسط التقييم',
        'rating_1'           => 'نجمة واحدة',
        'rating_2'           => 'نجمتان',
        'rating_3'           => '3 نجوم',
        'rating_4'           => '4 نجوم',
        'rating_5'           => '5 نجوم',
        'rating_count'       => 'التقييمات',
        'lowest_rating'      => 'أقل تقييم',
    ],

    'labels' => [
        'uncategorised' => 'غير مصنّف',
        'none'          => 'لا يوجد',
        'no_data'       => 'لا توجد بيانات للفلاتر المحددة.',
        'select_dates'  => 'حدد نطاقاً زمنياً لتشغيل التقرير.',
    ],

    'validation' => [
        'date_from_required' => 'تاريخ البدء مطلوب.',
        'date_to_required'   => 'تاريخ الانتهاء مطلوب.',
        'date_order'         => 'يجب أن يكون تاريخ الانتهاء بعد تاريخ البدء أو مساوياً له.',
    ],

    'export' => [
        'download_csv'  => 'تنزيل CSV',
        'download_xlsx' => 'تنزيل XLSX',
        'queue_csv'     => 'تصدير CSV في الخلفية',
        'queue_xlsx'    => 'تصدير XLSX في الخلفية',
        'queued_notice' => 'جارٍ إنشاء ملف التصدير. ستتلقى إشعاراً بالبريد الإلكتروني عند اكتماله.',

        'notification_subject' => 'ملف التصدير جاهز للتنزيل',
        'notification_body'    => 'تم إنشاء ملف التصدير بصيغة :format وهو جاهز للتنزيل.',
        'notification_action'  => 'تنزيل الملف',
        'notification_expires' => 'رابط التنزيل صالح لمدة 24 ساعة.',

        // Standard column headers
        'ticket_number' => 'رقم التذكرة',
        'subject'       => 'الموضوع',
        'status'        => 'الحالة',
        'priority'      => 'الأولوية',
        'category'      => 'الفئة',
        'subcategory'   => 'الفئة الفرعية',
        'group'         => 'المجموعة',
        'assigned_tech' => 'الفني المُعيَّن',
        'requester'     => 'مقدم الطلب',
        'created_at'    => 'تاريخ الإنشاء',
        'resolved_at'   => 'تاريخ الحل',
        'closed_at'     => 'تاريخ الإغلاق',

        // SLA column headers
        'sla_response_target_mins'     => 'هدف الاستجابة (دقيقة)',
        'sla_response_actual_mins'     => 'الاستجابة الفعلية (دقيقة)',
        'sla_response_status'          => 'حالة SLA للاستجابة',
        'sla_resolution_target_mins'   => 'هدف الحل (دقيقة)',
        'sla_resolution_actual_mins'   => 'الحل الفعلي (دقيقة)',
        'sla_resolution_status'        => 'حالة SLA للحل',
        'sla_total_paused_mins'        => 'إجمالي وقت الإيقاف (دقيقة)',

        // SLA status values
        'sla_statuses' => [
            'on_track' => 'في المسار',
            'warning'  => 'تحذير',
            'breached' => 'منتهك',
        ],

        // CSAT column headers
        'csat_rating'       => 'تقييم CSAT',
        'csat_comment'      => 'تعليق CSAT',
        'csat_submitted_at' => 'تاريخ إرسال CSAT',
        'csat_status'       => 'حالة CSAT',

        // CSAT status values
        'csat_statuses' => [
            'pending'   => 'في الانتظار',
            'submitted' => 'مُرسَل',
            'expired'   => 'منتهي الصلاحية',
        ],
    ],
];
