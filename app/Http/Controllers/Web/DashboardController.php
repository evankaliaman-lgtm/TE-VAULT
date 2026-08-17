<?php

namespace App\Http\Controllers\Web;

use App\Enums\AssetAvailabilityStatus;
use App\Enums\BorrowingStatus;
use App\Http\Controllers\Controller;
use App\Models\Asset;
use App\Models\Borrowing;
use App\Models\NotificationLog;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __invoke(): View
    {
        $user = request()->user();
        $query = Borrowing::query()->with(['asset', 'borrower']);
        $isAdmin = $user->hasRole('admin');
        if (! $isAdmin) {
            $query->where('borrower_user_id', $user->id);
        }

        return view('dashboard', [
            'dashboard' => $isAdmin ? 'admin' : ($user->hasRole('guru') ? 'guru' : 'siswa'),
            'summary' => [
                'assets' => $isAdmin ? Asset::query()->count() : null,
                'available_assets' => $isAdmin ? Asset::query()->where('availability_status', AssetAvailabilityStatus::Tersedia)->count() : null,
                'pending_borrowings' => $isAdmin ? Borrowing::query()->where('status', BorrowingStatus::Pending)->count() : null,
                'active_borrowings' => (clone $query)->whereIn('status', [BorrowingStatus::Approved, BorrowingStatus::Borrowed, BorrowingStatus::ReturnPendingVerification])->count(),
                'requests' => $isAdmin ? null : (clone $query)->count(),
                'return_pending' => (clone $query)->where('status', BorrowingStatus::ReturnPendingVerification)->count(),
            ],
            'recentBorrowings' => $query->latest()->limit(5)->get(),
            'recentNotifications' => $isAdmin ? NotificationLog::query()->with('recipient')->latest()->limit(5)->get() : collect(),
        ]);
    }
}
