<?php

return [
    'title' => 'التقارير',

    'types' => [
        'ticket_volume'       => 'حجم التذاكر',
        'tickets_by_status'   => 'التذاكر حسب الحالة',
        'tickets_by_category' => 'التذاكر حسب الفئة',
        'tickets_by_priority' => 'التذاكر حسب الأولوية',
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
        'period'    => 'التاريخ',
        'count'     => 'العدد',
        'status'    => 'الحالة',
        'category'  => 'الفئة',
        'priority'  => 'الأولوية',
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
];
