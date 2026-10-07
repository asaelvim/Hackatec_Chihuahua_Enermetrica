<?php

namespace App\Http\Middleware;

use App\Models\Device;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class VerifyDeviceToken
{
    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        /** @var Device|null $device */
        $device = $request->route('device');
        $token = $request->bearerToken();

        if (! $device || ! $token || ! hash_equals((string) $device->api_token, $token)) {
            abort(401, 'Token de dispositivo inválido.');
        }

        return $next($request);
    }
}
