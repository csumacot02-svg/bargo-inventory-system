<?php

use App\Http\Controllers\ActivityLogController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\Admin\UserManagementController;
use App\Http\Controllers\AiInsightsController;
use App\Http\Controllers\AlertController;
use App\Http\Controllers\Auth\AccountStatusController;
use App\Http\Controllers\Auth\EmailVerificationController;
use App\Http\Controllers\Auth\GatewayController;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Auth\NewPasswordController;
use App\Http\Controllers\Auth\PasswordResetLinkController;
use App\Http\Controllers\Auth\RegisterController;
use App\Http\Controllers\Auth\SocialAuthController;
use App\Http\Controllers\CategoryController;
use App\Http\Controllers\ClientReleaseController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\InventoryItemController;
use App\Http\Controllers\InventoryRequestController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\ScanController;
use App\Http\Controllers\Setup\FirstAdminSetupController;
use App\Http\Controllers\StockAdjustmentController;
use App\Http\Controllers\StockInController;
use App\Http\Controllers\StockMovementLogController;
use App\Http\Controllers\SupplierController;
use App\Support\AuthIntent;
use Illuminate\Support\Facades\Route;

$intentConstraint = implode('|', AuthIntent::all());

Route::get('/', GatewayController::class)->name('gateway');

Route::post('/access/{intent}', [GatewayController::class, 'select'])
    ->where('intent', $intentConstraint)
    ->name('gateway.select');

Route::middleware('setup.available')->group(function () {
    Route::get('/setup/admin', [FirstAdminSetupController::class, 'create'])->name('setup.admin');
    Route::post('/setup/admin', [FirstAdminSetupController::class, 'store'])
        ->middleware('throttle:5,1')
        ->name('setup.admin.store');
});

Route::middleware('guest')->group(function () use ($intentConstraint) {
    // Legacy entry points redirect to the role gateway (named routes kept for auth redirects).
    Route::get('/login', fn () => redirect()->route('gateway'))->name('login');
    Route::get('/register', fn () => redirect()->route('gateway'))->name('register');

    Route::get('/login/{intent}', [LoginController::class, 'create'])
        ->where('intent', $intentConstraint)
        ->name('login.role');
    Route::post('/login/{intent}', [LoginController::class, 'store'])
        ->middleware('throttle:login')
        ->where('intent', $intentConstraint)
        ->name('login.role.store');

    Route::get('/register/{intent}', [RegisterController::class, 'create'])
        ->where('intent', $intentConstraint)
        ->name('register.role');
    Route::post('/register/{intent}', [RegisterController::class, 'store'])
        ->middleware('throttle:register')
        ->where('intent', $intentConstraint)
        ->name('register.role.store');

    Route::get('/auth/{provider}/{intent}/redirect', [SocialAuthController::class, 'redirect'])
        ->middleware('throttle:login')
        ->where('intent', $intentConstraint)
        ->whereIn('provider', ['google', 'facebook'])
        ->name('auth.social.redirect');
    Route::get('/auth/{provider}/callback', [SocialAuthController::class, 'callback'])
        ->whereIn('provider', ['google', 'facebook'])
        ->name('auth.social.callback');

    Route::get('/forgot-password', [PasswordResetLinkController::class, 'create'])->name('password.request');
    Route::post('/forgot-password', [PasswordResetLinkController::class, 'store'])
        ->middleware('throttle:password-reset')
        ->name('password.email');
    Route::get('/reset-password/{token}', [NewPasswordController::class, 'create'])->name('password.reset');
    Route::post('/reset-password', [NewPasswordController::class, 'store'])
        ->middleware('throttle:password-reset')
        ->name('password.store');
});

Route::middleware('auth')->group(function () {
    Route::post('/logout', [LoginController::class, 'destroy'])->name('logout');

    Route::get('/email/verify', [EmailVerificationController::class, 'notice'])->name('verification.notice');
    Route::post('/email/verify', [EmailVerificationController::class, 'verify'])
        ->middleware('throttle:otp-verify')
        ->name('verification.verify');
    Route::post('/email/verification-notification', [EmailVerificationController::class, 'send'])
        ->middleware('throttle:6,1')
        ->name('verification.send');

    Route::get('/account/pending', [AccountStatusController::class, 'pending'])->name('account.pending');
    Route::get('/account/blocked', [AccountStatusController::class, 'blocked'])->name('account.blocked');
});

Route::middleware(['auth', 'verified', 'account.active'])->group(function () {
    Route::get('/profile', [ProfileController::class, 'show'])->name('profile.show');
    Route::get('/profile/edit', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');

    Route::get('/dashboard', function () {
        return redirect()->route(auth()->user()->homeRoute());
    })->name('dashboard');

    Route::middleware('role:admin')->get('/admin/dashboard', DashboardController::class)->name('admin.dashboard');
    Route::middleware('role:staff')->get('/staff/dashboard', DashboardController::class)->name('staff.dashboard');

    Route::get('/requests', [InventoryRequestController::class, 'index'])->name('requests.index');
    Route::get('/requests/pending', fn () => redirect()->route('requests.index', ['filter' => 'pending']))->name('requests.pending');
    Route::get('/requests/approved', fn () => redirect()->route('requests.index', ['filter' => 'approved']))->name('requests.approved');
    Route::get('/requests/released', fn () => redirect()->route('requests.index', ['filter' => 'released']))->name('requests.released');
    Route::get('/requests/completed', fn () => redirect()->route('requests.index', ['filter' => 'completed']))->name('requests.completed');
    Route::get('/requests/history', fn () => redirect()->route('requests.index', ['filter' => 'history']))->name('requests.history');

    Route::middleware('role:admin,staff')->group(function () {
        Route::get('/requests/create', [InventoryRequestController::class, 'create'])->name('requests.create');
        Route::post('/requests', [InventoryRequestController::class, 'store'])->name('requests.store');
    });

    Route::get('/requests/{inventoryRequest}', [InventoryRequestController::class, 'show'])->name('requests.show');
    Route::get('/requests/{inventoryRequest}/release-slip', [InventoryRequestController::class, 'releaseSlip'])->name('requests.release-slip');
    Route::get('/requests/{inventoryRequest}/payment-order.xlsx', [InventoryRequestController::class, 'downloadPaymentOrderExcel'])->name('requests.payment-order-excel');
    Route::patch('/requests/{inventoryRequest}/client', [InventoryRequestController::class, 'updateClient'])->name('requests.update-client');

    Route::middleware('role:admin,staff')->group(function () {
        Route::post('/requests/{inventoryRequest}/approve', [InventoryRequestController::class, 'approve'])->name('requests.approve');
        Route::post('/requests/{inventoryRequest}/reject', [InventoryRequestController::class, 'reject'])->name('requests.reject');
        Route::post('/requests/{inventoryRequest}/release', [InventoryRequestController::class, 'release'])->name('requests.release');
        Route::post('/requests/{inventoryRequest}/complete', [InventoryRequestController::class, 'complete'])->name('requests.complete');
        Route::post('/requests/{inventoryRequest}/cancel', [InventoryRequestController::class, 'cancel'])->name('requests.cancel');
        Route::post('/requests/{inventoryRequest}/confirm-payment', [InventoryRequestController::class, 'confirmPayment'])->name('requests.confirm-payment');
        Route::get('/inventory', [InventoryItemController::class, 'index'])->name('inventory.index');
        Route::get('/inventory/create', [InventoryItemController::class, 'create'])->name('inventory.create');
        Route::post('/inventory', [InventoryItemController::class, 'store'])->name('inventory.store');
        Route::get('/inventory/{inventory}', [InventoryItemController::class, 'show'])->name('inventory.show');
        Route::get('/inventory/{inventory}/edit', [InventoryItemController::class, 'edit'])->name('inventory.edit');
        Route::put('/inventory/{inventory}', [InventoryItemController::class, 'update'])->name('inventory.update');
        Route::patch('/inventory/{inventory}', [InventoryItemController::class, 'update']);

        Route::get('/adjustments/create', [StockAdjustmentController::class, 'create'])->name('adjustments.create');
        Route::post('/adjustments', [StockAdjustmentController::class, 'store'])->name('adjustments.store');

        Route::get('/stock-ins', [StockInController::class, 'index'])->name('stock-ins.index');
        Route::get('/stock-ins/create', [StockInController::class, 'create'])->name('stock-ins.create');
        Route::post('/stock-ins', [StockInController::class, 'store'])->name('stock-ins.store');
        Route::get('/stock-ins/{stockIn}', [StockInController::class, 'show'])->name('stock-ins.show');

        Route::get('/clients', [ClientReleaseController::class, 'index'])->name('clients.index');

        Route::get('/reports', [ReportController::class, 'index'])->name('reports.index');
        Route::get('/reports/export', [ReportController::class, 'export'])->name('reports.export');
        Route::get('/reports/consolidated-bargo', [ReportController::class, 'consolidatedBargo'])->name('reports.consolidated-bargo');
        Route::get('/alerts', [AlertController::class, 'index'])->name('alerts.index');
        Route::get('/ai-insights', [AiInsightsController::class, 'index'])->name('ai.index');
        Route::post('/ai-insights/analyze', [AiInsightsController::class, 'analyze'])
            ->middleware('throttle:ai-analyze')
            ->name('ai.analyze');
        Route::get('/scan', [ScanController::class, 'index'])->name('scan.index');
        Route::match(['get', 'post'], '/scan/lookup', [ScanController::class, 'lookup'])->name('scan.lookup');

        Route::get('/activity-logs', [ActivityLogController::class, 'index'])->name('activity-logs.index');
        Route::get('/activity-logs/export', [ActivityLogController::class, 'export'])->name('activity-logs.export');
        Route::get('/logs/stock-movements', [StockMovementLogController::class, 'index'])->name('logs.stock-movements');
        Route::get('/logs/stock-movements/export', [StockMovementLogController::class, 'export'])->name('logs.stock-movements.export');

        Route::get('/categories', [CategoryController::class, 'index'])->name('categories.index');
        Route::post('/categories', [CategoryController::class, 'store'])->name('categories.store');
        Route::put('/categories/{category}', [CategoryController::class, 'update'])->name('categories.update');
        Route::delete('/categories/{category}', [CategoryController::class, 'destroy'])->name('categories.destroy');

        Route::get('/suppliers', [SupplierController::class, 'index'])->name('suppliers.index');
        Route::post('/suppliers', [SupplierController::class, 'store'])->name('suppliers.store');
        Route::put('/suppliers/{supplier}', [SupplierController::class, 'update'])->name('suppliers.update');
        Route::delete('/suppliers/{supplier}', [SupplierController::class, 'destroy'])->name('suppliers.destroy');

        Route::get('/document-templates', [\App\Http\Controllers\Admin\BargoDocumentTemplateController::class, 'index'])->name('document-templates.index');
        Route::post('/document-templates', [\App\Http\Controllers\Admin\BargoDocumentTemplateController::class, 'store'])->name('document-templates.store');
        Route::delete('/document-templates', [\App\Http\Controllers\Admin\BargoDocumentTemplateController::class, 'reset'])->name('document-templates.reset');
        Route::get('/document-templates/default', [\App\Http\Controllers\Admin\BargoDocumentTemplateController::class, 'downloadDefault'])->name('document-templates.download-default');
        Route::get('/document-templates/active', [\App\Http\Controllers\Admin\BargoDocumentTemplateController::class, 'downloadActive'])->name('document-templates.download-active');
        Route::post('/document-templates/{documentTemplate}/reactivate', [\App\Http\Controllers\Admin\BargoDocumentTemplateController::class, 'reactivate'])->name('document-templates.reactivate');
        Route::delete('/document-templates/{documentTemplate}', [\App\Http\Controllers\Admin\BargoDocumentTemplateController::class, 'destroy'])->name('document-templates.destroy');
    });

    Route::middleware('role:admin')->group(function () {
        Route::delete('/inventory/{inventory}', [InventoryItemController::class, 'destroy'])->name('inventory.destroy');
        Route::post('/stock-ins/{stockIn}/void', [StockInController::class, 'void'])->name('stock-ins.void');
    });

    Route::middleware('role:admin')->prefix('admin')->name('admin.')->group(function () {
        Route::get('/users', [UserManagementController::class, 'index'])->name('users.index');
        Route::get('/users/create', [UserManagementController::class, 'create'])->name('users.create');
        Route::post('/users', [UserManagementController::class, 'store'])->name('users.store');
        Route::get('/users/{user}', [UserManagementController::class, 'show'])->name('users.show');
        Route::post('/users/{user}/approve', [UserManagementController::class, 'approve'])->name('users.approve');
        Route::post('/users/{user}/reject', [UserManagementController::class, 'reject'])->name('users.reject');
        Route::post('/users/{user}/suspend', [UserManagementController::class, 'suspend'])->name('users.suspend');
        Route::post('/users/{user}/reactivate', [UserManagementController::class, 'reactivate'])->name('users.reactivate');
        Route::post('/users/{user}/demote', [UserManagementController::class, 'demote'])->name('users.demote');
        Route::patch('/users/{user}/id-number', [UserManagementController::class, 'assignIdNumber'])->name('users.assign-id-number');
        Route::patch('/users/{user}/email', [UserManagementController::class, 'updateEmail'])->name('users.update-email');
        Route::patch('/users/{user}/password', [UserManagementController::class, 'updatePassword'])->name('users.update-password');
        Route::post('/users/{user}/role', [UserManagementController::class, 'updateRole'])->name('users.role');
    });
});
