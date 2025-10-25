<?php

namespace App\Services\Library;

use App\Enums\Library\IssueTo;
use Illuminate\Http\Request;

class BookWiseTransactionService
{
    public function preRequisite(Request $request)
    {
        $to = IssueTo::getOptions();

        return compact('to');
    }
}
