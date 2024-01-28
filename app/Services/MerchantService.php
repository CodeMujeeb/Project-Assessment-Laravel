<?php

namespace App\Services;

use App\Jobs\PayoutOrderJob;
use App\Models\Affiliate;
use App\Models\Merchant;
use App\Models\Order;
use App\Models\User;
use Illuminate\Support\Arr;

class MerchantService
{
    /**
     * Register a new user and associated merchant.
     * Hint: Use the password field to store the API key.
     * Hint: Be sure to set the correct user type according to the constants in the User model.
     *
     * @param array{domain: string, name: string, email: string, api_key: string} $data
     * @return Merchant
     */
    public function register(array $data): Merchant
    {
        $user = User::create([
            'name' => $data['name'],
            'password' => $data['api_key'],
            'email' => $data['email'],
            'type' => User::TYPE_MERCHANT
        ]);
        return $user->merchant()->save(new Merchant ([
            'domain' => $data['domain'],
            'display_name' => $data['name'],
            'turn_customers_into_affiliates' => $data['turn_customers_into_affiliates'] ?? true,
            'default_commission_rate' => $data['default_commission_rate'] ?? 0.1,
        ]));
    }

    /**
     * Update the user
     *
     * @param array{domain: string, name: string, email: string, api_key: string} $data
     * @return void
     */
    public function updateMerchant(User $user, array $data)
    {
        $user->update([
            'name' => $data['name'],
            'password' => $data['api_key'],
            'email' => $data['email'],
            'type' => User::TYPE_MERCHANT,
        ]);

        $user->merchant->update([
            'domain' => $data['domain'],
            'display_name' => $data['name'],
            'turn_customers_into_affiliates' => $data['turn_customers_into_affiliates'] ?? true,
            'default_commission_rate' => $data['default_commission_rate'] ?? 0.1,
        ]);
    }

    /**
     * Find a merchant by their email.
     * Hint: You'll need to look up the user first.
     *
     * @param string $email
     * @return Merchant|null
     */
    public function findMerchantByEmail(string $email): ?Merchant
    {
        return Merchant::whereHas('user', function ($query) use ($email) {
            $query->where('email', $email);
        })->first();
    }

    /**
     * Pay out all of an affiliate's orders.
     * Hint: You'll need to dispatch the job for each unpaid order.
     *
     * @param Affiliate $affiliate
     * @return void
     */
    public function payout(Affiliate $affiliate)
    {
        $unpaidOrders = $affiliate->orders()
            ->where('payout_status', Order::STATUS_UNPAID)
            ->get();

        foreach ($unpaidOrders as $order) {
            if ($order->refresh()->payout_status != Order::STATUS_UNPAID) {
                continue;
            }
            PayoutOrderJob::dispatch($order);
        }
    }
}
