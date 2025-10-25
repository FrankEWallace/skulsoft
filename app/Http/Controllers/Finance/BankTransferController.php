<?php

namespace App\Http\Controllers\Finance;

use App\Http\Controllers\Controller;
use App\Models\Finance\BankTransfer;

class BankTransferController extends Controller
{
    public function downloadMedia(BankTransfer $bankTransfer, string $uuid)
    {
        return $bankTransfer->downloadMedia($uuid);
    }
}
