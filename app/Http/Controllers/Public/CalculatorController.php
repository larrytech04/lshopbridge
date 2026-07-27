<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Services\Funding\FundingService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CalculatorController extends Controller
{
    public function quote(Request $request, FundingService $funding): JsonResponse
    {
        $data = $request->validate([
            'amount' => ['required', 'numeric', 'min:1'],
            'app_type' => ['nullable', 'string'],
        ]);

        return response()->json($funding->quote((float) $data['amount'], $data['app_type'] ?? null, $request->user()));
    }
}
