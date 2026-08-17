<?php

use App\Enums\AssetAvailabilityStatus;
use App\Enums\BorrowingStatus;
use App\Http\Controllers\ProfileController;
use App\Models\Asset;
use App\Models\Borrowing;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/dashboard', function () {
    $user = request()->user();
    if ($user->hasRole('admin')) {
        return view('dashboard', ['dashboard' => 'admin', 'summary' => ['assets' => Asset::count(), 'available_assets' => Asset::where('availability_status', AssetAvailabilityStatus::Tersedia)->count(), 'pending_borrowings' => Borrowing::where('status', BorrowingStatus::Pending)->count(), 'active_borrowings' => Borrowing::whereIn('status', [BorrowingStatus::Approved, BorrowingStatus::Borrowed, BorrowingStatus::ReturnPendingVerification])->count()]]);
    }
    $borrowings = Borrowing::where('borrower_user_id', $user->id);

    return view('dashboard', ['dashboard' => $user->hasRole('guru') ? 'guru' : 'siswa', 'summary' => ['requests' => (clone $borrowings)->count(), 'active_borrowings' => (clone $borrowings)->where('status', BorrowingStatus::Borrowed)->count(), 'return_pending' => (clone $borrowings)->where('status', BorrowingStatus::ReturnPendingVerification)->count()]]);
})->middleware(['auth', 'verified'])->name('dashboard');

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

require __DIR__.'/auth.php';
