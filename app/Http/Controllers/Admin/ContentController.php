<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\Audit\AuditLogger;
use App\Services\Settings\SettingsService;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ContentController extends Controller
{
    public function index(): View
    {
        return view('admin.content.index', ['groups' => config('cms.blocks', [])]);
    }

    public function update(Request $request, SettingsService $settings, AuditLogger $audit)
    {
        foreach (collect(config('cms.blocks', []))->flatten(1) as $block) {
            // $block = [key, label, input, default]
            $key = $block[0];
            if ($request->has($key)) {
                $settings->set($key, (string) $request->input($key, ''), 'string', 'cms');
            }
        }

        $audit->log('admin.content.updated', 'Site content updated');

        return back()->with('success', __('Content saved.'));
    }
}
