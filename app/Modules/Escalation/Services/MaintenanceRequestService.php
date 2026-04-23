<?php

namespace App\Modules\Escalation\Services;

use App\Modules\Escalation\Models\ConditionReport;
use App\Modules\Escalation\Models\MaintenanceRequest;
use App\Modules\Tickets\Models\Ticket;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use PhpOffice\PhpWord\Element\Section;
use PhpOffice\PhpWord\IOFactory;
use PhpOffice\PhpWord\PhpWord;

// Cross-module Ticket import is permitted in Escalation services (designated seam).
class MaintenanceRequestService
{
    private const DISCLAIMER_AR = 'بناءً على البيانات أعلاه، تخلي وحدة الدعم الفني مسؤوليتها بالكامل عن أي إجراءات ناتجة. كما تحتفظ الوحدة بالصلاحية الكاملة لاتخاذ أي إجراءات تراها مناسبة.';
    private const DISCLAIMER_EN = 'Based on the above data, the Technical Support Unit fully disclaims responsibility for any resulting actions. Additionally, the Unit reserves full authority to take any necessary measures it deems appropriate.';

    public function generate(string $ticketUlid, string $locale): MaintenanceRequest
    {
        if (! in_array($locale, ['ar', 'en'], true)) {
            throw new \InvalidArgumentException("Invalid locale '{$locale}'. Must be 'ar' or 'en'.");
        }

        $ticket = Ticket::withoutGlobalScopes()
            ->with(['requester.department', 'requester.location', 'category', 'subcategory', 'assignedTo'])
            ->findOrFail($ticketUlid);

        $conditionReport = ConditionReport::where('ticket_id', $ticketUlid)
            ->where('status', 'approved')
            ->with('tech')
            ->latest()
            ->first();

        $docx    = $this->buildDocument($ticket, $conditionReport, $locale);
        $content = $this->renderToString($docx);

        $filename     = strtolower((string) Str::ulid()) . '.docx';
        $storagePath  = "maintenance-requests/{$ticketUlid}/{$filename}";

        Storage::disk('local')->put($storagePath, $content);

        $maintenanceRequest = MaintenanceRequest::where('ticket_id', $ticketUlid)->first();

        if ($maintenanceRequest) {
            $maintenanceRequest->update([
                'generated_file_path' => $storagePath,
                'generated_locale'    => $locale,
            ]);
        } else {
            $maintenanceRequest = MaintenanceRequest::create([
                'ticket_id'           => $ticketUlid,
                'generated_file_path' => $storagePath,
                'generated_locale'    => $locale,
                'status'              => 'pending',
            ]);
        }

        return $maintenanceRequest->fresh();
    }

    private function buildDocument(Ticket $ticket, ?ConditionReport $conditionReport, string $locale): PhpWord
    {
        $isRtl   = $locale === 'ar';
        $font    = $isRtl ? ['rtl' => true] : [];
        $parBase = ['alignment' => $isRtl ? 'right' : 'left', 'bidi' => $isRtl];

        $phpWord = new PhpWord();
        $section  = $phpWord->addSection();

        // Company header from tenant config
        $companyName = $this->getAppSetting('company_name');
        if ($companyName) {
            $section->addText(
                $companyName,
                array_merge($font, ['bold' => true, 'size' => 14]),
                array_merge($parBase, ['alignment' => 'center'])
            );
        }

        // Document title
        $title = $isRtl ? 'طلب الصيانة' : 'Maintenance Request';
        $section->addText(
            $title,
            array_merge($font, ['bold' => true, 'size' => 16]),
            array_merge($parBase, ['alignment' => 'center'])
        );
        $section->addTextBreak(1);

        // Ticket information
        $categoryName    = $ticket->category ? ($isRtl ? $ticket->category->name_ar : $ticket->category->name_en) : '';
        $subcategoryName = $ticket->subcategory ? ($isRtl ? $ticket->subcategory->name_ar : $ticket->subcategory->name_en) : '';

        $this->addLabeledSection($section, $isRtl ? 'معلومات التذكرة' : 'Ticket Information', [
            ($isRtl ? 'رقم التذكرة'    : 'Ticket Number')  => $ticket->display_number,
            ($isRtl ? 'تاريخ الإنشاء'  : 'Creation Date')  => $ticket->created_at->format('Y-m-d'),
            ($isRtl ? 'الفئة'           : 'Category')       => $categoryName,
            ($isRtl ? 'الفئة الفرعية'   : 'Subcategory')    => $subcategoryName,
        ], $font, $parBase);

        // Requester information
        $requester    = $ticket->requester;
        $deptName     = $requester?->department ? ($isRtl ? $requester->department->name_ar : $requester->department->name_en) : '';
        $locationName = $requester?->location ? ($isRtl ? $requester->location->name_ar : $requester->location->name_en) : '';

        $this->addLabeledSection($section, $isRtl ? 'معلومات مقدم الطلب' : 'Requester Information', [
            ($isRtl ? 'الاسم الكامل'    : 'Full Name')       => $requester?->full_name ?? '',
            ($isRtl ? 'الرقم الوظيفي'   : 'Employee Number') => $requester?->employee_number ?? '',
            ($isRtl ? 'القسم'           : 'Department')      => $deptName,
            ($isRtl ? 'الموقع'          : 'Location')        => $locationName,
        ], $font, $parBase);

        // Issue description
        $section->addText(
            $isRtl ? 'وصف المشكلة' : 'Issue Description',
            array_merge($font, ['bold' => true, 'size' => 12]),
            $parBase
        );
        $section->addText($ticket->subject, array_merge($font, ['italic' => true]), $parBase);
        $section->addText(strip_tags($ticket->description), $font, $parBase);
        $section->addTextBreak(1);

        // Technical analysis from approved condition report
        if ($conditionReport) {
            $section->addText(
                $isRtl ? 'التحليل الفني' : 'Technical Analysis',
                array_merge($font, ['bold' => true, 'size' => 12]),
                $parBase
            );
            $this->addLabeledSection($section, null, [
                ($isRtl ? 'الفني المسؤول'   : 'Assigned Technician') => $conditionReport->tech?->full_name ?? '',
                ($isRtl ? 'الحالة الراهنة'  : 'Current Condition')   => strip_tags($conditionReport->current_condition),
                ($isRtl ? 'تحليل الحالة'    : 'Condition Analysis')  => strip_tags($conditionReport->condition_analysis),
                ($isRtl ? 'الإجراء المطلوب' : 'Required Action')     => strip_tags($conditionReport->required_action),
            ], $font, $parBase);
        }

        // Hardcoded bilingual disclaimer (§8.3.1 item 7)
        $section->addText(
            $isRtl ? 'إخلاء المسؤولية' : 'Disclaimer',
            array_merge($font, ['bold' => true, 'size' => 12]),
            $parBase
        );
        $section->addText($isRtl ? self::DISCLAIMER_AR : self::DISCLAIMER_EN, $font, $parBase);
        $section->addTextBreak(1);

        // Signature block
        $section->addText(
            $isRtl ? 'التوقيع' : 'Signature',
            array_merge($font, ['bold' => true, 'size' => 12]),
            $parBase
        );
        $section->addText(($isRtl ? 'اسم مقدم الطلب: ' : 'Requester Name: ') . ($requester?->full_name ?? ''), $font, $parBase);
        $section->addText($isRtl ? 'التوقيع: ____________________' : 'Signature: ____________________', $font, $parBase);
        $section->addText($isRtl ? 'التاريخ: ____________________' : 'Date: ____________________', $font, $parBase);

        return $phpWord;
    }

    private function addLabeledSection(
        Section $section,
        ?string $heading,
        array $rows,
        array $font,
        array $parStyle
    ): void {
        if ($heading !== null) {
            $section->addText(
                $heading,
                array_merge($font, ['bold' => true, 'size' => 12]),
                $parStyle
            );
        }
        foreach ($rows as $label => $value) {
            $section->addText("{$label}: {$value}", $font, $parStyle);
        }
        $section->addTextBreak(1);
    }

    private function renderToString(PhpWord $phpWord): string
    {
        $tempPath = tempnam(sys_get_temp_dir(), 'mreq_') . '.docx';
        $writer   = IOFactory::createWriter($phpWord, 'Word2007');
        $writer->save($tempPath);
        $content  = file_get_contents($tempPath);
        @unlink($tempPath);

        return $content;
    }

    private function getAppSetting(string $key): ?string
    {
        try {
            return DB::table('app_settings')->where('key', $key)->value('value');
        } catch (\Exception) {
            return null;
        }
    }
}
