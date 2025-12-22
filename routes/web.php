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
        Route::get('/create', \App\Livewire\Leads\CreateLead::class)->name('create');
        Route::get('/{id}', \App\Livewire\Leads\LeadDetails::class)->name('details');
    });
    
    // API Keys Management
    Route::get('/api-keys', \App\Livewire\Leads\ApiKeys::class)->name('api-keys');
});

Route::middleware(['auth'])->group(function () {
    Route::redirect('settings', 'settings/profile');

    Route::get('settings/profile', Profile::class)->name('settings.profile');
    Route::get('settings/password', Password::class)->name('settings.password');
    Route::get('settings/appearance', Appearance::class)->name('settings.appearance');
});

require __DIR__.'/auth.php';
