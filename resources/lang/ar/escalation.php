<?php

return [
    'condition_report' => [
        'title'              => 'تقرير الحالة',
        'report_type'        => 'نوع التقرير',
        'location'           => 'الموقع',
        'select_location'    => '-- اختر الموقع --',
        'report_date'        => 'تاريخ التقرير',
        'current_condition'  => 'الحالة الراهنة',
        'condition_analysis' => 'تحليل الحالة',
        'required_action'    => 'الإجراء المطلوب',
        'attachments'        => 'المرفقات (حتى 5 ملفات)',
        'submit'             => 'إرسال التقرير',
    ],

    'validation' => [
        'report_type_required'        => 'نوع التقرير مطلوب.',
        'current_condition_required'  => 'الحالة الراهنة مطلوبة.',
        'condition_analysis_required' => 'تحليل الحالة مطلوب.',
        'required_action_required'    => 'الإجراء المطلوب مطلوب.',
        'attachments_max'             => 'لا يمكن إرفاق أكثر من 5 ملفات.',
    ],
];
