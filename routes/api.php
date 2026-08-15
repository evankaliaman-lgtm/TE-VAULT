<?php

use App\Http\Controllers\AssetCategoryController;
use App\Http\Controllers\AssetController;
use App\Http\Controllers\AuditLogController;
use App\Http\Controllers\BorrowingController;
use App\Http\Controllers\NotificationController;
use Illuminate\Support\Facades\Route;

Route::middleware('auth')->group(function (): void {
    Route::get('assets', [AssetController::class, 'index'])->middleware('permission:assets.view');
    Route::get('assets/{asset}', [AssetController::class, 'show'])->middleware('permission:assets.view');
    Route::middleware(['role:admin', 'permission:assets.create'])->post('admin/assets', [AssetController::class, 'store']);
    Route::middleware(['role:admin', 'permission:assets.update'])->match(['put', 'patch'], 'admin/assets/{asset}', [AssetController::class, 'update']);
    Route::middleware(['role:admin', 'permission:assets.delete'])->delete('admin/assets/{asset}', [AssetController::class, 'destroy']);
    Route::apiResource('admin/asset-categories', AssetCategoryController::class)->middleware(['role:admin', 'permission:assets.create']);
    Route::get('borrowings', [BorrowingController::class, 'index'])->middleware('permission:borrowings.view');
    Route::post('borrowings', [BorrowingController::class, 'store'])->middleware('permission:borrowings.create');
    Route::get('borrowings/{borrowing}', [BorrowingController::class, 'show'])->middleware('can:view,borrowing');
    Route::middleware(['role:admin'])->group(function (): void {
        Route::post('admin/borrowings/{borrowing}/approve', [BorrowingController::class, 'approve'])->middleware('permission:borrowings.approve');
        Route::post('admin/borrowings/{borrowing}/reject', [BorrowingController::class, 'reject'])->middleware('permission:borrowings.reject');
        Route::post('admin/borrowings/{borrowing}/checkout', [BorrowingController::class, 'checkout'])->middleware('permission:borrowings.approve');
        Route::post('admin/borrowings/{borrowing}/verify-return', [BorrowingController::class, 'verifyReturn'])->middleware('permission:borrowings.verify-return');
    });
    Route::post('borrowings/{borrowing}/cancel', [BorrowingController::class, 'cancel'])->middleware('can:cancel,borrowing');
    Route::post('borrowings/{borrowing}/submit-return', [BorrowingController::class, 'submitReturn'])->middleware('can:submitReturn,borrowing');
    Route::get('notifications', [NotificationController::class, 'index'])->middleware('permission:notifications.view');
    Route::get('admin/audit-logs', [AuditLogController::class, 'index'])->middleware(['role:admin', 'permission:audit.view']);
});
