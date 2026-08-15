<?php

namespace App\Http\Controllers;

use App\Http\Resources\NotificationLogResource;
use App\Models\NotificationLog;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class NotificationController extends Controller
{
    public function index(): AnonymousResourceCollection
    {
        return NotificationLogResource::collection(NotificationLog::query()->with(['borrowing', 'recipient'])->where('recipient_user_id', request()->user()->id)->paginate());
    }
}
