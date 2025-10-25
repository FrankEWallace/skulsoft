<?php

namespace App\Console\Commands\Student;

use App\Actions\Student\UpdateServiceRequest;
use App\Models\Student\ServiceRequest;
use Illuminate\Console\Command;

class UpdateServiceAllocation extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'student:update-service-allocation';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Update service allocation';

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
        $serviceRequests = ServiceRequest::query()
            ->where('date', '<=', today()->toDateString())
            ->where('status', 'approved')
            ->get();

        foreach ($serviceRequests as $serviceRequest) {
            (new UpdateServiceRequest)->execute($serviceRequest);
            $serviceRequest->setMeta(['is_updated' => true]);
            $serviceRequest->save();
        }
    }
}
