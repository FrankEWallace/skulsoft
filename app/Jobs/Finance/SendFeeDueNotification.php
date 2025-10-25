<?php

namespace App\Jobs\Finance;

use App\Actions\SendMailTemplate;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;

class SendFeeDueNotification implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $students;

    protected $params;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct(Collection $students, array $params = [])
    {
        $this->students = $students;
        $this->params = $params;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        $this->students->each(function ($student) {
            if (empty(Arr::get($student, 'email'))) {
                return;
            }

            (new SendMailTemplate)->execute(
                email: Arr::get($student, 'email'),
                code: 'fee-due-notification',
                variables: [
                    'name' => Arr::get($student, 'name'),
                    'course' => Arr::get($student, 'course_name'),
                    'batch' => Arr::get($student, 'batch_name'),
                    'amount' => \Price::from(Arr::get($student, 'due_fee'))->formatted,
                    'due_date' => \Cal::date(Arr::get($student, 'final_due_date'))->formatted,
                ],
            );
        });
    }
}
