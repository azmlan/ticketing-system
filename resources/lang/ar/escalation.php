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

    'review' => [
        'title'                    => 'مراجعة تقرير الحالة',
        'submitted_by'             => 'مُقدَّم من',
        'submitted_at'             => 'تاريخ التقديم',
        'report_date'              => 'تاريخ التقرير',
        'approve'                  => 'اعتماد',
        'approve_confirm'          => 'هل أنت متأكد من اعتماد تقرير الحالة هذا؟',
        'reject'                   => 'رفض',
        'reject_with_notes'        => 'رفض مع ملاحظات',
        'review_notes'             => 'ملاحظات المراجعة',
        'review_notes_placeholder' => 'أدخل سبب الرفض...',
        'submit_rejection'         => 'تأكيد الرفض',
        'cancel'                   => 'إلغاء',
    ],

    'validation' => [
        'report_type_required'        => 'نوع التقرير مطلوب.',
        'current_condition_required'  => 'الحالة الراهنة مطلوبة.',
        'condition_analysis_required' => 'تحليل الحالة مطلوب.',
        'required_action_required'    => 'الإجراء المطلوب مطلوب.',
        'attachments_max'             => 'لا يمكن إرفاق أكثر من 5 ملفات.',
        'review_notes_required'       => 'ملاحظات المراجعة مطلوبة عند الرفض.',
        'review_notes_max'            => 'لا يمكن أن تتجاوز ملاحظات المراجعة 1000 حرف.',
    ],
];
