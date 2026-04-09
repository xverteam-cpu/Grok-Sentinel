<?php

use App\Http\Controllers\Admin\ValidationApprovalController;
use App\Http\Controllers\ProfileController;
use Illuminate\Foundation\Application;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::get('/', function () {
    return redirect()->route('login');
});

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
});

Route::middleware(['auth', 'verified', 'admin'])->prefix('admin')->name('admin.')->group(function () {
    Route::get('/dashboard', [\App\Http\Controllers\Admin\ValidationApprovalController::class, 'index'])
        ->name('dashboard');
    Route::get('/validations', [\App\Http\Controllers\Admin\ValidationApprovalController::class, 'index'])
        ->name('validations.index');
    Route::patch('/validations/{validationRequest}', [\App\Http\Controllers\Admin\ValidationApprovalController::class, 'updateValidation'])
        ->name('validations.update');
    Route::patch('/withdrawals/{withdrawalRequest}', [\App\Http\Controllers\Admin\ValidationApprovalController::class, 'updateWithdrawal'])
        ->name('withdrawals.update');
});
