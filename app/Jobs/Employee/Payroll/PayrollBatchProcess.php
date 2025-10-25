<?php

namespace App\Jobs\Employee\Payroll;

use App\Actions\Employee\Payroll\GeneratePayrollNumber;
use App\Enums\Employee\Payroll\PayrollStatus;
use App\Models\Employee\Payroll\Payroll;
use Illuminate\Bus\Batch;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Bus;
use Throwable;

class PayrollBatchProcess implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $batchUuid;

    protected $teamId;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct(string $batchUuid, string $teamId)
    {
        $this->batchUuid = $batchUuid;
        $this->teamId = $teamId;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        // logger()->info('Processing payroll batch', ['batch_uuid' => $this->batchUuid, 'team_id' => $this->teamId]);

        $jobs = [];

        Payroll::query()
            ->where('meta->batch_uuid', $this->batchUuid)
            ->where('meta->team_id', $this->teamId)
            ->where('status', PayrollStatus::INITIATED->value)
            ->chunk(20, function ($chunkedPayrolls) use (&$jobs) {
                foreach ($chunkedPayrolls as $payroll) {
                    $jobs[] = new PayrollProcess($payroll, $this->teamId);
                }
            });

        $batchUuid = $this->batchUuid;
        $teamId = $this->teamId;

        Bus::batch($jobs)
            ->then(function (Batch $batch) {
                // logger()->info('Payroll batch processed', ['batch_uuid' => $batchUuid, 'team_id' => $teamId]);
            })
            ->catch(function (Batch $batch, Throwable $e) {
                // logger()->error('Payroll batch processing failed', ['batch_uuid' => $batchUuid, 'team_id' => $teamId, 'error' => $e->getMessage()]);
            })
            ->finally(function (Batch $batch) use ($batchUuid, $teamId) {
                // logger()->info('Payroll batch processed', ['batch_uuid' => $batchUuid, 'team_id' => $teamId]);
                (new GeneratePayrollNumber)->execute($batchUuid, $teamId);
            })
            ->dispatch();
    }
}
