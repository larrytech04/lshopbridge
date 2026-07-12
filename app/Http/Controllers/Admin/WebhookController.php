<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\WebhookEvent;
use Illuminate\Http\Request;
use Illuminate\View\View;

class WebhookController extends Controller
{
    public function index(Request $request): View
    {
        $query = WebhookEvent::query();
        if ($status = $request->query('status')) {
            $query->where('status', $status);
        }
        if ($provider = $request->query('provider')) {
            $query->where('provider_code', $provider);
        }

        return view('admin.webhooks.index', [
            'events' => $query->latest()->paginate(25)->withQueryString(),
            'filters' => $request->only('status', 'provider'),
        ]);
    }

    public function show(WebhookEvent $event): View
    {
        return view('admin.webhooks.show', ['event' => $event->load('related')]);
    }
}
