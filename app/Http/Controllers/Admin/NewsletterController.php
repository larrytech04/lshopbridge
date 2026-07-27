<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\NewsletterSubscriber;
use Illuminate\Http\Request;
use Illuminate\View\View;

class NewsletterController extends Controller
{
    public function index(Request $request): View
    {
        $query = NewsletterSubscriber::query();
        $status = $request->query('status', 'subscribed');
        if ($status) {
            $query->where('status', $status);
        }

        return view('admin.newsletter.index', [
            'subscribers' => $query->latest('subscribed_at')->paginate(30)->withQueryString(),
            'status' => $status,
            'totalSubscribed' => NewsletterSubscriber::where('status', 'subscribed')->count(),
        ]);
    }
}
