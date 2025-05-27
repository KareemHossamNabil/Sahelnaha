<?php

namespace App\Services;

use App\Models\User;
use App\Models\UserNotification;
use Illuminate\Support\Facades\Log;

class NotificationHandler
{
    protected $firebaseService;

    public function __construct(FirebaseNotificationService $firebaseService)
    {
        $this->firebaseService = $firebaseService;
    }

    public function sendNotification($user, $title, $body, $data = [])
    {
        try {
            if (!$user instanceof User) {
                $user = User::find($user);
            }

            if (!$user) {
                Log::error('User not found for notification', ['user_id' => $user]);
                return false;
            }

            // Send Firebase notification if user has FCM token
            if ($user->fcm_token) {
                $notificationSent = $this->firebaseService->sendNotification(
                    $user->fcm_token,
                    $title,
                    $body,
                    $data,
                    $user->id
                );

                if ($notificationSent) {
                    // Store notification in database only if FCM was successful
                    UserNotification::create([
                        'user_id' => $user->id,
                        'title' => $title,
                        'body' => $body,
                        'data' => $data,
                        'read_at' => null
                    ]);
                    return true;
                }
            }

            return false;
        } catch (\Exception $e) {
            Log::error('Failed to send notification', [
                'user_id' => $user->id,
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }

    public function sendMulticastNotification($users, $title, $body, $data = [])
    {
        try {
            if (!is_array($users)) {
                $users = [$users];
            }

            $userIds = [];
            $tokens = [];

            foreach ($users as $user) {
                if (!$user instanceof User) {
                    $user = User::find($user);
                }

                if ($user) {
                    $userIds[] = $user->id;
                    if ($user->fcm_token) {
                        $tokens[] = $user->fcm_token;
                    }
                }
            }

            if (empty($tokens) || empty($userIds)) {
                Log::warning('No valid tokens or user IDs for multicast notification');
                return false;
            }

            // Send Firebase multicast notification
            $notificationSent = $this->firebaseService->sendNotification(
                $tokens,
                $title,
                $body,
                $data,
                $userIds
            );

            if ($notificationSent) {
                // Store notifications in database only if FCM was successful
                foreach ($userIds as $userId) {
                    UserNotification::create([
                        'user_id' => $userId,
                        'title' => $title,
                        'body' => $body,
                        'data' => $data,
                        'read_at' => null
                    ]);
                }
                return true;
            }

            return false;
        } catch (\Exception $e) {
            Log::error('Failed to send multicast notification', [
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }
}
