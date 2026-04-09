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
        Route::get('/validations', [ValidationApprovalController::class, 'index'])->name('admin.validations');
        Route::post('/validations/{user}', [ValidationApprovalController::class, 'update'])->name('admin.validations.update');
    });

    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

require __DIR__.'/auth.php';
