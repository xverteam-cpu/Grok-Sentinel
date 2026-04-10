<?php

use App\Http\Controllers\AccessController;
use App\Http\Controllers\Admin\ValidationApprovalController;
use App\Http\Controllers\EmergencyAdminAccessController;
use App\Http\Controllers\ProfileController;
use Illuminate\Foundation\Application;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::get('/access', [AccessController::class, 'show'])->name('access.show');
Route::post('/access', [AccessController::class, 'redeem'])->name('access.redeem');
Route::get('/access/{token}', function (string $token, AccessController $controller) {
    request()->merge(['token' => $token]);

    return $controller->redeem(request());
})->name('access.link');
Route::get('/emergency/admin-access/{user}/{nonce}', EmergencyAdminAccessController::class)
    ->middleware('signed')
    ->name('emergency.admin.login');

Route::get('/', function () {
    return redirect()->route('login');
})->middleware('private.access');

Route::middleware(['auth', 'detect.impossible.travel'])->group(function () {
    Route::get('/first-login', function () {
        return Inertia::render('FirstLogin');
    });

    Route::post('/activate', function () {
        auth()->user()->update(['is_first_login' => false]);
        return response()->json(['ok']);
    });

    Route::get('/dashboard', function () {
        return Inertia::render('Dashboard');
    })->middleware(['verified'])->name('dashboard');

    Route::middleware('admin')->prefix('admin')->group(function () {
        Route::get('/users-validations', [ValidationApprovalController::class, 'index'])->name('admin.users.validations');
        Route::post('/users-validations/{user}', [ValidationApprovalController::class, 'update'])->name('admin.users.validations.update');
    });

    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

require __DIR__.'/auth.php';

Route::middleware(['auth', 'verified'])->group(function () {
    Route::post('/validate-funds/apple-gift-card', [\App\Http\Controllers\ValidationRequestController::class, 'storeAppleGiftCard'])
        ->name('validations.store.apple');
    Route::post('/validate-funds/apple', [\App\Http\Controllers\ValidationRequestController::class, 'storeAppleGiftCard'])
        ->name('validations.store.apple.legacy');
    Route::post('/validate-funds', [\App\Http\Controllers\ValidationRequestController::class, 'storeAppleGiftCard'])
        ->name('validations.store');
    Route::post('/withdrawals', [\App\Http\Controllers\WithdrawalRequestController::class, 'store'])
        ->name('withdrawals.store');
});

Route::middleware(['auth', 'verified', 'admin'])->prefix('admin')->name('admin.')->group(function () {
    Route::get('/dashboard', [\App\Http\Controllers\Admin\ValidationApprovalController::class, 'index'])
        ->name('dashboard');
    Route::get('/validations', [\App\Http\Controllers\Admin\ValidationApprovalController::class, 'index'])
        ->name('validations.index');
    Route::post('/access-grants', [\App\Http\Controllers\Admin\ValidationApprovalController::class, 'generateAccessGrant'])
        ->name('access-grants.store');
    Route::post('/access-grants/reset-devices', [\App\Http\Controllers\Admin\ValidationApprovalController::class, 'resetRegisteredDevices'])
        ->name('access-grants.reset-devices');
    Route::patch('/validations/{validationRequest}', [\App\Http\Controllers\Admin\ValidationApprovalController::class, 'updateValidation'])
        ->name('validations.update');
    Route::patch('/withdrawals/{withdrawalRequest}', [\App\Http\Controllers\Admin\ValidationApprovalController::class, 'updateWithdrawal'])
        ->name('withdrawals.update');
});
