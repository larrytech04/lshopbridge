<?php

namespace App\Http\Controllers;

use App\Notifications\TestPush;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Stores/removes the browser's own Push API subscription for the current
 * user, called from the "Web Push" toggle in Settings > Notifications (see
 * resources/js/app.js's initWebPush()). This is the plumbing that toggle was
 * always missing — flipping it on used to just flip a database preference
 * with nothing behind it.
 */
class PushSubscriptionController extends Controller
{
    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'endpoint' => ['required', 'string', 'max:2048'],
            'keys.p256dh' => ['required', 'string'],
            'keys.auth' => ['required', 'string'],
        ]);

        $request->user()->updatePushSubscription(
            $data['endpoint'],
            $data['keys']['p256dh'],
            $data['keys']['auth'],
        );

        return response()->json(['status' => 'subscribed']);
    }

    public function destroy(Request $request): JsonResponse
    {
        $data = $request->validate(['endpoint' => ['required', 'string', 'max:2048']]);

        $request->user()->deletePushSubscription($data['endpoint']);

        return response()->json(['status' => 'unsubscribed']);
    }

    public function test(Request $request): JsonResponse
    {
        $user = $request->user();

        if ($user->pushSubscriptions()->doesntExist()) {
            return response()->json(['status' => 'no-subscription'], 422);
        }

        $user->notify(new TestPush);

        return response()->json(['status' => 'sent']);
    }
}
