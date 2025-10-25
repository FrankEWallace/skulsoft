<?php

namespace App\Console\Commands\Finance;

use App\Actions\Student\PayOnlineFee;
use App\Models\Config\Config;
use App\Models\Finance\Transaction;
use App\Models\Student\Registration;
use App\Models\Student\Student;
use App\Support\CcavenueCrypto;
use App\Support\PaymentGatewayMultiAccountSeparator;
use Illuminate\Console\Command;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Http;

class CcavenueStatusCheck extends Command
{
    use PaymentGatewayMultiAccountSeparator;

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'ccavenue:status {refnum? : Reference Number}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'CCAvenue Status Check';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $refnum = $this->argument('refnum');

        $transaction = Transaction::query()
            ->where('is_online', 1)
            ->where('payment_gateway->name', 'ccavenue')
            ->when(empty($refnum), function ($q) {
                $q->where(function ($q) {
                    $q->whereNull('payment_gateway->status')
                        ->orWhere('payment_gateway->status', '!=', 'updated');
                });
            })
            ->when($refnum, function ($q, $refnum) {
                $q->where('payment_gateway->reference_number', $refnum);
            }, function ($q) {
                $q->whereNull('processed_at')
                    ->where('created_at', '<=', now()->subMinutes(10));
            })
            ->orderBy('created_at', 'desc')
            ->first();

        if (! $transaction) {
            $this->info('No transaction found');

            return;
        }

        $transaction->load('period');

        $config = Config::query()
            ->whereTeamId($transaction->period->team_id)
            ->whereName('finance')
            ->first();

        $pgAccount = Arr::get($transaction->payment_gateway, 'pg_account');

        $client = $this->getCredential(Arr::get($config->value, 'ccavenue_client'), $pgAccount);
        $secret = $this->getCredential(Arr::get($config->value, 'ccavenue_secret'), $pgAccount);
        $mode = (bool) Arr::get($config->value, 'enable_live_ccavenue_mode');

        $referenceNumber = Arr::get($transaction->payment_gateway, 'reference_number');

        $merchantJsonData = [
            'order_no' => $referenceNumber,
            'reference_no' => '',
        ];

        $merchantData = json_encode($merchantJsonData);
        $encryptedData = (new CcavenueCrypto)->encrypt($merchantData, $secret);
        $finalData = 'enc_request='.$encryptedData.'&access_code='.$client.'&command=orderStatusTracker&request_type=JSON&response_type=JSON&version=1.2';

        if ($mode) {
            $url = 'https://api.ccavenue.com/apis/servlet/DoWebTrans';
        } else {
            $url = 'https://apitest.ccavenue.com/apis/servlet/DoWebTrans';
        }

        $response = Http::acceptJson()
            ->post($url.'?'.$finalData, []);

        $information = explode('&', $response->body());
        $response = $this->getTransactionData(collect($information), 'enc_response');

        $response = (new CcavenueCrypto)->decrypt(trim($response), $secret);
        $response = json_decode($response);

        if (empty($response)) {
            $this->info('Error desc: Empty Response');
            $this->failTransaction($transaction, 'Empty Error Response');

            return;
        }

        if (! empty($response->error_desc)) {
            $this->info('Error desc: '.$response->error_desc);
            $this->failTransaction($transaction, $response->error_desc);

            return;
        }

        if (! empty($response->error_code)) {
            $this->info('Error code: '.$response->error_code);
            $this->failTransaction($transaction, $response->error_code);

            return;
        }

        if ($response->param_value2 != $transaction->uuid) {
            $this->info('Invalid transaction uuid');
            $this->failTransaction($transaction, 'transaction_uuid_mismatch');

            return;
        }

        if (! in_array($response->order_status, ['Success', 'Shipped'])) {
            $this->info('Transaction not completed');
            $this->failTransaction($transaction, 'failed_transaction');

            return;
        }

        if ($response->order_amt != $transaction->amount->value) {
            $this->info('Amount mismatch');
            $this->failTransaction($transaction, 'amount_mismatch');

            return;
        }

        if ($transaction->processed_at->value) {
            $this->info('Transaction already processed');

            return;
        }

        if ($response->param_value1 == 'student_fee') {
            \DB::beginTransaction();

            $student = Student::find($transaction->transactionable_id);

            (new PayOnlineFee)->studentFeePayment($student, $transaction);

            $transaction->payment_gateway = array_merge($transaction->payment_gateway, [
                'status' => 'updated',
                'tracking_id' => $response->reference_no,
                'bank_ref_no' => $response->order_bank_ref_no,
            ]);
            $transaction->processed_at = now()->toDateTimeString();
            $transaction->save();

            \DB::commit();
        } elseif ($response->param_value1 == 'registration_fee') {
            \DB::beginTransaction();

            $registration = Registration::find($transaction->transactionable_id);

            (new PayOnlineFee)->registrationFeePayment($registration, $transaction);

            $transaction->payment_gateway = array_merge($transaction->payment_gateway, [
                'status' => 'updated',
                'tracking_id' => $response->reference_no,
                'bank_ref_no' => $response->order_bank_ref_no,
            ]);
            $transaction->processed_at = now()->toDateTimeString();
            $transaction->save();

            \DB::commit();
        } else {
            $this->info('Invalid fee');
            $this->failTransaction($transaction, 'invalid_fee');

            return;
        }
    }

    private function failTransaction(Transaction $transaction, $code)
    {
        $transaction->payment_gateway = array_merge($transaction->payment_gateway, [
            'status' => 'updated',
            'code' => $code,
        ]);
        $transaction->save();
    }

    private function getTransactionData(Collection $data, $key)
    {
        $item = $data->first(function ($item) use ($key) {
            return starts_with($item, $key.'=');
        });

        return explode('=', $item)[1] ?? null;
    }
}
