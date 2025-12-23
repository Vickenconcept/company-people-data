<?php

use App\Http\Controllers\ApiKeyController;
use App\Http\Controllers\CompanyAnalysisController;
use App\Http\Controllers\LeadGenController;
use App\Http\Controllers\PeopleSearchController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth'])->group(function () {
    // API Keys Management
    Route::prefix('api-keys')->group(function () {
        Route::get('/', [ApiKeyController::class, 'index']);
        Route::post('/', [ApiKeyController::class, 'store']);
        Route::put('/{id}', [ApiKeyController::class, 'update']);
        Route::delete('/{id}', [ApiKeyController::class, 'destroy']);
    });

    // Lead Generation
    Route::prefix('leads')->group(function () {
        Route::get('/', [LeadGenController::class, 'index']);
        Route::post('/', [LeadGenController::class, 'create']);
        Route::get('/{id}', [LeadGenController::class, 'show']);
        Route::get('/{id}/results', [LeadGenController::class, 'results']);
        Route::get('/{id}/queued-emails', [LeadGenController::class, 'queuedEmails']);
    });

    // Email Management
    Route::prefix('lead-results')->group(function () {
        Route::post('/{leadResultId}/generate-email', [LeadGenController::class, 'generateEmail']);
    });

    Route::post('/generate-bulk-emails', [LeadGenController::class, 'generateBulkEmails']);
    Route::post('/queue-emails', [LeadGenController::class, 'queueEmails']);

    // Company Analysis
    Route::post('/analyze-company', [CompanyAnalysisController::class, 'analyze']);

    // People Search
    Route::post('/search-people', [PeopleSearchController::class, 'search']);
});

