<?php

namespace App\Jobs\Communication;

use App\Concerns\SetConfigForJob;
use App\Models\Communication\Communication;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Mail;

class SendEmail implements ShouldQueue
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

        $this->setConfig($teamId, ['general', 'system', 'mail']);

        $communication = Communication::query()
            ->findOrFail(Arr::get($this->params, 'communication_id'));

        $recipients = Arr::get($this->params, 'recipients');

        foreach ($recipients as $recipient) {
            Mail::to($recipient)->send(new \App\Mail\Communication\Email($communication));
        }
    }
}
