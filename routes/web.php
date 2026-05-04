<?php

use Illuminate\Support\Facades\Route;
use App\Http\Middleware\PreventAccessFromTenantDomains;

/*
|--------------------------------------------------------------------------
| Central (Landlord) Routes
|--------------------------------------------------------------------------
|
| These routes are for the central application (super admin panel).
| They should be accessed from the central domain only.
|
| NOTE: Tenant routes are automatically loaded from routes/tenant.php
| by the TenancyServiceProvider and should NOT be included here.
|
*/

// Landlord routes (prevent access from tenant domains)
Route::middleware([
    'web',
    PreventAccessFromTenantDomains::class,
])->group(function () {
    // Landlord panel routes
    require __DIR__ . '/landlord.php';

    // Landing page (#21)
    Route::get('/', [\App\Http\Controllers\LandingController::class, 'index'])->name('landing');

    // Contact form (public)
    Route::post('/contact', [\App\Http\Controllers\Landlord\ContactController::class, 'store'])->name('contact.store');

    // SEO
    Route::get('/sitemap.xml', [\App\Http\Controllers\LandingController::class, 'sitemap'])->name('sitemap');
    Route::get('/robots.txt', [\App\Http\Controllers\LandingController::class, 'robots'])->name('robots');
});
