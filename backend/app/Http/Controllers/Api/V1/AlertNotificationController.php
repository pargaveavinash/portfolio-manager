<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\AlertNotification;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

class AlertNotificationController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $notifications = AlertNotification::where('user_id', $request->user()->id)
            ->orderBy('created_at', 'desc')
            ->get();
            
        return response()->json(['data' => $notifications]);
    }

    public function markAsRead(Request $request, AlertNotification $notification): JsonResponse
    {
        if ($notification->user_id !== $request->user()->id) {
            throw new AccessDeniedHttpException();
        }

        $notification->update(['is_read' => true]);

        return response()->json(['data' => $notification]);
    }
}
