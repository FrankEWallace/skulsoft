<?php

namespace App\Contracts\Finance;

use App\Models\Finance\Transaction;
use App\Models\Student\Student;
use Illuminate\Http\Request;

interface PaymentGateway
{
    public function getName(): string;

    public function getVersion(): string;

    public function isEnabled(): void;

    public function getMultiplier(Request $request): float;

    public function supportedCurrencies(): array;

    public function unsupportedCurrencies(): array;

    public function initiatePayment(Request $request, Student $student, Transaction $transaction): array;

    public function confirmPayment(Request $request): Transaction;

    public function failPayment(Request $request): Transaction;
}
