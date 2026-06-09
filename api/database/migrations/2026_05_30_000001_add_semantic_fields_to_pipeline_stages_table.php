<?php

use App\Modules\Lead\Domain\Enums\DefaultPipelineStage;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('pipeline_stages', function (Blueprint $table) {
            $table->text('description')->nullable()->after('position');
            $table->text('ai_instruction')->nullable()->after('description');
        });

        $definitions = collect(DefaultPipelineStage::definitions())->keyBy('slug');

        foreach (DB::table('pipeline_stages')->orderBy('id')->get() as $row) {
            $definition = $definitions->get($row->slug);

            DB::table('pipeline_stages')->where('id', $row->id)->update([
                'description' => $definition['description'] ?? $row->name,
                'ai_instruction' => $definition['ai_instruction'] ?? null,
            ]);
        }

        Schema::table('pipeline_stages', function (Blueprint $table) {
            $table->text('description')->nullable(false)->change();
            $table->unique(['company_id', 'position']);
        });
    }

    public function down(): void
    {
        Schema::table('pipeline_stages', function (Blueprint $table) {
            $table->dropUnique(['company_id', 'position']);
            $table->dropColumn(['description', 'ai_instruction']);
        });
    }
};
