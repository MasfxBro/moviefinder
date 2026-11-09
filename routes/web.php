<?php

use App\Http\Controllers\AccountController;
use App\Http\Controllers\Admin\AdminChannelAllowlistController;
use App\Http\Controllers\Admin\AdminClipLinkController;
use App\Http\Controllers\Admin\AdminDashboardController;
use App\Http\Controllers\Admin\AdminLoginController;
use App\Http\Controllers\Admin\AdminMissedSearchController;
use App\Http\Controllers\Admin\AdminReportController;
use App\Http\Controllers\Admin\AdminRequestController;
use App\Http\Controllers\Admin\AdminTitleController;
use App\Http\Controllers\Admin\AdminTwoFactorController;
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

$adminPath = trim((string) config('admin.path'), '/');
if ($adminPath !== '') {
    Route::prefix($adminPath)->group(function () {
        Route::get('/login', [AdminLoginController::class, 'create'])->middleware('throttle:5,1')->name('admin.login');
        Route::post('/login', [AdminLoginController::class, 'store'])->middleware('throttle:5,1')->name('admin.login.store');
        Route::get('/two-factor', AdminTwoFactorController::class)->middleware('hide-admin')->name('admin.2fa');
        Route::post('/two-factor', [AdminLoginController::class, 'verify'])->middleware(['hide-admin', 'throttle:5,1'])->name('admin.2fa.verify');
        Route::middleware('hide-admin')->group(function () {
            Route::get('/', AdminDashboardController::class)->name('admin.dashboard');

            Route::get('/titles', [AdminTitleController::class, 'index'])->name('admin.titles.index');
            Route::get('/titles/create', [AdminTitleController::class, 'create'])->name('admin.titles.create');
            Route::post('/titles', [AdminTitleController::class, 'store'])->name('admin.titles.store');
            Route::post('/titles/import', [AdminTitleController::class, 'import'])->name('admin.titles.import');
            Route::post('/titles/bulk-import', [AdminTitleController::class, 'bulkImport'])->name('admin.titles.bulk_import');
            Route::get('/titles/{title}/edit', [AdminTitleController::class, 'edit'])->name('admin.titles.edit');
            Route::put('/titles/{title}', [AdminTitleController::class, 'update'])->name('admin.titles.update');
            Route::post('/titles/{title}/toggle-hide', [AdminTitleController::class, 'toggleHide'])->name('admin.titles.toggle_hide');
            Route::post('/titles/{title}/refresh', [AdminTitleController::class, 'refresh'])->name('admin.titles.refresh');
            Route::get('/titles/{title}/delete', [AdminTitleController::class, 'confirmDelete'])->name('admin.titles.delete_confirm');
            Route::delete('/titles/{title}', [AdminTitleController::class, 'destroy'])->name('admin.titles.destroy');

            // Admin Requests Queue
            Route::get('/requests', [AdminRequestController::class, 'index'])->name('admin.requests.index');
            Route::post('/requests/{titleRequest}/approve', [AdminRequestController::class, 'approve'])->name('admin.requests.approve');
            Route::post('/requests/{titleRequest}/create-manual', [AdminRequestController::class, 'createManual'])->name('admin.requests.create_manual');
            Route::post('/requests/{titleRequest}/reject', [AdminRequestController::class, 'reject'])->name('admin.requests.reject');
            Route::post('/requests/merge', [AdminRequestController::class, 'merge'])->name('admin.requests.merge');

            // Admin Missed Searches
            Route::get('/missed-searches', [AdminMissedSearchController::class, 'index'])->name('admin.missed_searches.index');
            Route::post('/missed-searches/{searchMiss}/convert', [AdminMissedSearchController::class, 'convert'])->name('admin.missed_searches.convert');
            Route::post('/missed-searches/{searchMiss}/ignore', [AdminMissedSearchController::class, 'ignore'])->name('admin.missed_searches.ignore');

            // Admin Clip Links CRUD & Verify / Bulk Remove
            Route::get('/clip-links', [AdminClipLinkController::class, 'index'])->name('admin.clip_links.index');
            Route::get('/clip-links/create', [AdminClipLinkController::class, 'create'])->name('admin.clip_links.create');
            Route::post('/clip-links', [AdminClipLinkController::class, 'store'])->name('admin.clip_links.store');
            Route::get('/clip-links/{clipLink}/edit', [AdminClipLinkController::class, 'edit'])->name('admin.clip_links.edit');
            Route::put('/clip-links/{clipLink}', [AdminClipLinkController::class, 'update'])->name('admin.clip_links.update');
            Route::delete('/clip-links/{clipLink}', [AdminClipLinkController::class, 'destroy'])->name('admin.clip_links.destroy');
            Route::post('/clip-links/{clipLink}/verify', [AdminClipLinkController::class, 'verify'])->name('admin.clip_links.verify');
            Route::post('/clip-links/bulk-remove', [AdminClipLinkController::class, 'bulkRemove'])->name('admin.clip_links.bulk_remove');

            // Admin Reports Queue
            Route::get('/reports', [AdminReportController::class, 'index'])->name('admin.reports.index');
            Route::post('/reports/{linkReport}/restore', [AdminReportController::class, 'restore'])->name('admin.reports.restore');
            Route::post('/reports/{linkReport}/remove-link', [AdminReportController::class, 'removeLink'])->name('admin.reports.remove_link');
            Route::post('/reports/{linkReport}/block-channel', [AdminReportController::class, 'blockChannel'])->name('admin.reports.block_channel');
            Route::post('/reports/{linkReport}/resolve', [AdminReportController::class, 'resolve'])->name('admin.reports.resolve');

            // Admin Channel Allow-list CRUD
            Route::get('/channels', [AdminChannelAllowlistController::class, 'index'])->name('admin.channels.index');
            Route::get('/channels/create', [AdminChannelAllowlistController::class, 'create'])->name('admin.channels.create');
            Route::post('/channels', [AdminChannelAllowlistController::class, 'store'])->name('admin.channels.store');
            Route::get('/channels/{channel}/edit', [AdminChannelAllowlistController::class, 'edit'])->name('admin.channels.edit');
            Route::put('/channels/{channel}', [AdminChannelAllowlistController::class, 'update'])->name('admin.channels.update');
            Route::delete('/channels/{channel}', [AdminChannelAllowlistController::class, 'destroy'])->name('admin.channels.destroy');

            // Settings editor
            Route::get('/settings', [\App\Http\Controllers\Admin\AdminSettingsController::class, 'index'])->name('admin.settings.index');
            Route::post('/settings/{key}', [\App\Http\Controllers\Admin\AdminSettingsController::class, 'update'])->name('admin.settings.update');

            // Announcements CRUD
            Route::get('/announcements', [\App\Http\Controllers\Admin\AdminAnnouncementController::class, 'index'])->name('admin.announcements.index');
            Route::get('/announcements/create', [\App\Http\Controllers\Admin\AdminAnnouncementController::class, 'create'])->name('admin.announcements.create');
            Route::post('/announcements', [\App\Http\Controllers\Admin\AdminAnnouncementController::class, 'store'])->name('admin.announcements.store');
            Route::get('/announcements/{announcement}/edit', [\App\Http\Controllers\Admin\AdminAnnouncementController::class, 'edit'])->name('admin.announcements.edit');
            Route::put('/announcements/{announcement}', [\App\Http\Controllers\Admin\AdminAnnouncementController::class, 'update'])->name('admin.announcements.update');
            Route::delete('/announcements/{announcement}', [\App\Http\Controllers\Admin\AdminAnnouncementController::class, 'destroy'])->name('admin.announcements.destroy');

            // Feedback inbox
            Route::get('/feedback', [\App\Http\Controllers\Admin\AdminFeedbackController::class, 'index'])->name('admin.feedback.index');
            Route::post('/feedback/{feedback}/status', [\App\Http\Controllers\Admin\AdminFeedbackController::class, 'updateStatus'])->name('admin.feedback.update_status');
        });
    });
}

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
