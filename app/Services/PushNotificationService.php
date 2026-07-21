<?php

namespace App\Services;

use App\Models\Notification;
use App\Models\PushDeviceToken;
use Illuminate\Http\Client\Factory as HttpFactory;

class PushNotificationService
{
    public function __construct(private readonly HttpFactory $http) {}

    public function send(PushDeviceToken $device, Notification $notification): bool
    {
        $endpoint = config('push.endpoint');
        $accessToken = config('push.access_token');
        if (! is_string($endpoint) || $endpoint === '' || ! is_string($accessToken) || $accessToken === '') {
            return false;
        }

        $response = $this->http->withToken($accessToken)->acceptJson()->post($endpoint, [
            'message' => [
                'token' => $device->token,
                'notification' => ['title' => $notification->title, 'body' => $notification->body],
                'data' => collect($notification->data ?? [])->map(fn ($value) => is_scalar($value) ? (string) $value : json_encode($value))->all(),
            ],
        ]);

        return $response->successful();
    }
}
