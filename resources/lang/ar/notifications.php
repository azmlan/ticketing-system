<?php

return [
    'view_ticket' => 'عرض الطلب :display_number',

    'ticket_created' => [
        'subject'  => 'تم استلام طلبك — :display_number',
        'greeting' => 'مرحباً :name،',
        'body'     => 'تم استلام طلبك بنجاح. رقم الطلب: :display_number — ":subject".',
    ],

    'ticket_assigned' => [
        'subject'  => 'تم تعيين طلب جديد إليك — :display_number',
        'greeting' => 'مرحباً :name،',
        'body'     => 'تم تعيين الطلب :display_number — ":subject" إليك للمعالجة.',
    ],

    'ticket_resolved' => [
        'subject'  => 'تم حل طلبك — :display_number',
        'greeting' => 'مرحباً :name،',
        'body'     => 'يسعدنا إبلاغك بأن الطلب :display_number — ":subject" قد تم حله.',
    ],

    'ticket_closed' => [
        'subject'  => 'تم إغلاق طلبك — :display_number',
        'greeting' => 'مرحباً :name،',
        'body'     => 'تم إغلاق الطلب :display_number — ":subject".',
    ],

    'action_required' => [
        'subject'  => 'إجراء مطلوب — :display_number',
        'greeting' => 'مرحباً :name،',
        'body'     => 'يرجى مراجعة الطلب :display_number — ":subject" واتخاذ الإجراء المطلوب.',
    ],

    'form_rejected' => [
        'subject'  => 'تم رفض المستند — :display_number',
        'greeting' => 'مرحباً :name،',
        'body'     => 'تم رفض المستند المرفق بالطلب :display_number — ":subject". يرجى إعادة رفعه.',
    ],

    'escalation_submitted' => [
        'subject'  => 'طلب تصعيد جديد — :display_number',
        'greeting' => 'مرحباً :name،',
        'body'     => 'تم تقديم طلب تصعيد جديد بشأن الطلب :display_number — ":subject". يرجى المراجعة.',
    ],

    'escalation_updated' => [
        'subject'  => 'تحديث حالة التصعيد — :display_number',
        'greeting' => 'مرحباً :name،',
        'body'     => 'تم تحديث حالة التصعيد للطلب :display_number — ":subject".',
    ],

    'transfer_request' => [
        'subject'  => 'طلب تحويل تذكرة — :display_number',
        'greeting' => 'مرحباً :name،',
        'body'     => 'تم إرسال طلب تحويل الطلب :display_number — ":subject" إليك. يرجى قبوله أو رفضه.',
    ],

    'sla_warning' => [
        'subject'  => 'تحذير اتفاقية مستوى الخدمة — :display_number',
        'greeting' => 'مرحباً :name،',
        'body'     => 'الطلب :display_number — ":subject" يقترب من الموعد النهائي لاتفاقية مستوى الخدمة. يرجى اتخاذ الإجراء اللازم في أقرب وقت.',
    ],

    'sla_breached' => [
        'subject'  => 'تجاوز اتفاقية مستوى الخدمة — :display_number',
        'greeting' => 'مرحباً :name،',
        'body'     => 'تجاوز الطلب :display_number — ":subject" الموعد النهائي لاتفاقية مستوى الخدمة. يُرجى التدخل الفوري.',
    ],
];
