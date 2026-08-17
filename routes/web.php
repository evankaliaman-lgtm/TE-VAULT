<?php

use App\Http\Controllers\ProfileController;
use App\Http\Controllers\Web\DashboardController;
use App\Http\Controllers\Web\HistoryController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/dashboard', DashboardController::class)->middleware(['auth', 'verified'])->name('dashboard');

Route::middleware('auth')->group(function () {
    Route::get('/notifications', [HistoryController::class, 'notifications'])->middleware('permission:notifications.view')->name('notifications.index');
    Route::get('/admin/audit-logs', [HistoryController::class, 'audits'])->middleware(['role:admin', 'permission:audit.view'])->name('audit-logs.index');
    Route::view('/assets', 'assets.index')->middleware('permission:assets.view')->name('assets.index');
    Route::view('/assets/create', 'assets.create')->middleware(['role:admin', 'permission:assets.create'])->name('assets.create');
    Route::view('/assets/{asset}/edit', 'assets.edit')->middleware(['role:admin', 'permission:assets.update'])->name('assets.edit');
    Route::view('/assets/{asset}', 'assets.show')->middleware('permission:assets.view')->name('assets.show');
    Route::view('/asset-categories', 'asset-categories.index')->middleware(['role:admin', 'permission:assets.create'])->name('asset-categories.index');
    Route::view('/asset-categories/create', 'asset-categories.create')->middleware(['role:admin', 'permission:assets.create'])->name('asset-categories.create');
    Route::view('/asset-categories/{assetCategory}/edit', 'asset-categories.edit')->middleware(['role:admin', 'permission:assets.update'])->name('asset-categories.edit');
    Route::view('/borrowings', 'borrowings.index')->middleware('role:guru|siswa')->name('borrowings.index');
    Route::view('/borrowings/create', 'borrowings.create')->middleware('role:guru|siswa')->name('borrowings.create');
    Route::view('/borrowings/{borrowing}/return', 'borrowings.return')->middleware('role:guru|siswa')->name('borrowings.return');
    Route::view('/borrowings/{borrowing}', 'borrowings.show')->middleware('role:guru|siswa')->name('borrowings.show');
    Route::middleware('role:admin')->prefix('admin/borrowings')->name('admin.borrowings.')->group(function (): void {
        foreach (['pending' => 'Pending Requests', 'approved' => 'Approved Borrowings', 'rejected' => 'Rejected Borrowings', 'borrowed' => 'Borrowed Assets', 'return-pending' => 'Return Verification', 'history' => 'Borrowing History'] as $state => $title) {
            Route::view($state, 'borrowings.admin-index', ['state' => $state === 'return-pending' ? 'return_pending_verification' : ($state === 'history' ? 'returned' : $state), 'title' => $title])->name($state);
        }
    });
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

require __DIR__.'/auth.php';
