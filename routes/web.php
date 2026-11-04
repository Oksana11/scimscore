<?php

use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\EmailChangeController;
use App\Http\Controllers\InstitutionSearchController;
use App\Http\Controllers\ProfileController;
use Illuminate\Support\Facades\Route;

Route::view('/', 'home')->name('home');
Route::view('/terms', 'terms')->name('terms');

// --- Sign in / registration (email + one-time code) ---
Route::middleware('guest')->group(function () {
    Route::get('/login', [LoginController::class, 'showEmail'])->name('login');
    Route::post('/login', [LoginController::class, 'sendCode'])->middleware('throttle:otp-send')->name('login.send');
    Route::get('/login/code', [LoginController::class, 'showCode'])->name('login.code');
    Route::post('/login/code', [LoginController::class, 'verify'])->middleware('throttle:otp-verify')->name('login.verify');
    Route::post('/login/resend', [LoginController::class, 'resend'])->middleware('throttle:otp-send')->name('login.resend');
});

Route::middleware(['auth', 'active'])->group(function () {
    Route::post('/logout', [LoginController::class, 'logout'])->name('logout');

    Route::get('/profile/complete', [ProfileController::class, 'complete'])->name('profile.complete');
    Route::put('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::get('/institutions/search', InstitutionSearchController::class)->middleware('throttle:institution-search')->name('institutions.search');

    Route::middleware('profile.complete')->group(function () {
        Route::get('/dashboard', DashboardController::class)->name('dashboard');
        Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
        Route::post('/profile/email', [EmailChangeController::class, 'store'])->middleware('throttle:otp-send')->name('profile.email.store');
        Route::post('/profile/email/confirm', [EmailChangeController::class, 'confirm'])->middleware('throttle:otp-verify')->name('profile.email.confirm');
    });
});
