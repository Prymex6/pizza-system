<?php

use App\Http\Controllers\Landlord\AuthController;
use App\Http\Controllers\Landlord\ContactController;
use App\Http\Controllers\Landlord\RestaurantSearchController;
use App\Http\Controllers\Landlord\DashboardController;
use App\Http\Controllers\Landlord\ModificationController;
use App\Http\Controllers\Landlord\TenantController;
use App\Http\Controllers\Landlord\PlanController;
use App\Http\Controllers\Landlord\LandlordSupportController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Landlord Routes (Super Admin Panel)
|--------------------------------------------------------------------------
*/


Route::prefix('admin')->name('landlord.')->group(function () {
    // Authentication
    Route::get('login', [AuthController::class, 'showLogin'])->name('login');
    Route::post('login', [AuthController::class, 'login']);
    Route::post('logout', [AuthController::class, 'logout'])->name('logout');

    // Protected routes
    Route::middleware(['auth.super_admin'])->group(function () {
        // Broadcasting auth for landlord WebSockets (support-admin, contact-admin)
        Route::post('broadcasting/auth', \App\Http\Controllers\Landlord\BroadcastAuthController::class)->name('landlord.broadcasting.auth');

        // Dashboard
        Route::get('dashboard', [DashboardController::class, 'index'])->name('dashboard');

        // Tenants Management
        Route::resource('tenants', TenantController::class);
        Route::post('tenants/{tenant}/activate', [TenantController::class, 'activate'])->name('tenants.activate');
        Route::post('tenants/{tenant}/suspend', [TenantController::class, 'suspend'])->name('tenants.suspend');
        Route::post('tenants/{tenant}/impersonate', [TenantController::class, 'impersonate'])->name('tenants.impersonate');
        Route::post('tenants/{tenant}/clear-cache', [TenantController::class, 'clearCache'])->name('tenants.clear-cache');

        // Modifications (OCMod-like system)
        Route::resource('modifications', ModificationController::class)->except(['show']);
        Route::post('modifications/{modification}/toggle', [ModificationController::class, 'toggle'])->name('modifications.toggle');
        Route::post('modifications/apply', [ModificationController::class, 'apply'])->name('modifications.apply');
        Route::post('modifications/clear', [ModificationController::class, 'clear'])->name('modifications.clear');

        // Plans
        Route::resource('plans', PlanController::class)->except(['show']);

        // Restaurant search (prospecting tool)
        Route::get('restaurant-search', [RestaurantSearchController::class, 'index'])->name('restaurant-search.index');
        Route::get('restaurant-search/search', [RestaurantSearchController::class, 'search'])->name('restaurant-search.search');
        Route::get('restaurant-search/find-contact', [RestaurantSearchController::class, 'findContact'])->name('restaurant-search.find-contact');
        Route::post('restaurant-search/toggle-contacted', [RestaurantSearchController::class, 'toggleContacted'])->name('restaurant-search.toggle-contacted');
        Route::post('restaurant-search/send-email', [RestaurantSearchController::class, 'sendEmail'])->name('restaurant-search.send-email');

        // Contact inquiries from landing page
        Route::get('contacts', [ContactController::class, 'index'])->name('contacts.index');
        Route::patch('contacts/{inquiry}/read', [ContactController::class, 'markRead'])->name('contacts.read');
        Route::delete('contacts/{inquiry}', [ContactController::class, 'destroy'])->name('contacts.destroy');

        // Support tickets (#25)
        Route::prefix('support')->name('support.')->group(function () {
            Route::get('/', [LandlordSupportController::class, 'index'])->name('index');
            Route::get('/{ticket}', [LandlordSupportController::class, 'show'])->name('show');
            Route::patch('/{ticket}/status', [LandlordSupportController::class, 'updateStatus'])->name('update-status');
            Route::post('/{ticket}/reply', [LandlordSupportController::class, 'reply'])->name('reply');
        });
    });
});
