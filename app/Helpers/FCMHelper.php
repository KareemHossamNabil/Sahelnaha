<?php

namespace App\Helpers;

use Illuminate\Support\Facades\Http;
use Google_Client;
use GuzzleHttp\Client as HttpClient;

class FcmHelper
{
    public static function sendNotification($fcmToken, $title, $body)
    {
        $client = new Google_Client();
        $client->useApplicationDefaultCredentials();
        $client->setAuthConfig(public_path('json/firebase_credential.json'));
        $client->addScope('https://www.googleapis.com/auth/firebase.messaging');

        $accessToken = $client->fetchAccessTokenWithAssertion()['access_token'];

        $projectId = 'sahelnaha-notifications';  
        $url = "https://fcm.googleapis.com/v1/projects/$projectId/messages:send";

        $http = new HttpClient();

        $response = $http->post($url, [
            'headers' => [
                'Authorization' => "Bearer $accessToken",
                'Content-Type' => 'application/json',
            ],
            'json' => [
                'message' => [
                    'token' => $fcmToken,
                    'notification' => [
                        'title' => $title,
                        'body' => $body,
                    ],
                    'android' => [
                        'priority' => 'high',
                    ],
                ],
            ],
        ]);

        $responseBody = json_decode((string) $response->getBody(), true);

        if (isset($responseBody['error'])) {
            return response()->json(['error' => $responseBody['error']], 500);
        }

        return $responseBody;
    }
}
