<?php

use App\Http\Controllers\CategoryController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\ProblemController;
use App\Http\Controllers\SyncController;
use Illuminate\Support\Facades\Route;

Route::redirect('/', '/login');

Route::middleware('auth')->group(function () {
    Route::get('/dashboard', DashboardController::class)->name('dashboard');
    Route::get('/problems', [ProblemController::class, 'index'])->name('problems.index');
    Route::get('/categories', [CategoryController::class, 'index'])->name('categories.index');

    Route::post('/sync/progress', [SyncController::class, 'progress'])->name('sync.progress');
    Route::post('/sync/catalog', [SyncController::class, 'catalog'])->name('sync.catalog');
});

require __DIR__.'/auth.php';
