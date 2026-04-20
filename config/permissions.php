<?php

/*
|--------------------------------------------------------------------------
| Permission Registry
|--------------------------------------------------------------------------
| Source of truth for all system permissions (SPEC.md §6.3).
| The PermissionSeeder reads this file. Never hardcode keys elsewhere.
|
| Structure per entry:
|   'key' => [
|       'name_ar'    => '...',
|       'name_en'    => '...',
|       'group_key'  => '...',
|   ]
*/

return [

    // ── Ticket Management ────────────────────────────────────────────────────
    'ticket.assign' => [
        'name_ar'   => 'تعيين التذاكر',
        'name_en'   => 'Assign Tickets',
        'group_key' => 'ticket',
    ],
    'ticket.close' => [
        'name_ar'   => 'إغلاق التذاكر',
        'name_en'   => 'Close Tickets',
        'group_key' => 'ticket',
    ],
    'ticket.view-all' => [
        'name_ar'   => 'عرض جميع التذاكر',
        'name_en'   => 'View All Tickets',
        'group_key' => 'ticket',
    ],
    'ticket.manage-priority' => [
        'name_ar'   => 'إدارة أولوية التذاكر',
        'name_en'   => 'Manage Ticket Priority',
        'group_key' => 'ticket',
    ],
    'ticket.delete' => [
        'name_ar'   => 'حذف التذاكر',
        'name_en'   => 'Delete Tickets',
        'group_key' => 'ticket',
    ],

    // ── Escalation & Approval ────────────────────────────────────────────────
    'escalation.approve' => [
        'name_ar'   => 'اعتماد تقارير الحالة',
        'name_en'   => 'Approve Escalations',
        'group_key' => 'escalation',
    ],

    // ── Group Management ─────────────────────────────────────────────────────
    'group.manage' => [
        'name_ar'   => 'إدارة المجموعات',
        'name_en'   => 'Manage Groups',
        'group_key' => 'group',
    ],
    'group.manage-manager' => [
        'name_ar'   => 'تعيين مدير المجموعة',
        'name_en'   => 'Manage Group Manager',
        'group_key' => 'group',
    ],
    'group.manage-members' => [
        'name_ar'   => 'إدارة أعضاء المجموعة',
        'name_en'   => 'Manage Group Members',
        'group_key' => 'group',
    ],

    // ── Category Management ──────────────────────────────────────────────────
    'category.manage' => [
        'name_ar'   => 'إدارة التصنيفات',
        'name_en'   => 'Manage Categories',
        'group_key' => 'category',
    ],

    // ── User & Account Management ────────────────────────────────────────────
    'user.promote' => [
        'name_ar'   => 'ترقية الموظفين',
        'name_en'   => 'Promote Users',
        'group_key' => 'user',
    ],
    'user.manage-permissions' => [
        'name_ar'   => 'إدارة صلاحيات المستخدمين',
        'name_en'   => 'Manage User Permissions',
        'group_key' => 'user',
    ],
    'user.view-directory' => [
        'name_ar'   => 'عرض دليل الموظفين',
        'name_en'   => 'View Employee Directory',
        'group_key' => 'user',
    ],

    // ── System Administration ────────────────────────────────────────────────
    'system.view-audit-log' => [
        'name_ar'   => 'عرض سجل التدقيق',
        'name_en'   => 'View Audit Log',
        'group_key' => 'system',
    ],
    'system.manage-notifications' => [
        'name_ar'   => 'إدارة الإشعارات',
        'name_en'   => 'Manage Notifications',
        'group_key' => 'system',
    ],
    'system.manage-departments' => [
        'name_ar'   => 'إدارة الأقسام',
        'name_en'   => 'Manage Departments',
        'group_key' => 'system',
    ],
    'system.manage-locations' => [
        'name_ar'   => 'إدارة المواقع',
        'name_en'   => 'Manage Locations',
        'group_key' => 'system',
    ],
    'system.manage-tags' => [
        'name_ar'   => 'إدارة الوسوم',
        'name_en'   => 'Manage Tags',
        'group_key' => 'system',
    ],
    'system.manage-response-templates' => [
        'name_ar'   => 'إدارة قوالب الردود',
        'name_en'   => 'Manage Response Templates',
        'group_key' => 'system',
    ],
    'system.manage-custom-fields' => [
        'name_ar'   => 'إدارة الحقول المخصصة',
        'name_en'   => 'Manage Custom Fields',
        'group_key' => 'system',
    ],
    'system.view-reports' => [
        'name_ar'   => 'عرض التقارير',
        'name_en'   => 'View Reports',
        'group_key' => 'system',
    ],
    'system.manage-sla' => [
        'name_ar'   => 'إدارة اتفاقيات مستوى الخدمة',
        'name_en'   => 'Manage SLA',
        'group_key' => 'system',
    ],

];
