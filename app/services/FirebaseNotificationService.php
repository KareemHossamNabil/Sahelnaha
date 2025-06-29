<?php

namespace App\Services;

use App\Models\User;
use App\Models\UserNotification;
use Illuminate\Support\Facades\Log;
use Kreait\Firebase\Factory;
use Kreait\Firebase\Messaging\CloudMessage;
use Kreait\Firebase\Messaging\Notification as FirebaseNotification;
use Exception;

class FirebaseNotificationService
{
    protected $messaging;

    public function __construct()
    {
        $factory = (new Factory)
            ->withServiceAccount(storage_path('app/firebase/sahelnaha-notifications-firebase-adminsdk-fbsvc-3b17835e64.json'));

        $this->messaging = $factory->createMessaging();
    }

    public function sendNotification($token, $title, $body, $type, $data = [], $userId = null)
    {
        try {
            if (empty($token)) {
                throw new Exception("FCM token is empty");
            }

            Log::info('Sending FCM notification', [
                'token' => $token,
                'title' => $title,
                'body' => $body,
                'type' => $type,
                'data' => $data,
                'user_id' => $userId
            ]);

            $message = CloudMessage::new()
                ->withNotification(FirebaseNotification::create($title, $body))
                ->withData($data);

            $this->messaging->send($message, $token);

            if ($userId) {
                $this->storeNotification($userId, $title, $body, $data, $type);
            }

            return true;
        } catch (Exception $e) {
            $this->handleError($e, $userId, $token);
            return false;
        }
    }

    protected function storeNotification($userId, $title, $body, $data, $type)
    {
        try {
            Log::info('Attempting to store notification', [
                'user_id' => $userId,
                'title' => $title,
                'body' => $body,
                'type' => $type,
                'data' => $data
            ]);

            if (!$userId) {
                throw new Exception("User ID is required to store notification");
            }

            $notification = UserNotification::create([
                'user_id' => $userId,
                'title' => $title,
                'body' => $body,
                'type' => $type,
                'data' => $data,
                'read_at' => null
            ]);

            Log::info('Notification stored successfully', [
                'notification_id' => $notification->id
            ]);

            return $notification;
        } catch (Exception $e) {
            Log::error("Failed to store notification", [
                'user_id' => $userId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            throw $e;
        }
    }

    protected function handleError(Exception $e, $userId, $token)
    {
        Log::error("Notification sending failed", [
            'user_id' => $userId,
            'token' => $token,
            'error' => $e->getMessage()
        ]);


        if (str_contains($e->getMessage(), 'Requested entity was not found') && $userId) {
            User::where('id', $userId)->update(['fcm_token' => null]);
            Log::info("Invalid FCM token cleared for user: " . $userId);
        }
    }
}
