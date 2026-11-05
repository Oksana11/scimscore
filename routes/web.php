<?php

use App\Http\Controllers\Admin\AdminAuthController;
use App\Http\Controllers\Admin\AuditController as AdminAuditController;
use App\Http\Controllers\Admin\DashboardController as AdminDashboardController;
use App\Http\Controllers\Admin\DataController as AdminDataController;
use App\Http\Controllers\Admin\SettingsController as AdminSettingsController;
use App\Http\Controllers\Admin\UserController as AdminUserController;
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

// Institution autocomplete (profile and Quick Assessment step 1): our DB + ROR, rate-limited.
Route::get('/institutions/search', InstitutionSearchController::class)->middleware('throttle:institution-search')->name('institutions.search');

// --- Quick Assessment (no account) ---
Route::prefix('quick')->name('quick.')->controller(QuickAssessmentController::class)->group(function () {
    Route::get('/', 'create')->name('create');
    Route::post('/caregiver', 'saveCaregiver')->middleware('throttle:quick-assessment')->name('caregiver');
    Route::get('/assessment', 'assessment')->name('assessment');
    Route::post('/', 'store')->middleware('throttle:quick-assessment')->name('store');
    Route::get('/{assessment}', 'result')->name('result');
    Route::post('/{assessment}/email', 'sendCode')->middleware('throttle:otp-send')->name('email.code');
    Route::post('/{assessment}/email/send', 'sendResult')->middleware('throttle:otp-verify')->name('email.send');
    Route::post('/{assessment}/email/change', 'changeEmail')->name('email.change');
    Route::get('/{assessment}/pdf', 'pdf')->middleware('throttle:export')->name('pdf');
    Route::get('/{assessment}/xlsx', 'xlsx')->middleware('throttle:export')->name('xlsx');
});

// --- Sign in / registration (email + one-time code) ---
Route::middleware('guest')->group(function () {
    Route::get('/login', [LoginController::class, 'showEmail'])->name('login');
    Route::post('/login', [LoginController::class, 'sendCode'])->middleware('throttle:otp-send')->name('login.send');
    Route::get('/login/code', [LoginController::class, 'showCode'])->name('login.code');
    Route::post('/login/code', [LoginController::class, 'verify'])->middleware('throttle:otp-verify')->name('login.verify');
    Route::post('/login/resend', [LoginController::class, 'resend'])->middleware('throttle:otp-send')->name('login.resend');
});

Route::middleware(['auth:web', 'active'])->group(function () {
    Route::post('/logout', [LoginController::class, 'logout'])->name('logout');

    Route::get('/profile/complete', [ProfileController::class, 'complete'])->name('profile.complete');
    Route::put('/profile', [ProfileController::class, 'update'])->name('profile.update');

    Route::middleware('profile.complete')->group(function () {
        Route::get('/dashboard', DashboardController::class)->name('dashboard');
        Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
        Route::post('/profile/email', [EmailChangeController::class, 'store'])->middleware('throttle:otp-send')->name('profile.email.store');
        Route::post('/profile/email/confirm', [EmailChangeController::class, 'confirm'])->middleware('throttle:otp-verify')->name('profile.email.confirm');

        Route::resource('patients', PatientController::class)->except(['destroy']);
        Route::get('/patients/{patient}/assessments/create', [PatientAssessmentController::class, 'create'])->name('patients.assessments.create');
        Route::post('/patients/{patient}/assessments', [PatientAssessmentController::class, 'store'])->name('patients.assessments.store');
        Route::get('/patients/{patient}/compare', [AssessmentController::class, 'compare'])->name('patients.compare');

        Route::view('/assessments/new', 'assessments.new')->name('assessments.new');
        Route::get('/assessments', [AssessmentController::class, 'index'])->name('assessments.index');
        Route::get('/assessments/export/xlsx', [AssessmentController::class, 'exportXlsx'])->middleware('throttle:export')->name('assessments.export.xlsx');
        Route::get('/assessments/export/pdf', [AssessmentController::class, 'exportPdf'])->middleware('throttle:export')->name('assessments.export.pdf');
        Route::get('/assessments/{assessment}', [AssessmentController::class, 'show'])->name('assessments.show');
        Route::get('/assessments/{assessment}/pdf', [AssessmentController::class, 'pdf'])->middleware('throttle:export')->name('assessments.pdf');
        Route::get('/assessments/{assessment}/xlsx', [AssessmentController::class, 'xlsx'])->middleware('throttle:export')->name('assessments.xlsx');
        Route::post('/assessments/{assessment}/email', [AssessmentController::class, 'email'])->middleware('throttle:result-email')->name('assessments.email');
    });
});

// --- Administration (separate guard; email code + authenticator app) ---
Route::prefix('admin')->name('admin.')->group(function () {
    Route::middleware('guest:admin')->controller(AdminAuthController::class)->group(function () {
        Route::get('/login', 'showEmail')->name('login');
        Route::post('/login', 'sendCode')->middleware('throttle:otp-send')->name('login.send');
        Route::get('/login/code', 'showCode')->name('login.code');
        Route::post('/login/code', 'verifyCode')->middleware('throttle:otp-verify')->name('login.verify');
        Route::get('/login/mfa', 'showMfa')->name('login.mfa');
        Route::post('/login/mfa', 'verifyMfa')->middleware('throttle:otp-verify')->name('login.mfa.verify');
    });

    Route::middleware(['auth:admin', 'admin.session'])->group(function () {
        Route::post('/logout', [AdminAuthController::class, 'logout'])->name('logout');

        Route::get('/', [AdminDashboardController::class, 'index'])->name('dashboard');
        Route::get('/statistics/export/{format}', [AdminDashboardController::class, 'export'])->middleware('throttle:export')->name('statistics.export');

        Route::get('/users', [AdminUserController::class, 'index'])->name('users');
        Route::post('/users/{user}/toggle', [AdminUserController::class, 'toggle'])->name('users.toggle');

        Route::get('/patients', [AdminDataController::class, 'patients'])->name('patients');
        Route::get('/assessments', [AdminDataController::class, 'assessments'])->name('assessments');
        Route::get('/assessments/export/{format}', [AdminDataController::class, 'export'])->middleware('throttle:export')->name('assessments.export');

        Route::get('/settings', [AdminSettingsController::class, 'index'])->name('settings');
        Route::post('/settings/countries/{country}/toggle', [AdminSettingsController::class, 'toggleCountry'])->name('countries.toggle');
        Route::post('/settings/professions', [AdminSettingsController::class, 'storeProfession'])->name('professions.store');
        Route::post('/settings/professions/{profession}/toggle', [AdminSettingsController::class, 'toggleProfession'])->name('professions.toggle');
        Route::post('/settings/institutions/{institution}/rename', [AdminSettingsController::class, 'renameInstitution'])->name('institutions.rename');
        Route::post('/settings/institutions/{institution}/merge', [AdminSettingsController::class, 'mergeInstitution'])->name('institutions.merge');

        Route::get('/audit', [AdminAuditController::class, 'index'])->name('audit');
        Route::post('/audit/verify', [AdminAuditController::class, 'verify'])->name('audit.verify');
    });
});
