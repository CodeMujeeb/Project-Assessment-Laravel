<?php

namespace App\Http\Controllers;

use App\Models\Merchant;
use App\Services\MerchantService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use App\Models\Order;

class MerchantController extends Controller
{
    public function __construct(
        MerchantService $merchantService
    ) {
    }

    /**
     * Useful order statistics for the merchant API.
     * 
     * @param Request $request Will include a from and to date
     * @return JsonResponse Should be in the form {count: total number of orders in range, commission_owed: amount of unpaid commissions for orders with an affiliate, revenue: sum order subtotals}
     */
    public function orderStats(Request $request, Merchant $merchant): JsonResponse
    {
        $from = $request->input('from', now()->subDay());
        $to = $request->input('to', now());

        $user = auth()->user();
        $merchant = $user->merchant;

        $orders = Order::where('merchant_id', $merchant->id)
            ->with('affiliate')
            ->whereBetween('created_at', [$from, $to])
            ->get();

        $noAffiliate = $orders->filter(function ($order) {
            return $order->affiliate_id === null;
        });

        $response = [
            'count' => $orders->count(),
            'revenue' => $orders->sum('subtotal'),
            'commissions_owed' => $orders->sum('commission_owed') - $noAffiliate->sum('commission_owed'),
        ];

        return response()->json($response);
    }
}
