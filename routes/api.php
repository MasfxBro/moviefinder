<?php

use App\Http\Controllers\TitleSearchController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'verified'])->get('/search', [TitleSearchController::class, '__invoke'])
    ->middleware('throttle:60,1')
    ->name('search');
