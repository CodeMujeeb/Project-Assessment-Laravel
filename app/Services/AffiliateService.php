<?php

namespace App\Services;

use App\Exceptions\AffiliateCreateException;
use App\Mail\AffiliateCreated;
use App\Models\Affiliate;
use App\Models\Merchant;
use App\Models\Order;
use App\Models\User;
use Illuminate\Support\Facades\Mail;

class AffiliateService
{
    public function __construct(
        protected ApiService $apiService
    ) {
    }

    /**
     * Create a new affiliate for the merchant with the given commission rate.
     *
     * @param  Merchant $merchant
     * @param  string $email
     * @param  string $name
     * @param  float $commissionRate
     * @return Affiliate
     */
    public function register(Merchant $merchant, string $email, string $name, float $commissionRate): Affiliate
    {
        $email_in_use_as_merchant = Merchant::whereHas('user', function ($query) use ($email) {
            $query->where('email', $email);
        })->exists();

        if ($email_in_use_as_merchant || $merchant->affiliates()->count() > 0) {
            throw new AffiliateCreateException;
        }

        $user = User::create([
            'email' => $email,
            'name' => $name,
            'type' => User::TYPE_AFFILIATE
        ]);
        $discountCodeResponse = $this->apiService->createDiscountCode($merchant);
        $discountCode = $discountCodeResponse['code'];

        return Affiliate::create([
            'user_id' => $user->id,
            'merchant_id' => $merchant->id,
            'commission_rate' => $commissionRate,
            'discount_code' => $discountCode
        ]);
    }
}
