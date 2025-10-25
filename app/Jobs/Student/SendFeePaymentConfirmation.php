<?php

namespace App\Jobs\Student;

use App\Actions\SendMailTemplate;
use App\Actions\SendSMS;
use App\Concerns\SetConfigForJob;
use App\Models\Config\Template;
use App\Models\Finance\Transaction;
use App\Models\Student\Student;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Arr;

class SendFeePaymentConfirmation implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels, SetConfigForJob;

    protected $params;

    public function __construct(array $params)
    {
        $this->params = $params;
    }

    public function handle()
    {
        $teamId = Arr::get($this->params, 'team_id');

        $this->setConfig($teamId, ['general', 'system', 'mail', 'sms']);

        $templateCode = 'online-fee-payment-confirmation';

        $templates = Template::query()
            ->whereCode($templateCode)
            ->whereNotNull('enabled_at')
            ->get();

        if (!$templates->count()) {
            return;
        }

        $mailTemplate = $templates->where('type', 'mail')->first();
        $smsTemplate = $templates->where('type', 'sms')->first();
        $whatsappTemplate = $templates->where('type', 'whatsapp')->first();
        $pushTemplate = $templates->where('type', 'push')->first();

        $student = Student::query()
            ->summary($teamId)
            ->findOrFail(Arr::get($this->params, 'student_id'));

        $transaction = Transaction::query()
            ->where('id', '=', Arr::get($this->params, 'transaction_id'))
            ->firstOrFail();

        $variables = [
            'name' => $student->name,
            'course_name' => $student->course_name,
            'batch_name' => $student->batch_name,
            'fee_title' => $transaction->getMeta('fee_group_name'),
            'voucher_number' => $transaction->code_number,
            'reference_number' => Arr::get($transaction->payment_gateway, 'reference_number'),
            'amount' => $transaction->amount->formatted,
            'datetime' => $transaction->processed_at->formatted,
            'payment_method' => trans('student.payment.online'),
            'url' => url('/app/students/' . $student->uuid . '/fee'),
        ];

        if ($mailTemplate) {
            (new SendMailTemplate)->execute(
                email: $student->email,
                variables: $variables,
                template: $mailTemplate,
            );
        }

        if ($smsTemplate) {
            $params = [
                'template_id' => $smsTemplate->getMeta('template_id'),
                'recipients' => [
                    [
                        'mobile' => $student->contact_number,
                        'message' => $smsTemplate->content,
                        'variables' => $variables,
                    ],
                ],
            ];

            (new SendSMS)->execute($params);
        }

        if ($whatsappTemplate) {
            // send whatsapp
        }

        if ($pushTemplate) {
            // send push
        }
    }
}
