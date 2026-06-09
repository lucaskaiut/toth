<?php

use App\Http\Controllers\HealthController;
use App\Modules\Auth\Http\Controllers\LoginController;
use App\Modules\Auth\Http\Controllers\MeController;
use App\Modules\Auth\Http\Controllers\RegisterController;
use App\Modules\Company\Http\Controllers\CompanyWhatsAppSetupController;
use App\Modules\CompanyConfig\Http\Controllers\CompanyConfigController;
use App\Modules\Conversation\Http\Controllers\ConversationController;
use App\Modules\Lead\Http\Controllers\LeadController;
use App\Modules\Knowledge\Http\Controllers\KnowledgeSearchController;
use App\Modules\Knowledge\Http\Controllers\KnowledgeSourceController;
use App\Modules\Lead\Http\Controllers\PipelineStageController;
use App\Modules\Whatsapp\Http\Controllers\WhatsAppWebhookController;
use Illuminate\Support\Facades\Broadcast;
use Illuminate\Support\Facades\Route;

Route::get('/health', HealthController::class);

Route::post('/login', [LoginController::class, 'store']);
Route::post('/register', [RegisterController::class, 'store']);
Route::post('/webhooks/whatsapp', [WhatsAppWebhookController::class, 'store']);

Route::middleware(['auth:sanctum', 'company'])->group(function () {
    Broadcast::routes();

    Route::get('/me', [MeController::class, 'show']);

    Route::get('/company/whatsapp/connect', [CompanyWhatsAppSetupController::class, 'connect']);
    Route::get('/company/whatsapp/connection-state', [CompanyWhatsAppSetupController::class, 'connectionState']);

    Route::middleware(['company.active'])->group(function () {
    Route::get('/pipeline/stages', [PipelineStageController::class, 'index']);
    Route::post('/pipeline/stages', [PipelineStageController::class, 'store']);
    Route::patch('/pipeline/stages/reorder', [PipelineStageController::class, 'reorder']);
    Route::put('/pipeline/stages/{stage}', [PipelineStageController::class, 'update']);
    Route::delete('/pipeline/stages/{stage}', [PipelineStageController::class, 'destroy']);
    Route::get('/leads', [LeadController::class, 'index']);
    Route::get('/leads/{lead}', [LeadController::class, 'show']);
    Route::put('/leads/{lead}', [LeadController::class, 'update']);
    Route::patch('/leads/{lead}/stage', [LeadController::class, 'moveStage']);

    Route::get('/conversations', [ConversationController::class, 'index']);
    Route::get('/conversations/{conversation}', [ConversationController::class, 'show']);
    Route::get('/conversations/{conversation}/messages', [ConversationController::class, 'messages']);
    Route::post('/conversations/{conversation}/messages', [ConversationController::class, 'sendMessage']);
    Route::patch('/conversations/{conversation}/attendance-status', [ConversationController::class, 'updateAttendanceStatus']);

    Route::get('/company-configs', [CompanyConfigController::class, 'index']);
    Route::put('/company-configs', [CompanyConfigController::class, 'update']);

    Route::prefix('knowledge')->group(function () {
        Route::get('/sources', [KnowledgeSourceController::class, 'index']);
        Route::post('/sources', [KnowledgeSourceController::class, 'store']);
        Route::put('/sources/{knowledgeSource}', [KnowledgeSourceController::class, 'update']);
        Route::delete('/sources/{knowledgeSource}', [KnowledgeSourceController::class, 'destroy']);
        Route::post('/sources/documents', [KnowledgeSourceController::class, 'storeDocument']);
        Route::post('/sources/{knowledgeSource}/reindex', [KnowledgeSourceController::class, 'reindex']);
        Route::post('/reindex-all', [KnowledgeSourceController::class, 'reindexAll']);
        Route::get('/stats', [KnowledgeSourceController::class, 'stats']);
        Route::post('/search', [KnowledgeSearchController::class, 'search']);
        Route::post('/context', [KnowledgeSearchController::class, 'context']);
    });
    });
});
