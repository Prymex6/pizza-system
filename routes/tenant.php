<?php

use App\Http\Controllers\Tenant\AuthController;
use App\Http\Controllers\Tenant\Client\MenuController;
use App\Http\Controllers\Tenant\Client\CheckoutController;
use App\Http\Controllers\Tenant\Client\PaymentController;
use App\Http\Controllers\Tenant\Client\OrderTrackingController;
use App\Http\Controllers\Tenant\Client\CustomerAuthController;
use App\Http\Controllers\Tenant\Client\SocialAuthController;
use App\Http\Controllers\Tenant\Client\AccountController;
use App\Http\Controllers\Tenant\Client\PageController;
use App\Http\Controllers\Tenant\Client\ReservationController;
use App\Http\Controllers\Tenant\Client\ContactController;
use App\Http\Controllers\Tenant\Client\InvoiceController;
use App\Http\Controllers\Tenant\SitemapController;
use App\Http\Middleware\Tenant\CheckRestaurantOpen;
use App\Http\Controllers\Tenant\Manager\DashboardController;
use App\Http\Controllers\Tenant\Manager\MenuManagementController;
use App\Http\Controllers\Tenant\Manager\OrderManagementController;
use App\Http\Controllers\Tenant\Manager\StaffController;
use App\Http\Controllers\Tenant\Manager\DeliveryZoneController;
use App\Http\Controllers\Tenant\Manager\DiscountCodeController;
use App\Http\Controllers\Tenant\Manager\ReportController;
use App\Http\Controllers\Tenant\Manager\SettingsController;
use App\Http\Controllers\Tenant\Manager\ReservationManagementController;
use App\Http\Controllers\Tenant\Manager\TableController;
use App\Http\Controllers\Tenant\Manager\LoyaltyController;
use App\Http\Controllers\Tenant\Manager\LoyaltyRewardController;
use App\Http\Controllers\Tenant\Manager\LoyaltyCampaignController;
use App\Http\Controllers\Tenant\Manager\MarketingController;
use App\Http\Controllers\Tenant\Staff\KitchenController;
use App\Http\Controllers\Tenant\Staff\WaiterController;
use App\Http\Controllers\Tenant\Staff\DriverController;
use App\Http\Controllers\Tenant\Staff\StaffReportController;
use App\Http\Controllers\Tenant\Staff\PosController;
use App\Http\Controllers\Tenant\Staff\PushSubscriptionController;
use App\Http\Controllers\Tenant\Staff\QuickControlsController;
use App\Http\Controllers\Tenant\Manager\StaffReportsController;
use App\Http\Controllers\Tenant\Manager\RolePermissionsController;
use App\Http\Controllers\Tenant\Manager\LicenseController;
use App\Http\Controllers\Tenant\Manager\SupportController;
use App\Http\Controllers\Tenant\Manager\SetupController;
use App\Http\Controllers\Tenant\Manager\ImpersonateController;
use App\Http\Controllers\Tenant\BroadcastAuthController;
use App\Http\Controllers\Tenant\Manager\InstallController;
use App\Http\Controllers\Tenant\Manager\CustomerController;
use App\Http\Middleware\Tenant\EnsureInstallComplete;
use Illuminate\Support\Facades\Route;
use Stancl\Tenancy\Middleware\InitializeTenancyByDomain;
use Stancl\Tenancy\Middleware\PreventAccessFromCentralDomains;

/*
|--------------------------------------------------------------------------
| Tenant Routes (Restaurant Frontend & Backend)
|--------------------------------------------------------------------------
*/

Route::middleware([
  'web',
  InitializeTenancyByDomain::class,
  PreventAccessFromCentralDomains::class,
  EnsureInstallComplete::class,
])->group(function () {

  // ─── Install Wizard (public, only when setup not done) ──────────
  Route::get('/install', [InstallController::class, 'index'])->name('tenant.install');
  Route::post('/install/account', [InstallController::class, 'createAccount'])->name('tenant.install.account')->middleware('throttle:5,60');
  Route::post('/install/restaurant', [InstallController::class, 'saveRestaurant'])->name('tenant.install.restaurant')->middleware('throttle:10,60');
  Route::post('/install/hours', [InstallController::class, 'saveHours'])->name('tenant.install.hours')->middleware('throttle:10,60');
  Route::post('/install/complete', [InstallController::class, 'complete'])->name('tenant.install.complete')->middleware('throttle:10,60');

  // ─── Staff Authentication ───────────────────────────────────────
  Route::get('/login', [AuthController::class, 'showLogin'])->name('tenant.login');
  Route::post('/login', [AuthController::class, 'login'])->middleware('throttle:' . env('AUTH_THROTTLE', '5') . ',60');
  Route::post('/logout', [AuthController::class, 'logout'])->name('tenant.logout');

  // Staff Password Reset
  Route::get('/forgot-password', [AuthController::class, 'showForgotPassword'])->name('tenant.password.request');
  Route::post('/forgot-password', [AuthController::class, 'sendResetLink'])->name('tenant.password.email')->middleware('throttle:5,60');
  Route::get('/reset-password/{token}', [AuthController::class, 'showResetPassword'])->name('tenant.password.reset');
  Route::post('/reset-password', [AuthController::class, 'resetPassword'])->name('tenant.password.update');

  // ─── Customer Authentication ────────────────────────────────────
  Route::name('tenant.client.')->prefix('konto')->group(function () {
    Route::get('/logowanie', [CustomerAuthController::class, 'showLogin'])->name('login');
    Route::post('/logowanie', [CustomerAuthController::class, 'login'])->middleware('throttle:' . env('AUTH_THROTTLE', '5') . ',60');
    Route::get('/rejestracja', [CustomerAuthController::class, 'showRegister'])->name('register');
    Route::post('/rejestracja', [CustomerAuthController::class, 'register'])->middleware('throttle:10,60');
    Route::post('/wyloguj', [CustomerAuthController::class, 'logout'])->name('logout');

    // Customer Password Reset
    Route::get('/reset-hasla', [CustomerAuthController::class, 'showForgotPassword'])->name('password.request');
    Route::post('/reset-hasla', [CustomerAuthController::class, 'sendResetLink'])->name('password.email')->middleware('throttle:5,60');
    Route::get('/reset-hasla/{token}', [CustomerAuthController::class, 'showResetPassword'])->name('password.reset');
    Route::post('/nowe-haslo', [CustomerAuthController::class, 'resetPassword'])->name('password.update');
  });

  // ─── Client-facing routes (public) ──────────────────────────────
  Route::middleware(['check.license'])->name('tenant.')->group(function () {
    // Menu
    Route::get('/', [MenuController::class, 'index'])->name('menu');
    Route::get('/product/{product}', [MenuController::class, 'show'])->name('product.show');

    // Checkout
    Route::get('/checkout', [CheckoutController::class, 'index'])->name('checkout');
    Route::post('/checkout', [CheckoutController::class, 'store'])->name('checkout.store')->middleware([CheckRestaurantOpen::class, 'throttle:5,60']);
    Route::post('/checkout/validate-address', [CheckoutController::class, 'validateAddress'])->name('checkout.validate-address');
    Route::post('/checkout/validate-discount', [CheckoutController::class, 'validateDiscountCode'])->name('checkout.validate-discount')->middleware('throttle:15,60');
    Route::post('/checkout/validate-cart', [CheckoutController::class, 'validateCart'])->name('checkout.validate-cart')->middleware('throttle:30,60');

    // Payments - initiate + return (authenticated or open)
    Route::get('/payment/{order}/initiate', [PaymentController::class, 'initiate'])->name('payment.initiate');
    Route::get('/payment/{order}/return', [PaymentController::class, 'return'])->name('payment.return');

    // Webhooks (CSRF excluded in bootstrap/app.php via */payment/webhook*)
    Route::post('/payment/webhook', [PaymentController::class, 'webhook'])->name('payment.webhook');
    Route::post('/payment/webhook/payu', [PaymentController::class, 'webhookPayU'])->name('payment.webhook.payu');
    Route::post('/payment/webhook/tpay', [PaymentController::class, 'webhookTpay'])->name('payment.webhook.tpay');
    Route::post('/payment/webhook/stripe', [PaymentController::class, 'webhookStripe'])->name('payment.webhook.stripe');

    // Order Tracking
    Route::get('/order/{orderNumber}/tracking', [OrderTrackingController::class, 'show'])->name('order.tracking');

    // Static pages
    Route::get('/regulamin', [PageController::class, 'terms'])->name('terms');
    Route::get('/polityka-prywatnosci', [PageController::class, 'privacy'])->name('privacy');
    Route::get('/kontakt', [ContactController::class, 'index'])->name('contact');
    Route::post('/kontakt', [ContactController::class, 'send'])->name('contact.send')->middleware('throttle:5,60');

    // Reservations
    Route::get('/rezerwacja', [ReservationController::class, 'index'])->name('reservation');
    Route::post('/rezerwacja', [ReservationController::class, 'store'])->name('reservation.store');

    // SEO
    Route::get('/sitemap.xml', [SitemapController::class, 'index'])->name('sitemap');
    Route::get('/robots.txt', [SitemapController::class, 'robots'])->name('robots');

    // Marketing unsubscribe (public, no auth)
    Route::get('/marketing/unsubscribe', [MarketingController::class, 'unsubscribe'])->name('marketing.unsubscribe');

    // Email verification for email change (public – user clicks link in email)
    Route::get('/moje-konto/verify-email/{token}', [AccountController::class, 'verifyEmail'])->name('account.verify-email');

    // Customer account (authenticated)
    Route::middleware(['auth.customer'])->group(function () {
      Route::get('/moje-konto', [AccountController::class, 'index'])->name('account');
      Route::put('/moje-konto', [AccountController::class, 'update'])->name('account.update');
      Route::put('/moje-konto/haslo', [AccountController::class, 'updatePassword'])->name('account.password');
      Route::delete('/moje-konto', [AccountController::class, 'destroy'])->name('account.destroy');
      Route::delete('/moje-konto/zamowienia/{orderNumber}/cancel', [AccountController::class, 'cancelOrder'])->name('account.order.cancel');
      Route::get('/zamowienia/{orderNumber}/faktura', [InvoiceController::class, 'show'])->name('order.invoice');
      Route::post('/recenzja', [\App\Http\Controllers\Tenant\Client\ReviewController::class, 'store'])->name('review.store');
    });
  });

  // ─── Authenticated staff routes ─────────────────────────────────
  // Impersonation entry point (public – validates token then logs in as manager)
  Route::get('/manager/impersonate', [ImpersonateController::class, 'handle'])->name('tenant.manager.impersonate');

  Route::middleware(['auth.tenant'])->group(function () {

    // Broadcasting auth – uses tenant guard (for private channels in manager/staff panels)
    Route::post('/broadcasting/auth', BroadcastAuthController::class);

    // Role-based redirect after login
    Route::get('/staff', [AuthController::class, 'redirectByRole'])->name('tenant.staff.redirect');

    // ─── Manager Panel (role: manager) ──────────────────────────
    Route::prefix('manager')->name('tenant.manager.')->middleware(['role:manager', 'check.setup'])->group(function () {
      // Stop impersonating (landlord admin exit)
      Route::post('/impersonate/stop', [ImpersonateController::class, 'stop'])->name('impersonate.stop');
      // Installation wizard (#1)
      Route::get('/setup', [SetupController::class, 'index'])->name('setup');
      Route::post('/setup', [SetupController::class, 'store'])->name('setup.store');

      Route::get('/', [DashboardController::class, 'index'])->name('dashboard');

      // Menu Management
      Route::prefix('menu')->name('menu.')->group(function () {
        Route::get('/', [MenuManagementController::class, 'index'])->name('index');
        Route::get('/categories', [MenuManagementController::class, 'categories'])->name('categories');
        Route::post('/categories', [MenuManagementController::class, 'storeCategory'])->name('categories.store');
        Route::put('/categories/{category}', [MenuManagementController::class, 'updateCategory'])->name('categories.update');
        Route::patch('/categories/{category}', [MenuManagementController::class, 'updateCategory']);
        Route::delete('/categories/{category}', [MenuManagementController::class, 'destroyCategory'])->name('categories.destroy');
        Route::get('/products/create', [MenuManagementController::class, 'createProduct'])->name('products.create');
        Route::post('/products', [MenuManagementController::class, 'storeProduct'])->name('products.store');
        Route::get('/products/{product}/edit', [MenuManagementController::class, 'editProduct'])->name('products.edit');
        Route::put('/products/{product}', [MenuManagementController::class, 'updateProduct'])->name('products.update');
        Route::patch('/products/{product}', [MenuManagementController::class, 'updateProduct']);
        Route::delete('/products/{product}', [MenuManagementController::class, 'destroyProduct'])->name('products.destroy');
        Route::post('/products/{product}/toggle-availability', [MenuManagementController::class, 'toggleAvailability'])->name('products.toggle-availability');
        Route::post('/addons', [MenuManagementController::class, 'storeAddon'])->name('addons.store');
        Route::delete('/addons/{addon}', [MenuManagementController::class, 'destroyAddon'])->name('addons.destroy');
        Route::get('/import/template', [MenuManagementController::class, 'importTemplate'])->name('import.template');
        Route::post('/import', [MenuManagementController::class, 'importCsv'])->name('import');
      });

      // Order Management
      Route::prefix('orders')->name('orders.')->group(function () {
        Route::get('/', [OrderManagementController::class, 'index'])->name('index');
        Route::get('/{order}', [OrderManagementController::class, 'show'])->name('show');
        Route::patch('/{order}/status', [OrderManagementController::class, 'updateStatus'])->name('update-status');
        Route::patch('/{order}/payment-status', [OrderManagementController::class, 'updatePaymentStatus'])->name('update-payment-status');
        // Faktura VAT (dostępna tylko w APP_VERSION=test)
        Route::get('/{orderNumber}/faktura', [InvoiceController::class, 'showForManager'])->name('invoice');
      });

      Route::resource('staff', StaffController::class)->except(['show']);

      // Delivery Zones
      Route::prefix('delivery-zones')->name('delivery-zones.')->group(function () {
        Route::get('/', [DeliveryZoneController::class, 'index'])->name('index');
        Route::post('/', [DeliveryZoneController::class, 'store'])->name('store');
        Route::put('/{deliveryZone}', [DeliveryZoneController::class, 'update'])->name('update');
        Route::delete('/{deliveryZone}', [DeliveryZoneController::class, 'destroy'])->name('destroy');
      });

      // Discount Codes
      Route::prefix('discounts')->name('discounts.')->group(function () {
        Route::get('/', [DiscountCodeController::class, 'index'])->name('index');
        Route::post('/', [DiscountCodeController::class, 'store'])->name('store');
        Route::put('/{discountCode}', [DiscountCodeController::class, 'update'])->name('update');
        Route::delete('/{discountCode}', [DiscountCodeController::class, 'destroy'])->name('destroy');
        Route::post('/{discountCode}/toggle', [DiscountCodeController::class, 'toggle'])->name('toggle');
      });

      // Reports
      Route::prefix('reports')->name('reports.')->group(function () {
        Route::get('/', [ReportController::class, 'index'])->name('index');
        Route::get('/export-csv', [ReportController::class, 'exportCsv'])->name('export-csv');
      });

      // Settings
      Route::prefix('settings')->name('settings.')->group(function () {
        Route::get('/', [SettingsController::class, 'index'])->name('index');
        Route::put('/', [SettingsController::class, 'update'])->name('update');
        Route::post('/upload', [SettingsController::class, 'upload'])->name('upload');
        Route::post('/test-smtp', [SettingsController::class, 'testSmtp'])->name('test-smtp');
        Route::post('/test-sms', [SettingsController::class, 'testSms'])->name('test-sms');
      });

      // Reservations
      Route::prefix('reservations')->name('reservations.')->group(function () {
        Route::get('/', [ReservationManagementController::class, 'index'])->name('index');
        Route::patch('/{reservation}/status', [ReservationManagementController::class, 'updateStatus'])->name('update-status');
      });

      // Customers (#8)
      Route::prefix('customers')->name('customers.')->group(function () {
        Route::get('/', [CustomerController::class, 'index'])->name('index');
        Route::get('/export', [CustomerController::class, 'export'])->name('export');
        Route::get('/{customer}', [CustomerController::class, 'show'])->name('show');
      });

      // Tables management (manager)
      Route::prefix('tables')->name('tables.')->group(function () {
        Route::get('/', [TableController::class, 'index'])->name('index');
        Route::post('/', [TableController::class, 'store'])->name('store');
        Route::put('/{table}', [TableController::class, 'update'])->name('update');
        Route::delete('/{table}', [TableController::class, 'destroy'])->name('destroy');
        Route::patch('/{table}/toggle', [TableController::class, 'toggleActive'])->name('toggle');
        Route::post('/{table}/qr-code', [TableController::class, 'generateQrCode'])->name('qr-code');
      });

      // Reviews
      Route::prefix('reviews')->name('reviews.')->group(function () {
        Route::get('/', [\App\Http\Controllers\Tenant\Manager\ReviewController::class, 'index'])->name('index');
        Route::post('/', [\App\Http\Controllers\Tenant\Manager\ReviewController::class, 'store'])->name('store');
        Route::put('/{review}', [\App\Http\Controllers\Tenant\Manager\ReviewController::class, 'update'])->name('update');
        Route::delete('/{review}', [\App\Http\Controllers\Tenant\Manager\ReviewController::class, 'destroy'])->name('destroy');
      });

      // Loyalty Program
      Route::prefix('loyalty')->name('loyalty.')->group(function () {
        Route::get('/', [LoyaltyController::class, 'index'])->name('index');
        Route::post('/customers/{customer}/points', [LoyaltyController::class, 'addPoints'])->name('add-points');
        Route::get('/customers/{customer}', [LoyaltyController::class, 'customerDetail'])->name('customer-detail');

        // Rewards CRUD
        Route::prefix('rewards')->name('rewards.')->group(function () {
          Route::get('/', [LoyaltyRewardController::class, 'index'])->name('index');
          Route::post('/', [LoyaltyRewardController::class, 'store'])->name('store');
          Route::put('/{reward}', [LoyaltyRewardController::class, 'update'])->name('update');
          Route::delete('/{reward}', [LoyaltyRewardController::class, 'destroy'])->name('destroy');
        });

        // Campaigns CRUD
        Route::prefix('campaigns')->name('campaigns.')->group(function () {
          Route::get('/', [LoyaltyCampaignController::class, 'index'])->name('index');
          Route::post('/', [LoyaltyCampaignController::class, 'store'])->name('store');
          Route::put('/{campaign}', [LoyaltyCampaignController::class, 'update'])->name('update');
          Route::delete('/{campaign}', [LoyaltyCampaignController::class, 'destroy'])->name('destroy');
        });
      });

      // Marketing / Email Campaigns
      Route::prefix('marketing')->name('marketing.')->group(function () {
        Route::get('/', [MarketingController::class, 'index'])->name('index');
        Route::post('/', [MarketingController::class, 'store'])->name('store');
        Route::put('/{campaign}', [MarketingController::class, 'update'])->name('update');
        Route::post('/{campaign}/send', [MarketingController::class, 'send'])->name('send');
        Route::delete('/{campaign}', [MarketingController::class, 'destroy'])->name('destroy');
      });

      // Staff Reports (manager view)
      Route::prefix('staff-reports')->name('staff-reports.')->group(function () {
        Route::get('/', [StaffReportsController::class, 'index'])->name('index');
        Route::patch('/{report}/mark-read', [StaffReportsController::class, 'markRead'])->name('mark-read');
        Route::post('/mark-all-read', [StaffReportsController::class, 'markAllRead'])->name('mark-all-read');
        Route::get('/export', [StaffReportsController::class, 'export'])->name('export');
      });

      // Role Permissions
      Route::prefix('role-permissions')->name('role-permissions.')->group(function () {
        Route::get('/', [RolePermissionsController::class, 'index'])->name('index');
        Route::put('/', [RolePermissionsController::class, 'update'])->name('update');
      });

      // License info
      Route::get('/license', [LicenseController::class, 'index'])->name('license');

      // Support tickets (tenant side)
      Route::prefix('support')->name('support.')->group(function () {
        Route::get('/', [SupportController::class, 'index'])->name('index');
        Route::post('/', [SupportController::class, 'store'])->name('store');
        Route::get('/{ticket}', [SupportController::class, 'show'])->name('show');
        Route::post('/{ticket}/reply', [SupportController::class, 'reply'])->name('reply');
        Route::post('/{ticket}/close', [SupportController::class, 'close'])->name('close');
      });
    });

    // ─── Staff Panels ───────────────────────────────────────────
    Route::prefix('staff')->name('tenant.staff.')->group(function () {
      Route::middleware(['role:chef,manager'])->group(function () {
        Route::get('/kitchen', [KitchenController::class, 'index'])->name('kitchen');
        Route::patch('/kitchen/{order}/status', [OrderManagementController::class, 'updateStatus'])
          ->middleware('permission:update_order_status')
          ->name('kitchen.update-status');
      });
      Route::middleware(['role:waiter,cashier,manager'])->group(function () {
        Route::get('/waiter', [WaiterController::class, 'index'])->name('waiter');
        Route::get('/waiter/tables', [WaiterController::class, 'tables'])->name('waiter.tables');
        Route::post('/waiter/tables', [WaiterController::class, 'store'])->name('waiter.tables.store');
        Route::put('/waiter/tables/{table}', [WaiterController::class, 'update'])->name('waiter.tables.update');
        Route::delete('/waiter/tables/{table}', [WaiterController::class, 'destroy'])->name('waiter.tables.destroy');
        Route::patch('/waiter/tables/{table}/toggle', [WaiterController::class, 'toggle'])->name('waiter.tables.toggle');
        Route::patch('/waiter/tables/{table}/status', [WaiterController::class, 'setStatus'])->name('waiter.tables.status');
        Route::post('/waiter/tables/{table}/qr-code', [WaiterController::class, 'generateQrCode'])->name('waiter.tables.qr-code');
        Route::patch('/waiter/{order}/accept', [WaiterController::class, 'acceptOrder'])
          ->middleware('permission:update_order_status')
          ->name('waiter.accept');
        Route::patch('/waiter/{order}/complete', [WaiterController::class, 'completeOrder'])
          ->middleware('permission:update_order_status')
          ->name('waiter.complete');
      });
      Route::middleware(['role:driver,manager'])->group(function () {
        Route::get('/driver', [DriverController::class, 'index'])->name('driver');
        Route::post('/driver/{order}/assign', [DriverController::class, 'assignToMe'])
          ->middleware('permission:update_order_status')
          ->name('driver.assign');
        Route::post('/driver/{order}/complete', [DriverController::class, 'completeDelivery'])
          ->middleware('permission:update_order_status')
          ->name('driver.complete');
        Route::post('/driver/{order}/location', [DriverController::class, 'updateLocation'])
          ->middleware('permission:update_order_status')
          ->name('driver.location');
      });
      // Quick controls (all staff roles)
      Route::post('/quick-controls', [QuickControlsController::class, 'update'])->name('quick-controls');

      // Staff reports (for all staff with permission)
      Route::get('/reports', [StaffReportController::class, 'index'])->name('reports.index');
      Route::post('/reports', [StaffReportController::class, 'store'])
        ->middleware('permission:send_reports')
        ->name('reports.store');
      // Push subscriptions (#32)
      Route::post('/push/subscribe', [PushSubscriptionController::class, 'store'])->name('push.subscribe');
      Route::post('/push/unsubscribe', [PushSubscriptionController::class, 'destroy'])->name('push.unsubscribe');
      Route::get('/push/debug', function () {
          $subs = \App\Models\Tenant\PushSubscription::all();
          return response()->json([
              'vapid_public_key'  => substr(config('webpush.vapid.public_key', ''), 0, 20) . '...',
              'vapid_configured'  => !empty(config('webpush.vapid.public_key')) && !empty(config('webpush.vapid.private_key')),
              'subscriptions'     => $subs->count(),
              'subscription_list' => $subs->map(fn($s) => [
                  'id'         => $s->id,
                  'user_id'    => $s->staff_user_id,
                  'endpoint'   => substr($s->endpoint, 0, 50) . '...',
                  'has_p256dh' => !empty($s->p256dh_key),
                  'has_auth'   => !empty($s->auth_key),
                  'created_at' => $s->created_at,
              ]),
          ]);
      })->name('push.debug');
      // POS - point of sale
      Route::middleware(['role:waiter,cashier,manager'])->group(function () {
        Route::get('/pos', [PosController::class, 'index'])->name('pos');
        Route::post('/pos/order', [PosController::class, 'store'])
          ->middleware('permission:accept_phone_orders')
          ->name('pos.store');
      });
    });
  });
});
