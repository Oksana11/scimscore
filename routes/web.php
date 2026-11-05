<?php

use App\Http\Controllers\AssessmentController;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\EmailChangeController;
use App\Http\Controllers\InstitutionSearchController;
use App\Http\Controllers\PatientAssessmentController;
use App\Http\Controllers\PatientController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\QuickAssessmentController;
use Illuminate\Support\Facades\Route;

Route::view('/', 'home')->name('home');
Route::view('/terms', 'terms')->name('terms');

// --- Quick Assessment (no account) ---
Route::prefix('quick')->name('quick.')->controller(QuickAssessmentController::class)->group(function () {
    Route::get('/', 'create')->name('create');
    Route::post('/', 'store')->middleware('throttle:quick-assessment')->name('store');
    Route::get('/{assessment}', 'result')->name('result');
    Route::post('/{assessment}/email', 'sendCode')->middleware('throttle:otp-send')->name('email.code');
    Route::post('/{assessment}/email/send', 'sendResult')->middleware('throttle:otp-verify')->name('email.send');
    Route::post('/{assessment}/email/change', 'changeEmail')->name('email.change');
});

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

        Route::resource('patients', PatientController::class)->except(['destroy']);
        Route::get('/patients/{patient}/assessments/create', [PatientAssessmentController::class, 'create'])->name('patients.assessments.create');
        Route::post('/patients/{patient}/assessments', [PatientAssessmentController::class, 'store'])->name('patients.assessments.store');
        Route::get('/patients/{patient}/compare', [AssessmentController::class, 'compare'])->name('patients.compare');

        Route::get('/assessments', [AssessmentController::class, 'index'])->name('assessments.index');
        Route::get('/assessments/{assessment}', [AssessmentController::class, 'show'])->name('assessments.show');
        Route::post('/assessments/{assessment}/email', [AssessmentController::class, 'email'])->middleware('throttle:result-email')->name('assessments.email');
    });
});
