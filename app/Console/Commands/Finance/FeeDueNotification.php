<?php

namespace App\Console\Commands\Finance;

use App\Jobs\Finance\SendFeeDueNotification;
use App\Models\Config\Template;
use App\Models\Student\Student;
use App\Models\Team;
use Illuminate\Console\Command;

class FeeDueNotification extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'fee-due:notice';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Fee due notification';

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
        $mailTemplateCode = 'fee-due-notification';

        $mailTemplate = Template::query()
            ->where('type', 'mail')
            ->whereCode($mailTemplateCode)
            ->first();

        if (! $mailTemplate) {
            $this->error('Could not find mail template.');

            return 1;
        }

        $dueOn = today()->toDateString();

        $teams = Team::query()
            ->get();

        foreach ($teams as $team) {
            $students = Student::query()
                ->select('students.id', 'fee_groups.name as fee_group_name', \DB::raw('SUM(student_fees.total - student_fees.paid) as due_fee'),
                    \DB::raw('(SELECT MAX(COALESCE(student_fees.due_date, fee_installments.due_date))) as final_due_date'), \DB::raw('REGEXP_REPLACE(CONCAT_WS(" ", first_name, middle_name, third_name, last_name), "[[:space:]]+", " ") as name'), 'admissions.code_number', 'admissions.joining_date', 'admissions.leaving_date', 'batches.uuid as batch_uuid', 'batches.name as batch_name', 'courses.uuid as course_uuid', 'courses.name as course_name', 'contacts.father_name', 'contacts.contact_number', 'contacts.email')
                ->join('student_fees', 'students.id', '=', 'student_fees.student_id')
                ->join('fee_installments', 'student_fees.fee_installment_id', '=', 'fee_installments.id')
                ->join('fee_groups', 'fee_installments.fee_group_id', '=', 'fee_groups.id')
                ->join('contacts', 'students.contact_id', '=', 'contacts.id')
                ->join('admissions', 'students.admission_id', '=', 'admissions.id')
                ->join('batches', 'students.batch_id', '=', 'batches.id')
                ->leftJoin('courses', 'batches.course_id', '=', 'courses.id')
                ->whereNotNull('contacts.email')->where('contacts.email', '!=', '')
                ->where('contacts.team_id', $team->id)
                ->whereDate(\DB::raw('COALESCE(student_fees.due_date, fee_installments.due_date)'), '<=', $dueOn)
                ->havingRaw('SUM(student_fees.total - student_fees.paid) > 0')
                ->groupBy('students.id', 'fee_groups.name')
                ->get()
                ->toArray();

            collect($students)->chunk(20)->each(function ($chunk) {
                SendFeeDueNotification::dispatch($chunk, []);
            });
        }
    }
}
