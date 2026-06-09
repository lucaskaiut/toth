<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('knowledge_sources', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->string('type', 32);
            $table->string('title');
            $table->longText('content')->nullable();
            $table->json('metadata')->nullable();
            $table->string('status', 32)->default('pending');
            $table->timestamp('indexed_at')->nullable();
            $table->text('index_error')->nullable();
            $table->timestamps();

            $table->index(['company_id', 'type']);
            $table->index(['company_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('knowledge_sources');
    }
};
