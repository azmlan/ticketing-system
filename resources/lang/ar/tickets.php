<?php

return [
    'create' => [
        'title'       => 'إنشاء تذكرة جديدة',
        'subject'     => 'الموضوع',
        'description' => 'الوصف',
        'category'    => 'التصنيف',
        'subcategory' => 'التصنيف الفرعي',
        'submit'      => 'إرسال التذكرة',
        'success'     => 'تم إنشاء التذكرة بنجاح.',
    ],
    'validation' => [
        'subject_required'       => 'الموضوع مطلوب.',
        'subject_max'            => 'يجب ألا يتجاوز الموضوع 255 حرفاً.',
        'description_required'   => 'الوصف مطلوب.',
        'category_required'      => 'يرجى اختيار التصنيف.',
        'category_invalid'       => 'التصنيف المحدد غير صالح.',
        'subcategory_required'   => 'التصنيف الفرعي مطلوب لهذا التصنيف.',
        'subcategory_invalid'    => 'التصنيف الفرعي المحدد غير صالح.',
    ],
    'rate_limit_exceeded' => 'لقد تجاوزت الحد المسموح به من التذاكر. يرجى المحاولة لاحقاً.',
    'select_category'     => 'اختر التصنيف...',
    'select_subcategory'  => 'اختر التصنيف الفرعي...',
];
