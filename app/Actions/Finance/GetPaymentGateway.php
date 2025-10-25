<?php

namespace App\Actions\Finance;

use App\Models\Config\Config;
use Illuminate\Support\Arr;

class GetPaymentGateway
{
    public function execute(?int $teamId = null): array
    {
        if (auth()->user()) {
            $financeConfig = config('config.finance');
        } else {
            $financeConfig = Config::query()
                ->where('team_id', $teamId)
                ->where('name', 'finance')
                ->first()
                ?->value;
        }

        $paymentGateways = [];
        if (Arr::get($financeConfig, 'enable_razorpay')) {
            $paymentGateways[] = [
                'value' => 'razorpay',
                'label' => 'Razorpay',
            ];
        }

        if (Arr::get($financeConfig, 'enable_paystack')) {
            $paymentGateways[] = [
                'value' => 'paystack',
                'label' => 'Paystack',
            ];
        }

        if (Arr::get($financeConfig, 'enable_stripe')) {
            $paymentGateways[] = [
                'value' => 'stripe',
                'label' => 'Stripe',
                'key' => Arr::get($financeConfig, 'stripe_client'),
            ];
        }

        if (Arr::get($financeConfig, 'enable_paypal')) {
            $paymentGateways[] = [
                'value' => 'paypal',
                'label' => 'PayPal',
                'key' => Arr::get($financeConfig, 'paypal_client'),
            ];
        }

        if (Arr::get($financeConfig, 'enable_payzone')) {
            $paymentGateways[] = [
                'value' => 'payzone',
                'label' => 'Payzone',
                'key' => Arr::get($financeConfig, 'payzone_merchant'),
            ];
        }

        if (Arr::get($financeConfig, 'enable_ccavenue')) {
            $paymentGateways[] = [
                'value' => 'ccavenue',
                'label' => 'CCAvenue',
                'key' => '',
            ];
        }

        if (Arr::get($financeConfig, 'enable_billdesk')) {
            $paymentGateways[] = [
                'value' => 'billdesk',
                'label' => 'Billdesk',
                'key' => '',
            ];
        }

        if (Arr::get($financeConfig, 'enable_billplz')) {
            $paymentGateways[] = [
                'value' => 'billplz',
                'label' => 'Billplz',
                'key' => '',
            ];
        }

        return $paymentGateways;
    }
}
