<?php

namespace App\Http\Controllers\Shop;

use App\Enums\ShopProductType;
use App\Http\Controllers\Controller;
use App\Models\ShopOrder;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ShopOrderController extends Controller
{
    public function index(Request $request): View
    {
        return $this->list($request, false);
    }

    /**
     * Same order history, scoped to orders containing at least one digitally
     * delivered item (everything except physical products) — the delivery
     * codes/download links already live on the shared order-detail partial,
     * this just gives that content its own dedicated nav entry point.
     */
    public function digital(Request $request): View
    {
        return $this->list($request, true);
    }

    private function list(Request $request, bool $digitalOnly): View
    {
        $query = $request->user()->shopOrders()->with('items.product', 'items.esimProvisioning')->latest();

        if ($request->filled('q')) {
            $query->where('reference', 'like', '%'.$request->string('q').'%');
        }

        // "Expired orders" = orders that never completed (failed/refunded), hidden by default.
        if (! $request->boolean('expired')) {
            $query->whereNotIn('status', ['failed', 'refunded']);
        }

        if ($digitalOnly) {
            $query->whereHas('items.product', fn ($q) => $q->where('type', '!=', ShopProductType::Physical->value));
        }

        return view('shop.orders.index', [
            'orders' => $query->paginate(12)->withQueryString(),
            'digitalOnly' => $digitalOnly,
        ]);
    }

    public function show(Request $request, ShopOrder $order): View
    {
        abort_unless($order->user_id === $request->user()->id, 403);

        return view('shop.orders.show', ['order' => $order->load('items.product', 'items.variant', 'items.esimProvisioning')]);
    }
}
