<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('knowledge_chunks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->foreignId('source_id')->constrained('knowledge_sources')->cascadeOnDelete();
            $table->unsignedInteger('chunk_index');
            $table->longText('content');
            $table->json('metadata')->nullable();
            $table->string('embedding_reference')->nullable();
            $table->timestamps();

            $table->unique(['source_id', 'chunk_index']);
            $table->index(['company_id', 'source_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('knowledge_chunks');
    }
};
