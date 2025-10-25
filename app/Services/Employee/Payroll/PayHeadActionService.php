<?php

namespace App\Services\Employee\Payroll;

use App\Models\Employee\Payroll\PayHead;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;

class PayHeadActionService
{
    public function reorder(Request $request): void
    {
        $payHeads = $request->pay_heads ?? [];

        $allPayHeads = PayHead::query()
            ->byTeam()
            ->get();

        foreach ($payHeads as $index => $payHeadItem) {
            $payHead = $allPayHeads->firstWhere('uuid', Arr::get($payHeadItem, 'uuid'));

            if (! $payHead) {
                continue;
            }

            $payHead->position = $index + 1;
            $payHead->save();
        }
    }
}
