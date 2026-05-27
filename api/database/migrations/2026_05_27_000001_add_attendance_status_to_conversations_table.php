<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('conversations', function (Blueprint $table) {
            $table->string('attendance_status')->default('ai_enabled')->after('summary');
            $table->index(['company_id', 'attendance_status']);
        });
    }

    public function down(): void
    {
        Schema::table('conversations', function (Blueprint $table) {
            $table->dropIndex(['company_id', 'attendance_status']);
            $table->dropColumn('attendance_status');
        });
    }
};
