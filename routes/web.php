<?php

use App\Http\Controllers\AccountController;
use App\Http\Controllers\AiController;
use App\Http\Controllers\BrowseController;
use App\Http\Controllers\ClipReportController;
use App\Http\Controllers\FeedbackController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\OnboardingController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\SearchHistoryController;
use App\Http\Controllers\TitleDetailController;
use App\Http\Controllers\TitleRequestController;
use App\Http\Controllers\TitleSearchController;
use App\Http\Controllers\VersionController;
use App\Http\Controllers\WatchlistController;
use Illuminate\Support\Facades\Route;

// Public routes (D-35: guests allowed)
Route::get('/', HomeController::class)->name('home');

Route::view('/pricing', 'pricing')->name('pricing');
Route::view('/terms', 'legal.terms')->name('terms');
Route::view('/privacy', 'legal.privacy')->name('privacy');
Route::view('/copyright', 'legal.copyright')->name('copyright');
Route::view('/about', 'legal.about')->name('about');

Route::get('/version', VersionController::class)->middleware('throttle:30,1')->name('version');

// Auth routes (register, login, etc.) loaded from auth.php
require __DIR__.'/auth.php';

// Protected routes (auth required, guests redirected to login)
Route::middleware('auth')->group(function () {
    Route::post('/onboarding/outcome', [OnboardingController::class, 'outcome'])->name('onboarding.outcome');
    Route::get('/browse', BrowseController::class)->name('browse');
    Route::get('/titles/{slug}', TitleDetailController::class)->name('titles.show');
    Route::post('/clips/{clipLink}/report', [ClipReportController::class, 'store'])->middleware('throttle:report-link')->name('clips.report');
    Route::get('/watchlist', [WatchlistController::class, 'index'])->name('watchlist.index');
    Route::post('/watchlist/{title}', [WatchlistController::class, 'toggle'])->middleware('throttle:30,1')->name('watchlist.toggle');
    Route::get('/history', [SearchHistoryController::class, 'index'])->name('history.index');
    Route::delete('/history', [SearchHistoryController::class, 'destroy'])->name('history.destroy');
    Route::get('/history/recent', [SearchHistoryController::class, 'recent'])->name('history.recent');
});

Route::middleware(['auth'])->group(function () {
    Route::get('/account', [AccountController::class, 'show'])->name('account.show');
    Route::patch('/account/name', [AccountController::class, 'updateName'])->name('account.name.update');
    Route::delete('/account', [AccountController::class, 'destroy'])->name('account.destroy');
});

Route::middleware(['auth', 'verified'])->group(function () {
    Route::post('/ai/messages', [AiController::class, 'messages'])->middleware(['ai.verified', 'throttle:ai-messages'])->name('ai.messages');
    Route::get('/ai/status', [AiController::class, 'status'])->middleware('ai.verified')->name('ai.status');
    Route::get('/dashboard', function () {
        return view('dashboard');
    })->name('dashboard');

    Route::get('/search', [TitleSearchController::class, '__invoke'])->name('search');

    Route::get('/requests', [TitleRequestController::class, 'index'])->name('requests.index');
    Route::get('/requests/create', [TitleRequestController::class, 'create'])->name('requests.create');
    Route::post('/requests', [TitleRequestController::class, 'store'])->middleware('throttle:request-title')->name('requests.store');
    Route::get('/requests/{titleRequest}', [TitleRequestController::class, 'show'])->name('requests.show');

    Route::get('/feedback', [FeedbackController::class, 'create'])->name('feedback.create');
    Route::post('/feedback', [FeedbackController::class, 'store'])->name('feedback.store');

    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});
