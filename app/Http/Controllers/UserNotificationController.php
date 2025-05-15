<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\UserNotification;

class UserNotificationController extends Controller
{
    /**
     * Get all notifications for the authenticated user
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function index()
    {
        $user = Auth::user();
        $notifications = $user->notifications()->latest()->get();

        return response()->json([
            'status' => 'success',
            'data' => $notifications
        ]);
    }

    /**
     * Get notifications for pending service request offers
     */
    public function getPendingOffers()
    {
        $user = Auth::user();
        $notifications = $user->notifications()
            ->where('type', 'service_request_offer')
            ->whereJsonContains('data->status', 'pending')
            ->latest()
            ->get();

        return response()->json([
            'status' => 'success',
            'data' => $notifications
        ]);
    }

    /**
     * Get notifications for in-progress service request offers
     */
    public function getInProgressOffers()
    {
        $user = Auth::user();
        $notifications = $user->notifications()
            ->where('type', 'service_request_offer')
            ->whereJsonContains('data->status', 'in_progress')
            ->latest()
            ->get();

        return response()->json([
            'status' => 'success',
            'data' => $notifications
        ]);
    }

    /**
     * Get notifications for completed service request offers
     */
    public function getCompletedOffers()
    {
        $user = Auth::user();
        $notifications = $user->notifications()
            ->where('type', 'service_request_offer')
            ->whereJsonContains('data->status', 'completed')
            ->latest()
            ->get();

        return response()->json([
            'status' => 'success',
            'data' => $notifications
        ]);
    }

    /**
     * Get notifications for canceled service request offers
     */
    public function getCanceledOffers()
    {
        $user = Auth::user();
        $notifications = $user->notifications()
            ->where('type', 'service_request_offer')
            ->whereJsonContains('data->status', 'canceled')
            ->latest()
            ->get();

        return response()->json([
            'status' => 'success',
            'data' => $notifications
        ]);
    }
} 