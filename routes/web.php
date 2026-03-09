<?php

use App\Livewire\Settings\Appearance;
use App\Livewire\Settings\Password;
use App\Livewire\Settings\Profile;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
})->name('home');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('dashboard', \App\Livewire\Leads\Dashboard::class)->name('dashboard');
    
    // Lead Generation Routes
    Route::prefix('leads')->name('leads.')->group(function () {
        Route::get('/', \App\Livewire\Leads\Dashboard::class)->name('dashboard');
        Route::get('/all', \App\Livewire\Leads\AllLeads::class)->name('all');
        Route::get('/create', \App\Livewire\Leads\CreateLead::class)->name('create');
        Route::get('/export', [\App\Http\Controllers\ExportController::class, 'exportLeads'])->name('export');
        Route::get('/email-templates', \App\Livewire\Leads\EmailTemplates::class)->name('email-templates');
        Route::get('/import', \App\Livewire\Leads\ImportLeads::class)->name('import');
        Route::get('/{id}', \App\Livewire\Leads\LeadDetails::class)->name('details');
    });
    
    // API Keys Management
    Route::get('/api-keys', \App\Livewire\Leads\ApiKeys::class)->name('api-keys');
});

Route::middleware(['auth'])->group(function () {
    Route::redirect('settings', 'settings/profile');

    Route::get('settings/profile', Profile::class)->name('settings.profile');
    Route::get('settings/password', Password::class)->name('settings.password');
});

require __DIR__.'/auth.php';
