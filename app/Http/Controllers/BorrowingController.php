<?php

namespace App\Http\Controllers;

use App\Actions\Borrowings\ApproveBorrowingAction;
use App\Actions\Borrowings\CancelBorrowingAction;
use App\Actions\Borrowings\CheckoutBorrowingAction;
use App\Actions\Borrowings\RejectBorrowingAction;
use App\Actions\Borrowings\RequestBorrowingAction;
use App\Actions\Borrowings\SubmitReturnAction;
use App\Actions\Borrowings\VerifyReturnAction;
use App\Enums\AssetCondition;
use App\Http\Requests\Borrowings\ApproveBorrowingRequest;
use App\Http\Requests\Borrowings\CancelBorrowingRequest;
use App\Http\Requests\Borrowings\CheckoutBorrowingRequest;
use App\Http\Requests\Borrowings\RejectBorrowingRequest;
use App\Http\Requests\Borrowings\StoreBorrowingRequest;
use App\Http\Requests\Borrowings\SubmitReturnRequest;
use App\Http\Requests\Borrowings\VerifyReturnRequest;
use App\Http\Resources\BorrowingResource;
use App\Models\Asset;
use App\Models\Borrowing;
use App\Services\AuditLogService;
use App\Services\NotificationService;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class BorrowingController extends Controller
{
    public function index(): AnonymousResourceCollection
    {
        $query = Borrowing::query()->with(['asset', 'borrower']);
        if (! request()->user()->hasRole('admin')) {
            $query->where('borrower_user_id', request()->user()->id);
        }

        return BorrowingResource::collection($query->paginate());
    }

    public function store(StoreBorrowingRequest $request, RequestBorrowingAction $action, AuditLogService $audit): BorrowingResource
    {
        $borrowing = $action->execute($request->user(), Asset::findOrFail($request->integer('asset_id')), $request->input('borrower_note'));
        $audit->record($request->user(), 'borrowing.requested', $borrowing, null, $borrowing->getAttributes());

        return new BorrowingResource($borrowing->load(['asset', 'borrower']));
    }

    public function show(Borrowing $borrowing): BorrowingResource
    {
        $this->authorize('view', $borrowing);

        return new BorrowingResource($borrowing->load(['asset', 'borrower']));
    }

    public function approve(ApproveBorrowingRequest $request, Borrowing $borrowing, ApproveBorrowingAction $action, AuditLogService $audit, NotificationService $notifications): BorrowingResource
    {
        $old = $borrowing->getAttributes();
        $result = $action->execute($request->user(), $borrowing);
        $audit->record($request->user(), 'borrowing.approved', $result, $old, $result->getAttributes());
        $notifications->scheduleReminder($result);
        $notifications->queueApproval($result);

        return new BorrowingResource($result->load(['asset', 'borrower']));
    }

    public function reject(RejectBorrowingRequest $request, Borrowing $borrowing, RejectBorrowingAction $action, AuditLogService $audit, NotificationService $notifications): BorrowingResource
    {
        $old = $borrowing->getAttributes();
        $result = $action->execute($request->user(), $borrowing, $request->string('rejection_reason')->toString());
        $audit->record($request->user(), 'borrowing.rejected', $result, $old, $result->getAttributes());
        $notifications->queueRejection($result);

        return new BorrowingResource($result);
    }

    public function cancel(CancelBorrowingRequest $request, Borrowing $borrowing, CancelBorrowingAction $action, AuditLogService $audit): BorrowingResource
    {
        $old = $borrowing->getAttributes();
        $result = $action->execute($request->user(), $borrowing, $request->input('cancellation_reason'));
        $audit->record($request->user(), 'borrowing.cancelled', $result, $old, $result->getAttributes());

        return new BorrowingResource($result);
    }

    public function checkout(CheckoutBorrowingRequest $request, Borrowing $borrowing, CheckoutBorrowingAction $action, AuditLogService $audit): BorrowingResource
    {
        $old = $borrowing->getAttributes();
        $result = $action->execute($request->user(), $borrowing, $request->enum('checkout_condition', AssetCondition::class));
        $audit->record($request->user(), 'borrowing.checked_out', $result, $old, $result->getAttributes());

        return new BorrowingResource($result);
    }

    public function submitReturn(SubmitReturnRequest $request, Borrowing $borrowing, SubmitReturnAction $action, AuditLogService $audit): BorrowingResource
    {
        $old = $borrowing->getAttributes();
        $result = $action->execute($request->user(), $borrowing, $request->string('return_evidence_path')->toString(), $request->input('return_note'));
        $audit->record($request->user(), 'borrowing.return_submitted', $result, $old, $result->getAttributes());

        return new BorrowingResource($result);
    }

    public function verifyReturn(VerifyReturnRequest $request, Borrowing $borrowing, VerifyReturnAction $action, AuditLogService $audit, NotificationService $notifications): BorrowingResource
    {
        $old = $borrowing->getAttributes();
        $result = $action->execute($request->user(), $borrowing, $request->enum('return_condition', AssetCondition::class), $request->input('return_verification_note'));
        $audit->record($request->user(), 'borrowing.return_verified', $result, $old, $result->getAttributes());
        $notifications->queueReturnVerification($result);

        return new BorrowingResource($result);
    }
}
