<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\StoreDeviceTokenRequest;
use App\Http\Resources\DeviceTokenResource;
use App\Models\DeviceToken;
use Illuminate\Http\Request;

class DeviceTokenController extends Controller
{
    /**
     * Register (or refresh) the FCM token for the authenticated user's device.
     */
    public function store(StoreDeviceTokenRequest $request)
    {
        $deviceToken = DeviceToken::updateOrCreate(
            ['fcm_token' => $request->validated('fcm_token')],
            [
                'user_id' => $request->user()->id,
                'platform' => $request->validated('platform'),
            ]
        );

        return (new DeviceTokenResource($deviceToken))->response()->setStatusCode(201);
    }

    /**
     * Remove the FCM token, e.g. when the user logs out of the mobile app.
     */
    public function destroy(Request $request, DeviceToken $deviceToken)
    {
        abort_if($deviceToken->user_id !== $request->user()->id, 403);

        $deviceToken->delete();

        return response()->noContent();
    }
}
