<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('condition_report_attachments', function (Blueprint $table) {
            $table->char('id', 26)->primary();
            $table->char('condition_report_id', 26);
            $table->string('original_name');
            $table->string('file_path', 500);
            $table->unsignedInteger('file_size');
            $table->string('mime_type', 100);
            $table->timestamps();

            // FK index
            $table->index('condition_report_id');

            $table->foreign('condition_report_id')
                ->references('id')->on('condition_reports')
                ->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('condition_report_attachments');
    }
};
