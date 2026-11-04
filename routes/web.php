<?php

use App\Http\Controllers\HomeController;
use App\Http\Controllers\ProfileController;
use Illuminate\Support\Facades\Route;

// Public routes (D-35: guests allowed)
Route::get('/', HomeController::class)->name('home');

Route::view('/pricing', 'pricing')->name('pricing');
Route::view('/terms', 'legal.terms')->name('terms');
Route::view('/privacy', 'legal.privacy')->name('privacy');
Route::view('/copyright', 'legal.copyright')->name('copyright');
Route::view('/about', 'legal.about')->name('about');

Route::get('/version', function () {
    return response()->json(['build' => config('app.build_id', 'dev')]);
})->middleware('throttle:30,1')->name('version');

// Auth routes (register, login, etc.) loaded from auth.php
require __DIR__.'/auth.php';

// Protected routes (auth required, guests redirected to login)
Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('/dashboard', function () {
        return view('dashboard');
    })->name('dashboard');

    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});
