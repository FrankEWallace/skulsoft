<?php

namespace App\Http\Controllers\Custom;

use App\Http\Controllers\Controller;
use App\Models\Finance\Transaction;
use Illuminate\Http\Request;

class TransactionUserTransferController extends Controller
{
    public function __invoke(Request $request)
    {
        $fromUserId = $request->query('from');
        $toUserId = $request->query('to');
        $startDate = $request->query('start_date', today()->toDateString());

        if (empty($fromUserId) || empty($toUserId)) {
            return response()->json([
                'message' => 'From and to user IDs are required',
            ], 422);
        }

        if ($fromUserId === $toUserId) {
            return response()->json([
                'message' => 'From and to user IDs cannot be the same',
            ], 422);
        }

        $transactions = Transaction::query()
            ->where('user_id', $fromUserId)
            ->where('created_at', '>=', $startDate)
            ->get();

        $transactions->each(function ($transaction) use ($toUserId, $fromUserId) {
            $transaction->user_id = $toUserId;
            $transaction->setMeta(['old_user_id' => $fromUserId]);
            $transaction->save();
        });

        return response()->json([
            'message' => 'Transactions transferred successfully',
        ]);
    }
}
