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

    'maintenance_request' => [
        'title'     => 'طلب الصيانة',
        'export_ar' => 'تصدير بالعربية',
        'export_en' => 'تصدير بالإنجليزية',
    ],

    'upload_signed' => [
        'title'               => 'رفع طلب الصيانة الموقَّع',
        'policy_notice'       => 'يرجى مراجعة وثيقة طلب الصيانة وتوقيعها يدويًا، ثم رفع النسخة الموقَّعة بصيغة PDF أو DOCX.',
        'disclaimer_reminder' => 'برفعك الوثيقة الموقَّعة فإنك تُقرّ بإخلاء المسؤولية الوارد في طلب الصيانة.',
        'download_title'      => 'تحميل طلب الصيانة',
        'file_label'          => 'الوثيقة الموقَّعة (PDF أو DOCX، الحجم الأقصى 10 ميغابايت)',
        'submit'              => 'رفع الوثيقة الموقَّعة',
    ],

    'final_review' => [
        'title'                    => 'مراجعة طلب الصيانة الموقَّع',
        'signed_document'          => 'عرض الوثيقة الموقَّعة',
        'rejection_count'          => 'عدد مرات الرفض',
        'prior_notes'              => 'ملاحظات الرفض السابقة',
        'approve'                  => 'اعتماد',
        'approve_confirm'          => 'هل أنت متأكد من اعتماد طلب الصيانة هذا؟',
        'reject_resubmit'          => 'رفض (لإعادة التقديم)',
        'reject_permanently'       => 'رفض نهائي',
        'review_notes'             => 'ملاحظات المراجعة',
        'review_notes_placeholder' => 'أدخل سبب الرفض...',
        'close_reason'             => 'سبب الإغلاق',
        'submit_rejection'         => 'تأكيد الرفض',
        'submit_permanent'         => 'رفض نهائي',
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
        'signed_file_required'        => 'الوثيقة الموقَّعة مطلوبة.',
        'signed_file_invalid_type'    => 'يُقبل فقط ملفات PDF و DOCX.',
        'signed_file_too_large'       => 'حجم الملف يتجاوز الحد الأقصى البالغ 10 ميغابايت.',
        'close_reason_required'       => 'سبب الإغلاق مطلوب عند الرفض النهائي.',
    ],
];
