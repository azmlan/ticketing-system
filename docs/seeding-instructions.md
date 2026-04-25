# Seeding Instructions — IT Ticketing System

This document is the authoritative reference for generating realistic seed data for the system. It is intended to be read by a future context (or the current one) to implement `DatabaseSeeder`, factory `definition()` bodies, and any additional seeder classes — without needing to re-explore the codebase.

All data represents a fictional Saudi university's IT department. Primary locale is Arabic (`ar`); all bilingual fields must have both `_ar` and `_en` values.

---

## 1. Seeding Order (Dependency Graph)

Seed in this exact order to satisfy foreign-key constraints:

```
1. permissions          (PermissionSeeder — already exists, run it)
2. departments
3. locations
4. sla_policies
5. response_templates
6. groups
7. categories           (depends on groups)
8. subcategories        (depends on categories)
9. users                (depends on departments, locations)
10. tech_profiles       (depends on users where is_tech=true)
11. group_user pivot    (depends on groups + tech users)
12. permission_user     (depends on permissions + users)
13. ticket_counters     (seed row id=1 with last_number=0, will increment during ticket seeding)
14. tickets             (depends on users, categories, groups, locations, departments)
15. ticket_sla          (depends on tickets + sla_policies)
16. sla_pause_logs      (depends on ticket_sla)
17. comments            (depends on tickets + users)
18. transfer_requests   (depends on tickets + tech users)
19. condition_reports   (depends on tickets + tech users + locations)
20. maintenance_requests (depends on tickets)
21. csat_ratings        (depends on tickets + users)
22. notification_logs   (depends on tickets + users)
```

---

## 2. Reference Data

### 2.1 Departments (5 records)

| name_ar                        | name_en                    | is_active | sort_order |
|-------------------------------|----------------------------|-----------|------------|
| تقنية المعلومات                 | Information Technology     | true      | 1          |
| الشؤون الأكاديمية               | Academic Affairs           | true      | 2          |
| الشؤون الإدارية والمالية        | Administrative & Financial | true      | 3          |
| إدارة المرافق والخدمات          | Facilities & Services      | true      | 4          |
| المكتبة والمصادر التعليمية      | Library & Learning Resources | true    | 5          |

### 2.2 Locations (5 records)

| name_ar                   | name_en              | is_active | sort_order |
|--------------------------|----------------------|-----------|------------|
| المبنى الرئيسي             | Main Building        | true      | 1          |
| مبنى الجناح الشمالي        | North Wing           | true      | 2          |
| مبنى الجناح الجنوبي        | South Wing           | true      | 3          |
| مجمع المختبرات             | Labs Complex         | true      | 4          |
| مبنى الإدارة العليا         | Executive Admin Tower | true     | 5          |

### 2.3 SLA Policies (4 records — one per priority)

| priority | response_target_minutes | resolution_target_minutes | use_24x7 |
|----------|------------------------|--------------------------|----------|
| low      | 480                    | 2880                     | false    |
| medium   | 240                    | 1440                     | false    |
| high     | 60                     | 480                      | false    |
| critical | 15                     | 120                      | true     |

Notes:
- Business hours are Sun–Thu, 08:00–16:00 AST for non-24x7 policies.
- Critical tickets have `use_24x7 = true` — SLA clock runs continuously.
- These are realistic for an educational IT department.

### 2.4 Response Templates (8 records)

**Internal templates (is_internal = true):**

1. title_ar: "تصعيد إلى الفريق التقني" / title_en: "Escalate to Tech Team"
   body_ar: "تم تصعيد هذه التذكرة إلى الفريق التقني المختص. سيتم التواصل مع مقدم الطلب في أقرب وقت."
   body_en: "This ticket has been escalated to the specialized technical team. The requester will be contacted shortly."

2. title_ar: "طلب معلومات إضافية" / title_en: "Request Additional Information"
   body_ar: "نحتاج معلومات إضافية لإتمام معالجة هذه التذكرة. يرجى التواصل مع مقدم الطلب."
   body_en: "Additional information is needed to process this ticket. Please contact the requester."

3. title_ar: "ملاحظة داخلية — تكرار المشكلة" / title_en: "Internal Note — Recurring Issue"
   body_ar: "هذه المشكلة تكررت أكثر من مرة. يُنصح بإجراء فحص شامل للبنية التحتية."
   body_en: "This issue has recurred multiple times. A full infrastructure audit is recommended."

4. title_ar: "تحويل للمورد الخارجي" / title_en: "Refer to External Vendor"
   body_ar: "تتجاوز هذه المشكلة نطاق دعمنا الداخلي وتحتاج إلى تدخل المورد."
   body_en: "This issue is beyond our internal support scope and requires vendor intervention."

**Public templates (is_internal = false):**

5. title_ar: "تم استلام طلبك" / title_en: "Your Request Has Been Received"
   body_ar: "شكراً لتواصلك مع فريق تقنية المعلومات. تم استلام طلبك وسيتم معالجته في أقرب وقت."
   body_en: "Thank you for contacting the IT support team. Your request has been received and will be processed shortly."

6. title_ar: "تم حل المشكلة — يرجى التأكيد" / title_en: "Issue Resolved — Please Confirm"
   body_ar: "يسعدنا إعلامك بأنه تم حل المشكلة. يرجى إخبارنا في حال استمرار المشكلة خلال 48 ساعة."
   body_en: "We are pleased to inform you that the issue has been resolved. Please let us know within 48 hours if the problem persists."

7. title_ar: "التذكرة قيد المعالجة" / title_en: "Ticket Under Review"
   body_ar: "تذكرتك قيد المعالجة حالياً من قِبل الفريق التقني. سيتم إعلامك بأي تحديثات."
   body_en: "Your ticket is currently being reviewed by the technical team. You will be notified of any updates."

8. title_ar: "مشكلة تقنية معروفة — جاري العمل على حلها" / title_en: "Known Technical Issue — Being Addressed"
   body_ar: "نحن على علم بهذه المشكلة التقنية ويعمل فريقنا على إيجاد حل في أسرع وقت ممكن."
   body_en: "We are aware of this technical issue and our team is working to resolve it as quickly as possible."

---

## 3. Groups, Categories, and Subcategories

### 3.1 Groups (4 records)

| name_ar                        | name_en                  | manager_id           | is_active |
|-------------------------------|--------------------------|----------------------|-----------|
| الدعم التقني                   | Technical Support        | (tech manager user)  | true      |
| البنية التحتية والشبكات        | Infrastructure & Networks | (tech manager user) | true      |
| البرمجيات والأنظمة             | Software & Systems       | (group manager user) | true      |
| الوسائط والتقنيات التعليمية    | AV & Educational Tech    | (group manager user) | true      |

Note: Set `manager_id` after users are created. Group managers reference the 2 group-manager tech users.

### 3.2 Categories (12 records)

**Group: Technical Support (3 categories)**

| name_ar                        | name_en                  | is_active | sort_order |
|-------------------------------|--------------------------|-----------|------------|
| أجهزة الحاسوب والمحيطات       | Computers & Peripherals  | true      | 1          |
| الطباعة والمسح الضوئي          | Printing & Scanning      | true      | 2          |
| الهواتف وأجهزة الاتصال         | Phones & Communication   | true      | 3          |

**Group: Infrastructure & Networks (3 categories)**

| name_ar                        | name_en                  | is_active | sort_order |
|-------------------------------|--------------------------|-----------|------------|
| الشبكة والإنترنت               | Network & Internet       | true      | 1          |
| الخوادم والتخزين               | Servers & Storage        | true      | 2          |
| الكهرباء وأنظمة الطاقة         | Power & Electrical Systems | true    | 3          |

**Group: Software & Systems (3 categories)**

| name_ar                        | name_en                  | is_active | sort_order |
|-------------------------------|--------------------------|-----------|------------|
| أنظمة التشغيل والتثبيت         | OS & Software Installation | true    | 1          |
| الأنظمة الإدارية والتعليمية    | Admin & Academic Systems | true      | 2          |
| الأمن المعلوماتي والحسابات     | Security & Account Access | true     | 3          |

**Group: AV & Educational Tech (3 categories)**

| name_ar                        | name_en                  | is_active | sort_order |
|-------------------------------|--------------------------|-----------|------------|
| أجهزة العرض والشاشات           | Projectors & Displays    | true      | 1          |
| أنظمة الصوت والفيديو           | Audio & Video Systems    | true      | 2          |
| أجهزة التعليم الإلكتروني       | E-Learning Devices       | true      | 3          |

### 3.3 Subcategories (~26 records)

**Computers & Peripherals:**
- عطل في الجهاز / Device Failure (is_required: false, sort_order: 1)
- طلب استبدال جهاز / Device Replacement Request (is_required: false, sort_order: 2)
- مشكلة في لوحة المفاتيح أو الفأرة / Keyboard or Mouse Issue (is_required: false, sort_order: 3)
- شاشة تالفة أو لا تعمل / Damaged or Non-functional Screen (is_required: false, sort_order: 4)

**Printing & Scanning:**
- الطابعة لا تطبع / Printer Not Printing (is_required: false, sort_order: 1)
- احتياج للحبر أو مواد استهلاكية / Ink or Consumables Needed (is_required: false, sort_order: 2)
- ماسح ضوئي لا يعمل / Scanner Not Working (is_required: false, sort_order: 3)

**Phones & Communication:**
- هاتف داخلي لا يعمل / Internal Phone Not Working (is_required: false, sort_order: 1)
- طلب توصيل خط هاتفي / Phone Line Setup Request (is_required: false, sort_order: 2)

**Network & Internet:**
- انقطاع الإنترنت / Internet Outage (is_required: false, sort_order: 1)
- بطء في الشبكة / Slow Network (is_required: false, sort_order: 2)
- لا يمكن الوصول للشبكة / Cannot Connect to Network (is_required: false, sort_order: 3)
- طلب نقطة وصول لاسلكية / Wireless Access Point Request (is_required: false, sort_order: 4)

**Servers & Storage:**
- خادم لا يستجيب / Server Not Responding (is_required: false, sort_order: 1)
- مساحة تخزين ممتلئة / Storage Full (is_required: false, sort_order: 2)

**Power & Electrical Systems:**
- انقطاع الكهرباء في قسم / Power Outage in Section (is_required: false, sort_order: 1)
- جهاز UPS لا يعمل / UPS Not Working (is_required: false, sort_order: 2)

**OS & Software Installation:**
- تثبيت برنامج / Software Installation (is_required: false, sort_order: 1)
- تحديث نظام التشغيل / OS Update (is_required: false, sort_order: 2)
- إزالة برامج خبيثة / Malware Removal (is_required: false, sort_order: 3)

**Admin & Academic Systems:**
- مشكلة في نظام الإدارة الأكاديمية / Academic Management System Issue (is_required: false, sort_order: 1)
- طلب صلاحية وصول / Access Permission Request (is_required: true, sort_order: 2)

**Security & Account Access:**
- نسيان كلمة المرور / Forgotten Password (is_required: false, sort_order: 1)
- قفل الحساب / Account Locked (is_required: false, sort_order: 2)
- اشتباه في اختراق أمني / Suspected Security Breach (is_required: false, sort_order: 3)

**Projectors & Displays:**
- جهاز العرض لا يعمل / Projector Not Working (is_required: false, sort_order: 1)
- شاشة عرض تالفة / Damaged Display Screen (is_required: false, sort_order: 2)

**Audio & Video Systems:**
- مكبرات صوت لا تعمل / Speakers Not Working (is_required: false, sort_order: 1)
- نظام الفيديو لا يعمل / Video System Not Working (is_required: false, sort_order: 2)

**E-Learning Devices:**
- جهاز لوحي لا يعمل / Tablet Not Working (is_required: false, sort_order: 1)
- مشكلة في منصة التعلم الإلكتروني / E-Learning Platform Issue (is_required: false, sort_order: 2)

---

## 4. Users

### 4.1 Fixed Named Users (create these with predictable credentials)

All passwords: `Password@123` (hashed with bcrypt)

#### SuperUser (is_super_user = true, is_tech = false)
- full_name: "أحمد الشمري" / Ahmed Al-Shammari
- email: admin@university.edu.sa
- employee_number: EMP-0001
- department: Information Technology
- location: Executive Admin Tower
- locale: ar
- email_verified_at: (set to now)
- No TechProfile needed (not is_tech)
- Grant all 22 permissions via permission_user

#### IT Manager (is_tech = true, all 22 permissions)
- full_name: "سارة القحطاني" / Sara Al-Qahtani
- email: itmanager@university.edu.sa
- employee_number: EMP-0002
- department: Information Technology
- location: Main Building
- locale: ar
- TechProfile: specialization="IT Management", job_title_ar="مدير تقنية المعلومات", job_title_en="IT Manager"
- Grant all 22 permissions

#### Approver (not is_tech, has escalation.approve + ticket.view-all + user.view-directory)
- full_name: "محمد العتيبي" / Mohammed Al-Otaibi
- email: approver@university.edu.sa
- employee_number: EMP-0003
- department: Administrative & Financial
- location: Executive Admin Tower
- locale: ar
- Grant permissions: escalation.approve, ticket.view-all, user.view-directory

#### Group Manager 1 — Technical Support (is_tech = true)
- full_name: "خالد الدوسري" / Khalid Al-Dossari
- email: gm.support@university.edu.sa
- employee_number: EMP-0010
- department: Information Technology
- location: Main Building
- locale: ar
- TechProfile: specialization="Hardware Support", job_title_ar="مدير مجموعة الدعم التقني", job_title_en="Technical Support Group Manager"
- Assign as manager of group "Technical Support"
- Grant permissions: ticket.view-all, ticket.assign, group.manage-members, user.view-directory
- Add to group_user for "Technical Support" group

#### Group Manager 2 — Infrastructure & Networks (is_tech = true)
- full_name: "نورة الزهراني" / Noura Al-Zahrani
- email: gm.infra@university.edu.sa
- employee_number: EMP-0011
- department: Information Technology
- location: North Wing
- locale: ar
- TechProfile: specialization="Network Infrastructure", job_title_ar="مدير مجموعة البنية التحتية", job_title_en="Infrastructure Group Manager"
- Assign as manager of group "Infrastructure & Networks"
- Grant permissions: ticket.view-all, ticket.assign, group.manage-members, user.view-directory
- Add to group_user for "Infrastructure & Networks" group

### 4.2 Technicians (6 records — is_tech = true)

All technicians: grant permissions `ticket.view-all` + `user.view-directory`

| full_name (ar)      | full_name (en)        | email                          | employee_number | department       | location     | group                    | specialization        |
|--------------------|-----------------------|-------------------------------|-----------------|------------------|--------------|--------------------------|-----------------------|
| فيصل الغامدي       | Faisal Al-Ghamdi      | tech1@university.edu.sa       | EMP-0020        | IT               | Main Building | Technical Support       | Hardware Repair       |
| ريم الحربي         | Reem Al-Harbi         | tech2@university.edu.sa       | EMP-0021        | IT               | North Wing   | Technical Support        | End User Support      |
| عبدالله المطيري    | Abdullah Al-Mutairi   | tech3@university.edu.sa       | EMP-0022        | IT               | Labs Complex  | Technical Support       | Printer & Peripherals |
| بندر القرني        | Bandar Al-Qarni       | tech4@university.edu.sa       | EMP-0023        | IT               | Main Building | Infrastructure & Networks | Network Admin      |
| هنا السبيعي        | Hana Al-Subai'i       | tech5@university.edu.sa       | EMP-0024        | IT               | North Wing   | Infrastructure & Networks | Server Admin       |
| عمر الشهري         | Omar Al-Shahri        | tech6@university.edu.sa       | EMP-0025        | IT               | South Wing   | Software & Systems       | Systems Admin        |

TechProfile for each:
- job_title_ar: "فني دعم تقني" / job_title_en: "IT Support Technician"
- promoted_at: (set to a date ~3 months ago)
- promoted_by: IT Manager user ID
- internal_notes: null

### 4.3 Regular Employees / Requesters (15 records — is_tech = false)

Spread across all 5 departments and 5 locations. Mix of ar/en locale preferences (80% ar, 20% en). All have `email_verified_at` set.

| full_name (ar)         | full_name (en)           | email                              | emp_no  | department                 | location      |
|-----------------------|--------------------------|------------------------------------|---------|----------------------------|---------------|
| مريم الصالح            | Mariam Al-Saleh          | mariam@university.edu.sa           | EMP-0030 | Academic Affairs          | Main Building  |
| تركي الرشيدي           | Turki Al-Rashidi         | turki@university.edu.sa            | EMP-0031 | Academic Affairs          | South Wing     |
| لمياء العنزي           | Lamia Al-Anzi            | lamia@university.edu.sa            | EMP-0032 | Administrative & Financial | Executive Admin Tower |
| سعد البقمي             | Saad Al-Baqami           | saad@university.edu.sa             | EMP-0033 | Administrative & Financial | Main Building  |
| شيماء الشمراني         | Shaima Al-Shamrani       | shaima@university.edu.sa           | EMP-0034 | Facilities & Services     | North Wing     |
| ماجد الجهني            | Majid Al-Juhani          | majid@university.edu.sa            | EMP-0035 | Facilities & Services     | South Wing     |
| نجلاء المالكي          | Najla Al-Maliki          | najla@university.edu.sa            | EMP-0036 | Library & Learning Resources | Main Building |
| وليد السهلي            | Walid Al-Suhli           | walid@university.edu.sa            | EMP-0037 | Library & Learning Resources | Labs Complex  |
| حنان الثبيتي           | Hanan Al-Thubaiti        | hanan@university.edu.sa            | EMP-0038 | Academic Affairs          | Labs Complex   |
| رامي الحازمي           | Rami Al-Hazmi            | rami@university.edu.sa             | EMP-0039 | Administrative & Financial | North Wing     |
| غادة المطرفي           | Ghada Al-Mutarfi         | ghada@university.edu.sa            | EMP-0040 | Information Technology    | Main Building  |
| يوسف القرعاوي          | Yousef Al-Qar'awi        | yousef@university.edu.sa           | EMP-0041 | Academic Affairs          | South Wing     |
| أسماء الزيد            | Asma Al-Zaid             | asma@university.edu.sa             | EMP-0042 | Facilities & Services     | Labs Complex   |
| حسين المحمدي           | Hussein Al-Muhammadi     | hussein@university.edu.sa          | EMP-0043 | Library & Learning Resources | North Wing   |
| دانة البريكي           | Dana Al-Baraiki          | dana@university.edu.sa             | EMP-0044 | Academic Affairs          | Executive Admin Tower |

---

## 5. Tickets — Scenario Sets

All tickets: `incident_origin = 'web'`. Display numbers follow TKT-0000001 format; update `ticket_counters.last_number` to match total ticket count after seeding.

### Scenario A: Fresh / Awaiting Assignment (5 tickets)

These tickets were just submitted and have no tech assigned yet. Priority is NULL.

**Ticket A-1**
- subject: "الطابعة في مكتب 204 لا تطبع"
- description: (HTML paragraph) "الطابعة HP LaserJet في مكتب رقم 204 في المبنى الجنوبي توقفت عن الطباعة منذ الصباح. تظهر رسالة خطأ 'Paper Jam' رغم عدم وجود أي ورقة عالقة."
- status: awaiting_assignment
- priority: NULL
- category: Printing & Scanning
- subcategory: Printer Not Printing
- group: Technical Support
- assigned_to: NULL
- requester: مريم الصالح
- location: South Wing
- department: Academic Affairs

**Ticket A-2**
- subject: "شاشة الحاسوب في قاعة 101 لا تعمل"
- description: "شاشة الحاسوب في قاعة المحاضرات 101 لا تعمل منذ بداية اليوم. جُربت إعادة التشغيل دون جدوى."
- status: awaiting_assignment
- priority: NULL
- category: Computers & Peripherals
- subcategory: Damaged or Non-functional Screen
- group: Technical Support
- assigned_to: NULL
- requester: تركي الرشيدي
- location: Main Building
- department: Academic Affairs

**Ticket A-3**
- subject: "لا يمكن الوصول إلى شبكة الإنترنت في الجناح الشمالي"
- description: "منذ ساعتين وجميع أجهزة قسمنا في الجناح الشمالي غير قادرة على الاتصال بالإنترنت. الشبكة الداخلية تعمل بشكل طبيعي لكن الإنترنت الخارجي منقطع."
- status: awaiting_assignment
- priority: NULL
- category: Network & Internet
- subcategory: Internet Outage
- group: Infrastructure & Networks
- assigned_to: NULL
- requester: شيماء الشمراني
- location: North Wing
- department: Facilities & Services

**Ticket A-4**
- subject: "طلب تثبيت برنامج Microsoft Office على جهازي"
- description: "أحتاج إلى تثبيت Microsoft Office 365 على جهازي الجديد. لم يتم تثبيته عند الإعداد الأولي."
- status: awaiting_assignment
- priority: NULL
- category: OS & Software Installation
- subcategory: Software Installation
- group: Software & Systems
- assigned_to: NULL
- requester: لمياء العنزي
- location: Executive Admin Tower
- department: Administrative & Financial

**Ticket A-5**
- subject: "جهاز العرض في قاعة المؤتمرات لا يتصل بالكمبيوتر"
- description: "جهاز البروجيكتور في قاعة المؤتمرات الرئيسية لا يستجيب عند توصيله بأي جهاز كمبيوتر. تم التحقق من الكابلات وكلها سليمة."
- status: awaiting_assignment
- priority: NULL
- category: Projectors & Displays
- subcategory: Projector Not Working
- group: AV & Educational Tech
- assigned_to: NULL
- requester: نجلاء المالكي
- location: Main Building
- department: Library & Learning Resources

### Scenario B: In Progress (8 tickets)

Assigned to techs, SLA clock running. Mix of priorities and categories. Some have public + internal comments.

**Ticket B-1**
- subject: "بطء شديد في الشبكة في مختبر الحاسوب"
- description: "سرعة الشبكة في مختبر الحاسوب رقم 3 بطيئة جداً خلال ساعات الذروة. المشكلة تؤثر على جميع الطلاب."
- status: in_progress
- priority: high
- category: Network & Internet
- subcategory: Slow Network
- group: Infrastructure & Networks
- assigned_to: بندر القرني (tech4)
- requester: حنان الثبيتي
- location: Labs Complex
- department: Academic Affairs
- Comments:
  - (internal, by tech4): "تم الفحص الأولي، يبدو أن مفتاح الشبكة overloaded. سيتم فحص إعدادات QoS."
  - (public, by tech4): "تم استلام طلبك وبدأنا بالتحقيق في المشكلة. سنعود بتحديث قريباً."

**Ticket B-2**
- subject: "الحساب مقفل ولا أستطيع الدخول"
- description: "تم قفل حسابي في نظام الجامعة عند محاولة تسجيل الدخول اليوم. أحتاج فك القفل بشكل عاجل لأن لدي اجتماع مهم."
- status: in_progress
- priority: critical
- category: Security & Account Access
- subcategory: Account Locked
- group: Software & Systems
- assigned_to: عمر الشهري (tech6)
- requester: رامي الحازمي
- location: North Wing
- department: Administrative & Financial
- Comments:
  - (internal, by tech6): "تم التحقق من الحساب. محاولات فاشلة متعددة. سيتم إعادة تعيين كلمة المرور وفتح القفل."
  - (public, by tech6): "جاري العمل على فك قفل حسابك. ستتلقى رسالة إلكترونية فور الانتهاء."

**Ticket B-3**
- subject: "طلب نقطة وصول لاسلكية لقاعة الاجتماعات"
- description: "قاعة الاجتماعات في الطابق الثالث لا تغطيها شبكة الواي فاي. نحتاج إضافة نقطة وصول جديدة قبل نهاية الأسبوع."
- status: in_progress
- priority: medium
- category: Network & Internet
- subcategory: Wireless Access Point Request
- group: Infrastructure & Networks
- assigned_to: هنا السبيعي (tech5)
- requester: سعد البقمي
- location: Main Building
- department: Administrative & Financial
- Comments:
  - (internal, by tech5): "تم الكشف على الموقع. يحتاج كابل Cat6 جديد بطول 15 متر. قدّر التنفيذ يومين."

**Ticket B-4**
- subject: "الطابعة المشتركة في الطابق الثاني لا تستجيب"
- description: "الطابعة Canon المشتركة في ممر الطابق الثاني لا تستجيب لأي طلبات طباعة منذ الأمس."
- status: in_progress
- priority: medium
- category: Printing & Scanning
- subcategory: Printer Not Printing
- group: Technical Support
- assigned_to: عبدالله المطيري (tech3)
- requester: وليد السهلي
- location: Main Building
- department: Library & Learning Resources
- Comments:
  - (public, by tech3): "تم زيارة الموقع. المشكلة في إعدادات الشبكة للطابعة. سيتم إصلاحها اليوم."

**Ticket B-5**
- subject: "جهاز الحاسوب لا يتعرف على القرص الصلب"
- description: "عند تشغيل الجهاز يظهر خطأ 'No Boot Device Found'. يبدو أن القرص الصلب تالف."
- status: in_progress
- priority: high
- category: Computers & Peripherals
- subcategory: Device Failure
- group: Technical Support
- assigned_to: فيصل الغامدي (tech1)
- requester: ماجد الجهني
- location: South Wing
- department: Facilities & Services
- Comments:
  - (internal, by tech1): "تم تشخيص المشكلة: القرص الصلب تالف بالكامل. يحتاج استبدال. سيتم طلب قطعة غيار."

**Ticket B-6**
- subject: "مشكلة في نظام إدارة التعلم Moodle"
- description: "لا يمكنني رفع الملفات على منصة Moodle. تظهر رسالة خطأ 'Upload Failed' مع كل محاولة."
- status: in_progress
- priority: medium
- category: Admin & Academic Systems
- subcategory: Academic Management System Issue
- group: Software & Systems
- assigned_to: عمر الشهري (tech6)
- requester: مريم الصالح
- location: Main Building
- department: Academic Affairs

**Ticket B-7**
- subject: "مكبرات الصوت في قاعة المحاضرات 205 لا تعمل"
- description: "مكبرات الصوت في قاعة 205 صامتة تماماً. تم التحقق من مستوى الصوت وإعدادات الجهاز والمشكلة مستمرة."
- status: in_progress
- priority: low
- category: Audio & Video Systems
- subcategory: Speakers Not Working
- group: AV & Educational Tech
- assigned_to: ريم الحربي (tech2)
- requester: دانة البريكي
- location: Main Building
- department: Academic Affairs

**Ticket B-8**
- subject: "طلب صلاحيات وصول لنظام الميزانية"
- description: "بموجب توجيهات المدير، أحتاج إلى صلاحية قراءة في نظام الميزانية الجديد لإعداد التقارير الفصلية."
- status: in_progress
- priority: low
- category: Admin & Academic Systems
- subcategory: Access Permission Request
- group: Software & Systems
- assigned_to: عمر الشهري (tech6)
- requester: غادة المطرفي
- location: Main Building
- department: Information Technology

### Scenario C: On Hold (3 tickets)

SLA clock paused. Each has a pause log and a comment explaining the hold.

**Ticket C-1**
- subject: "خادم قاعدة البيانات يتعطل بشكل متكرر"
- description: "خادم قاعدة البيانات الرئيسي يُعيد تشغيل نفسه كل 4-6 ساعات تقريباً. المشكلة بدأت منذ 3 أيام."
- status: on_hold
- priority: critical
- category: Servers & Storage
- subcategory: Server Not Responding
- group: Infrastructure & Networks
- assigned_to: هنا السبيعي (tech5)
- requester: سعد البقمي
- location: Main Building
- department: Administrative & Financial
- Comments:
  - (public, by tech5): "تم تحليل السجلات. المشكلة محتملة في وحدة الذاكرة RAM. في انتظار وصول قطع الغيار من المورد."
  - (internal, by tech5): "طلب الشراء مرفوع، ETA يومين عمل. التذكرة ستُستأنف عند استلام القطع."
- Pause log: paused_at = (created_at + 2 hours), resumed_at = NULL, pause_status = "on_hold", duration_minutes = NULL

**Ticket C-2**
- subject: "UPS في غرفة الخوادم لا يعمل"
- description: "جهاز UPS في غرفة الخوادم في الطابق السفلي يُصدر صوت إنذار ويبدو أن البطارية منتهية."
- status: on_hold
- priority: high
- category: Power & Electrical Systems
- subcategory: UPS Not Working
- group: Infrastructure & Networks
- assigned_to: بندر القرني (tech4)
- requester: حسين المحمدي
- location: North Wing
- department: Library & Learning Resources
- Comments:
  - (public, by tech4): "تم فحص الجهاز. يحتاج بطارية جديدة. بانتظار الموافقة على طلب الشراء."
  - (internal, by tech4): "طلب شراء رقم PO-2024-0312 مرفوع للإدارة المالية."
- Pause log: paused_at = (created_at + 1 hour), resumed_at = NULL, pause_status = "on_hold", duration_minutes = NULL

**Ticket C-3**
- subject: "فيروس على جهاز موظف قسم المالية"
- description: "جهاز الحاسوب الخاص بي يتصرف بشكل غريب ويفتح نوافذ وإعلانات دون إذن. أعتقد أن هناك فيروساً."
- status: on_hold
- priority: high
- category: Security & Account Access
- subcategory: Suspected Security Breach
- group: Software & Systems
- assigned_to: عمر الشهري (tech6)
- requester: لمياء العنزي
- location: Executive Admin Tower
- department: Administrative & Financial
- Comments:
  - (public, by tech6): "تم عزل الجهاز عن الشبكة احترازياً. جاري الفحص الكامل. سيستغرق ذلك بعض الوقت."
  - (internal, by tech6): "تم اكتشاف Trojan. الجهاز بحاجة لإعادة تهيئة كاملة. في انتظار موافقة المستخدم على مسح البيانات."
- Pause log: paused_at = (created_at + 30 minutes), resumed_at = NULL, pause_status = "on_hold", duration_minutes = NULL

### Scenario D: Full Escalation Flow (3 complete chains)

**Ticket D-1: Awaiting Approval (condition report submitted, pending review)**
- subject: "تلف كامل في وحدة إمداد الطاقة للخادم"
- description: "وحدة الطاقة PSU في الخادم الرئيسي احترقت وتحتاج إلى استبدال عاجل بقطعة متخصصة."
- status: awaiting_approval
- priority: critical
- category: Servers & Storage
- subcategory: Server Not Responding
- group: Infrastructure & Networks
- assigned_to: هنا السبيعي (tech5)
- requester: سعد البقمي
- location: Main Building

ConditionReport for D-1:
- report_type: "hardware"
- report_date: (today - 1 day)
- current_condition: "وحدة إمداد الطاقة (PSU) تالفة بالكامل بسبب ارتفاع مفاجئ في الجهد الكهربائي. الخادم غير قادر على التشغيل."
- condition_analysis: "فحص شامل أثبت أن دائرة PSU محترقة. لا يمكن إصلاحها ويلزم استبدالها بقطعة أصلية من المورد."
- required_action: "شراء وتركيب وحدة PSU جديدة من المورد المعتمد (Dell PowerEdge). تقدير التكلفة: 1,200 ريال."
- tech_id: هنا السبيعي (tech5)
- status: pending
- reviewed_by: NULL
- review_notes: NULL
- Comments (on ticket):
  - (internal, by tech5): "تم إعداد تقرير الحالة. في انتظار موافقة المختص."

**Ticket D-2: Action Required (condition report approved, maintenance request generated)**
- subject: "مفتاح شبكة رئيسي تالف — انقطاع كامل للشبكة في مبنى المختبرات"
- description: "مفتاح الشبكة الرئيسي في مجمع المختبرات تعطل مسبباً انقطاعاً كاملاً للشبكة في المبنى بأكمله."
- status: action_required
- priority: critical
- category: Network & Internet
- subcategory: Internet Outage
- group: Infrastructure & Networks
- assigned_to: بندر القرني (tech4)
- requester: حنان الثبيتي
- location: Labs Complex

ConditionReport for D-2:
- report_type: "network"
- report_date: (today - 3 days)
- current_condition: "مفتاح الشبكة الرئيسي (Core Switch) بالمختبرات تالف بسبب عطل في البورد الداخلي. 200+ جهاز متأثر."
- condition_analysis: "تشخيص مفصل أكد تلف الدائرة الرئيسية. المفتاح Cisco Catalyst 2960X. لا يمكن الإصلاح الجزئي."
- required_action: "استبدال فوري بمفتاح Cisco Catalyst 2960X جديد. التكلفة التقديرية: 8,500 ريال. يستلزم موافقة إدارية رسمية."
- tech_id: بندر القرني (tech4)
- status: approved
- reviewed_by: محمد العتيبي (approver)
- reviewed_at: (today - 2 days)
- review_notes: "تمت الموافقة. يُرجى إعداد مستندات الصيانة وإرسالها لمقدم الطلب لتوقيعها."

MaintenanceRequest for D-2:
- generated_file_path: "maintenance/{ulid}.pdf"
- generated_locale: "ar"
- submitted_file_path: NULL
- submitted_at: NULL
- status: pending
- reviewed_by: NULL
- rejection_count: 0
- Comments (on ticket):
  - (public, by system/approver): "تمت الموافقة على تقرير الحالة. يرجى تنزيل مستند الصيانة وتوقيعه ورفعه مجدداً."

**Ticket D-3: Awaiting Final Approval (requester uploaded signed doc)**
- subject: "تلف في نظام التبريد لغرفة الخوادم"
- description: "نظام تكييف غرفة الخوادم توقف عن العمل ودرجات الحرارة ترتفع بشكل مقلق."
- status: awaiting_final_approval
- priority: high
- category: Power & Electrical Systems
- subcategory: UPS Not Working
- group: Infrastructure & Networks
- assigned_to: بندر القرني (tech4)
- requester: سعد البقمي
- location: Main Building

ConditionReport for D-3:
- report_type: "hardware"
- status: approved
- reviewed_by: محمد العتيبي (approver)
- reviewed_at: (today - 5 days)
- current_condition: "وحدة التكييف الخاصة بغرفة الخوادم تعطلت بالكامل. درجة الحرارة وصلت 42 درجة مئوية وهي خطرة."
- condition_analysis: "تعطل ضاغط الكمبريسور الرئيسي. الجهاز قديم (2016) وتكلفة إصلاحه أعلى من 70% من قيمة جهاز جديد."
- required_action: "استبدال وحدة التكييف بالكامل بوحدة Samsung 5-طن متخصصة للغرف التقنية. التكلفة: 22,000 ريال."

MaintenanceRequest for D-3:
- generated_file_path: "maintenance/{ulid}.pdf"
- generated_locale: "ar"
- submitted_file_path: "maintenance/signed/{ulid}.pdf"
- submitted_at: (today - 1 day)
- status: submitted
- reviewed_by: NULL
- rejection_count: 0
- Comments (on ticket):
  - (public, by requester سعد البقمي): "تم رفع المستند الموقع."

### Scenario E: Terminal States (10 tickets)

**Resolved Tickets (4)**

**Ticket E-1: Resolved, CSAT submitted (rating 5)**
- subject: "استعادة الملفات المحذوفة من مجلد المشاركة"
- description: "تم حذف مجلد مهم بشكل غير مقصود من مجلد الشبكة المشترك. أحتاج استعادته."
- status: resolved
- priority: medium
- category: Servers & Storage
- subcategory: Storage Full
- group: Infrastructure & Networks
- assigned_to: هنا السبيعي (tech5)
- requester: وليد السهلي
- resolved_at: (today - 3 days)
- Comments:
  - (public, by tech5): "تم استعادة المجلد بالكامل من النسخ الاحتياطية. تأكد من البيانات وأخبرني إن وجدت أي نقص."
CsatRating: status=submitted, rating=5, submitted_at=(today - 2 days), comment="خدمة ممتازة وسريعة، شكراً جزيلاً"

**Ticket E-2: Resolved, CSAT submitted (rating 3)**
- subject: "تحديث نظام Windows على أجهزة قسم المكتبة"
- description: "تحديث Windows 11 فشل على 5 أجهزة في قسم المكتبة وتظهر أخطاء متعددة."
- status: resolved
- priority: low
- category: OS & Software Installation
- subcategory: OS Update
- group: Software & Systems
- assigned_to: عمر الشهري (tech6)
- requester: نجلاء المالكي
- resolved_at: (today - 10 days)
CsatRating: status=submitted, rating=3, submitted_at=(today - 9 days), comment="تم الحل لكن استغرق وقتاً طويلاً"

**Ticket E-3: Resolved, CSAT pending (not yet submitted)**
- subject: "إعادة ضبط كلمة مرور Wi-Fi للضيوف"
- description: "كلمة مرور شبكة الواي فاي الخاصة بالضيوف انتهت صلاحيتها. أحتاج تجديدها قبل حفل التخرج غداً."
- status: resolved
- priority: high
- category: Network & Internet
- subcategory: Cannot Connect to Network
- group: Infrastructure & Networks
- assigned_to: بندر القرني (tech4)
- requester: دانة البريكي
- resolved_at: (today - 1 day)
CsatRating: status=pending, expires_at=(today + 6 days), rating=NULL, submitted_at=NULL

**Ticket E-4: Resolved, CSAT expired, SLA breached**
- subject: "انقطاع الكهرباء الكامل عن مبنى الجناح الجنوبي"
- description: "انقطعت الكهرباء عن مبنى الجناح الجنوبي بالكامل منذ الصباح. عدد كبير من الموظفين متأثرون."
- status: resolved
- priority: critical
- category: Power & Electrical Systems
- subcategory: Power Outage in Section
- group: Infrastructure & Networks
- assigned_to: بندر القرني (tech4)
- requester: ماجد الجهني
- resolved_at: (today - 20 days)
- created_at: (today - 22 days)
CsatRating: status=expired, expires_at=(today - 13 days), rating=NULL
TicketSla: resolution_status=breached, response_status=on_track (response was met in 10 min, resolution breached by 3 hours)

**Closed Tickets (3)**

**Ticket E-5: Closed — reason: duplicate**
- subject: "الإنترنت بطيء في الجناح الشمالي"
- description: "سرعة الإنترنت في قسمنا بالجناح الشمالي شبه معدومة منذ الأمس."
- status: closed
- priority: medium
- category: Network & Internet
- subcategory: Slow Network
- group: Infrastructure & Networks
- assigned_to: NULL
- requester: شيماء الشمراني
- close_reason: duplicate
- close_reason_text: NULL
- closed_at: (today - 5 days)

**Ticket E-6: Closed — reason: requester_unresponsive**
- subject: "مشكلة في طباعة المستندات"
- description: "لا أستطيع طباعة أي مستند. المشكلة بدأت منذ أسبوع."
- status: closed
- priority: low
- category: Printing & Scanning
- subcategory: Printer Not Printing
- group: Technical Support
- assigned_to: عبدالله المطيري (tech3)
- close_reason: requester_unresponsive
- close_reason_text: NULL
- closed_at: (today - 8 days)
- Comments:
  - (public, by tech3): "حاولنا التواصل معك 3 مرات دون رد. سيتم إغلاق التذكرة."

**Ticket E-7: Closed — reason: other (with close_reason_text)**
- subject: "طلب تركيب كاميرا مراقبة في ممر الطابق الثالث"
- description: "يرجى تركيب كاميرا مراقبة إضافية في ممر الطابق الثالث بمبنى الإدارة."
- status: closed
- priority: low
- category: Computers & Peripherals
- subcategory: Device Replacement Request
- group: Technical Support
- assigned_to: NULL
- close_reason: other
- close_reason_text: "طلب تركيب كاميرات المراقبة يقع ضمن اختصاص قسم الأمن وليس تقنية المعلومات. يُرجى التواصل مع إدارة الأمن والسلامة مباشرةً."
- closed_at: (today - 12 days)

**Cancelled Tickets (2)**

**Ticket E-8: Cancelled by requester**
- subject: "تثبيت برنامج AutoCAD"
- description: "أحتاج تثبيت برنامج AutoCAD 2024 على جهازي لمشروع طارئ."
- status: cancelled
- priority: NULL
- category: OS & Software Installation
- subcategory: Software Installation
- group: Software & Systems
- assigned_to: NULL
- requester: يوسف القرعاوي
- cancelled_at: (today - 7 days)
- Comments:
  - (public, by requester): "تم إلغاء الطلب، تمكنت من استخدام جهاز زميلي."

**Ticket E-9: Cancelled by requester after assignment**
- subject: "مشكلة في عدم قبول كلمة المرور"
- description: "كلمة المرور الجديدة التي تم إنشاؤها بعد إعادة التعيين لا تعمل."
- status: cancelled
- priority: medium
- category: Security & Account Access
- subcategory: Forgotten Password
- group: Software & Systems
- assigned_to: عمر الشهري (tech6)
- requester: أسماء الزيد
- cancelled_at: (today - 4 days)
- Comments:
  - (public, by requester): "حُلّت المشكلة بمفردي، شكراً."

### Scenario F: Transfer Requests (3 tickets)

**Ticket F-1: Pending Transfer Request**
- subject: "لوحة مفاتيح معطوبة وتكتب حروف غلط"
- description: "عدة مفاتيح على لوحة المفاتيح تكتب حروفاً غير صحيحة، مما يصعّل كتابة كلمة المرور."
- status: in_progress
- priority: low
- category: Computers & Peripherals
- subcategory: Keyboard or Mouse Issue
- group: Technical Support
- assigned_to: فيصل الغامدي (tech1)
- requester: تركي الرشيدي
TransferRequest: from_user=tech1 (فيصل), to_user=tech2 (ريم), status=pending, responded_at=NULL

**Ticket F-2: Accepted Transfer (reassigned)**
- subject: "ماسح ضوئي لا يتعرف على الأوراق"
- description: "الماسح الضوئي في مكتب السكرتارية لا يتعرف على الأوراق الموضوعة فيه."
- status: in_progress
- priority: medium
- category: Printing & Scanning
- subcategory: Scanner Not Working
- group: Technical Support
- assigned_to: ريم الحربي (tech2) ← (was tech3, transferred to tech2)
- requester: غادة المطرفي
TransferRequest: from_user=tech3 (عبدالله), to_user=tech2 (ريم), status=accepted, responded_at=(today - 1 day)

**Ticket F-3: Rejected Transfer (original tech still owns it)**
- subject: "هاتف المكتب لا يُصدر رنيناً عند الاستقبال"
- description: "هاتف المكتب يُظهر المكالمات الواردة على الشاشة لكن لا يُصدر أي صوت."
- status: in_progress
- priority: low
- category: Phones & Communication
- subcategory: Internal Phone Not Working
- group: Technical Support
- assigned_to: ريم الحربي (tech2) ← (original, transfer was rejected)
- requester: حسين المحمدي
TransferRequest: from_user=tech2 (ريم), to_user=tech3 (عبدالله), status=rejected, responded_at=(today - 2 days)

---

## 6. SLA Records

Create one `ticket_sla` record per ticket. Rules:
- Tickets with status `awaiting_assignment` or `in_progress`: `is_clock_running = true`
- Tickets with status `on_hold`, `awaiting_approval`, `action_required`, `awaiting_final_approval`: `is_clock_running = false`
- Terminal status (`resolved`, `closed`, `cancelled`): `is_clock_running = false`

Target values come from `sla_policies` matching the ticket's priority. NULL priority = no targets (response_target_minutes = NULL, resolution_target_minutes = NULL).

| Ticket | Priority | response_status | resolution_status | Notes |
|--------|----------|----------------|------------------|-------|
| A-1    | NULL     | on_track       | on_track         | No SLA targets |
| A-2    | NULL     | on_track       | on_track         | No SLA targets |
| A-3    | NULL     | on_track       | on_track         | No SLA targets |
| A-4    | NULL     | on_track       | on_track         | No SLA targets |
| A-5    | NULL     | on_track       | on_track         | No SLA targets |
| B-1    | high     | on_track       | on_track         | Clock running, created 4 hours ago |
| B-2    | critical | on_track       | on_track         | Clock running, created 30 min ago |
| B-3    | medium   | on_track       | on_track         | Clock running, created 2 hours ago |
| B-4    | medium   | on_track       | warning          | Clock running, 80% of resolution target elapsed |
| B-5    | high     | on_track       | warning          | Clock running, getting close |
| B-6    | medium   | on_track       | on_track         | Clock running |
| B-7    | low      | on_track       | on_track         | Clock running |
| B-8    | low      | on_track       | on_track         | Clock running |
| C-1    | critical | on_track       | warning          | Clock paused (on_hold) |
| C-2    | high     | on_track       | on_track         | Clock paused (on_hold) |
| C-3    | high     | on_track       | on_track         | Clock paused (on_hold) |
| D-1    | critical | on_track       | warning          | Clock paused (awaiting_approval) |
| D-2    | critical | on_track       | breached         | Clock paused, breach happened during escalation |
| D-3    | high     | on_track       | warning          | Clock paused (awaiting_final_approval) |
| E-1    | medium   | on_track       | on_track         | Resolved; final elapsed stored |
| E-2    | low      | on_track       | on_track         | Resolved |
| E-3    | high     | on_track       | on_track         | Resolved quickly |
| E-4    | critical | on_track       | breached         | Resolved late — use for breach reporting tests |
| E-5    | medium   | on_track       | on_track         | Closed |
| E-6    | low      | on_track       | on_track         | Closed |
| E-7    | low      | on_track       | on_track         | Closed |
| E-8    | NULL     | on_track       | on_track         | Cancelled |
| E-9    | medium   | on_track       | on_track         | Cancelled |
| F-1    | low      | on_track       | on_track         | In progress |
| F-2    | medium   | on_track       | on_track         | In progress |
| F-3    | low      | on_track       | on_track         | In progress |

**SLA Pause Logs** — create for all tickets that were paused:
- Tickets C-1, C-2, C-3: one pause log each (no resumed_at — still paused)
- Ticket D-1: one pause log (paused when status moved to awaiting_approval)
- Ticket D-2: two pause logs (first was on_hold then resumed; second when awaiting_approval then to action_required)
- Ticket D-3: two pause logs (action_required pause + awaiting_final_approval pause)

---

## 7. CSAT Records

Only for `resolved` tickets. Summary:

| Ticket | status    | rating | comment                              | expires_at     | submitted_at      |
|--------|-----------|--------|--------------------------------------|----------------|-------------------|
| E-1    | submitted | 5      | "خدمة ممتازة وسريعة، شكراً جزيلاً" | (today + 4 d)  | (today - 2 days)  |
| E-2    | submitted | 3      | "تم الحل لكن استغرق وقتاً طويلاً"  | (today - 3 d)  | (today - 9 days)  |
| E-3    | pending   | NULL   | NULL                                 | (today + 6 d)  | NULL              |
| E-4    | expired   | NULL   | NULL                                 | (today - 13 d) | NULL              |

---

## 8. Notification Logs

Create a representative set of notification log entries showing the full lifecycle. Status should be `sent` for all entries (simulating successful delivery).

| recipient        | type                      | ticket     | subject (en)                                     |
|-----------------|---------------------------|------------|--------------------------------------------------|
| مريم الصالح      | ticket_created            | A-1        | Your ticket TKT-0000001 has been received        |
| حنان الثبيتي     | ticket_created            | B-1        | Your ticket TKT-0000006 has been received        |
| بندر القرني      | ticket_assigned           | B-1        | New ticket assigned to you: TKT-0000006          |
| رامي الحازمي     | ticket_assigned_to_you    | B-2        | A technician has been assigned to your ticket    |
| وليد السهلي      | ticket_resolved           | E-1        | Your ticket TKT-0000020 has been resolved        |
| وليد السهلي      | csat_prompt               | E-1        | Please rate your experience with ticket E-1      |
| هنا السبيعي      | transfer_request          | F-1        | Transfer request for ticket TKT-0000029          |
| فيصل الغامدي     | transfer_request_result   | F-1        | Your transfer request is pending acceptance      |
| سعد البقمي       | escalation_approved       | D-2        | Action required: Please sign maintenance form    |
| سعد البقمي       | final_doc_rejected        | D-3        | (if rejection scenario exists)                   |

---

## 9. Ticket Counters

After all tickets are created, update `ticket_counters` row where `id = 1`:
- Set `last_number` = total number of tickets seeded (32 in the scenarios above: 5+8+3+3+10+3)

This ensures future real tickets created via the app continue numbering from TKT-0000033 onwards.

---

## 10. Implementation Notes for the Seeding Developer

### Critical Rules

1. **Never call TicketStateMachine from seeders.** Use direct DB inserts or `Ticket::create(['status' => TicketStatus::InProgress])` bypassing the state machine. Seeders represent already-happened historical data.

2. **Display numbers must be generated manually** by incrementing a counter variable and formatting as `sprintf('TKT-%07d', $i)`. Do NOT rely on the production `ticket_counters` lock-based mechanism.

3. **ULIDs for all IDs.** Use `(string) Str::ulid()` or rely on Eloquent `HasUlids` which auto-generates on create.

4. **SLA elapsed minutes** for terminal tickets should be realistic:
   - Resolved tickets: set `resolution_elapsed_minutes` to reflect actual time-to-resolve
   - Breached ticket (E-4): set `resolution_elapsed_minutes` to `resolution_target_minutes + 180` (3 hours over)

5. **File paths for attachments** are placeholder strings — no actual files need to exist:
   - Ticket attachments: `"tickets/{ticketId}/{ulid}.pdf"`
   - Condition report attachments: `"escalation/{reportId}/{ulid}.jpg"`
   - Maintenance request files: `"maintenance/{ulid}.pdf"`, `"maintenance/signed/{ulid}.pdf"`

6. **`email_verified_at`** should be set for all seeded users (use `now()` or a fixed past date).

7. **`promoted_at` and `promoted_by`** in TechProfiles: set `promoted_at` to ~3 months ago, `promoted_by` to the IT Manager user's ID. Seed IT Manager first.

8. **Permission assignment order**: Create the `permission_user` pivot entries AFTER all permissions and users are created. Use the permission `key` values to look up the permission IDs dynamically.

9. **`group_user` pivot**: Assign each tech to their group after both groups and users are created. Group managers are also `group_user` members (they are part of their group).

10. **Locale**: Set Arabic users to `locale = 'ar'`, set 2-3 English-preferring users to `locale = 'en'` among the regular employees.

### Suggested Seeder Class Structure

```
DatabaseSeeder
  → PermissionSeeder        (existing)
  → ReferenceDataSeeder     (departments, locations, SLA policies, response templates)
  → GroupCategorySeeder     (groups, categories, subcategories)
  → UserSeeder              (users, tech profiles, group memberships, permissions)
  → TicketSeeder            (all 32 tickets + SLA + pause logs + comments + transfers + escalation + CSAT + notifications)
```

The `TicketSeeder` should be one class that runs all sub-scenarios in order (A through F), then updates `ticket_counters` at the end.

### Factory States to Use

All factories already exist with the following states — use them rather than raw arrays:

- `UserFactory`: `tech()`, `superUser()`
- `TicketFactory`: `inProgress()`, `resolved()`, `closed()`
- `CommentFactory`: `public()`, `internal()`
- `CsatRatingFactory`: `submitted()`, `expired()`
- `TicketSlaFactory`: `paused()`, `warning()`, `breached()`
- `SlaPauseLogFactory`: `resumed()`
- `MaintenanceRequestFactory`: `submitted()`, `approved()`, `rejected()`
- `ConditionReportFactory`: `approved()`, `rejected()`
- `TransferRequestFactory`: `accepted()`, `rejected()`, `revoked()`

For scenarios that need custom data beyond what factory states provide, pass attribute overrides directly to `factory()->create([...])`.
