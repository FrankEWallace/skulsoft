<?php

namespace App\Providers;

use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;
use Illuminate\Support\Facades\Gate;

class AuthServiceProvider extends ServiceProvider
{
    /**
     * The policy mappings for the application.
     *
     * @var array
     */
    protected $policies = [
        'App\Models\Config\Config' => 'App\Policies\Config\ConfigPolicy',
        'App\Models\User' => 'App\Policies\UserPolicy',
        'App\Models\Utility\Todo' => 'App\Policies\Utility\TodoPolicy',
        'App\Models\Post\Post' => 'App\Policies\Post\PostPolicy',
        'App\Models\Document' => 'App\Policies\DocumentPolicy',
        'App\Models\Academic\Period' => 'App\Policies\Academic\PeriodPolicy',
        'App\Models\Academic\Division' => 'App\Policies\Academic\DivisionPolicy',
        'App\Models\Academic\Course' => 'App\Policies\Academic\CoursePolicy',
        'App\Models\Academic\Batch' => 'App\Policies\Academic\BatchPolicy',
        'App\Models\Academic\Subject' => 'App\Policies\Academic\SubjectPolicy',
        'App\Models\Academic\CertificateTemplate' => 'App\Policies\Academic\CertificateTemplatePolicy',
        'App\Models\Academic\Certificate' => 'App\Policies\Academic\CertificatePolicy',
        'App\Models\Academic\ClassTiming' => 'App\Policies\Academic\ClassTimingPolicy',
        'App\Models\Academic\Timetable' => 'App\Policies\Academic\TimetablePolicy',
        'App\Models\Incharge' => 'App\Policies\InchargePolicy',
        'App\Models\Finance\FeeGroup' => 'App\Policies\Finance\FeeGroupPolicy',
        'App\Models\Finance\FeeHead' => 'App\Policies\Finance\FeeHeadPolicy',
        'App\Models\Finance\FeeConcession' => 'App\Policies\Finance\FeeConcessionPolicy',
        'App\Models\Finance\FeeStructure' => 'App\Policies\Finance\FeeStructurePolicy',
        'App\Models\Finance\LedgerType' => 'App\Policies\Finance\LedgerTypePolicy',
        'App\Models\Finance\Ledger' => 'App\Policies\Finance\LedgerPolicy',
        'App\Models\Finance\Transaction' => 'App\Policies\Finance\TransactionPolicy',
        'App\Models\Transport\Route' => 'App\Policies\Transport\RoutePolicy',
        'App\Models\Transport\Circle' => 'App\Policies\Transport\CirclePolicy',
        'App\Models\Transport\Fee' => 'App\Policies\Transport\FeePolicy',
        'App\Models\Transport\Vehicle\Vehicle' => 'App\Policies\Transport\Vehicle\VehiclePolicy',
        'App\Models\Transport\Vehicle\TravelRecord' => 'App\Policies\Transport\Vehicle\TravelRecordPolicy',
        'App\Models\Transport\Vehicle\FuelRecord' => 'App\Policies\Transport\Vehicle\FuelRecordPolicy',
        'App\Models\Transport\Vehicle\ServiceRecord' => 'App\Policies\Transport\Vehicle\ServiceRecordPolicy',
        'App\Models\Contact' => 'App\Policies\ContactPolicy',
        'App\Models\Guardian' => 'App\Policies\GuardianPolicy',
        'App\Models\Student\Registration' => 'App\Policies\Student\RegistrationPolicy',
        'App\Models\Student\Student' => 'App\Policies\Student\StudentPolicy',
        'App\Models\Employee\Department' => 'App\Policies\Employee\DepartmentPolicy',
        'App\Models\Employee\Designation' => 'App\Policies\Employee\DesignationPolicy',
        'App\Models\Employee\Employee' => 'App\Policies\Employee\EmployeePolicy',
        'App\Models\Employee\Leave\Allocation' => 'App\Policies\Employee\Leave\AllocationPolicy',
        'App\Models\Employee\Leave\Request' => 'App\Policies\Employee\Leave\RequestPolicy',
        'App\Models\Employee\Payroll\SalaryTemplate' => 'App\Policies\Employee\Payroll\SalaryTemplatePolicy',
        'App\Models\Employee\Payroll\SalaryStructure' => 'App\Policies\Employee\Payroll\SalaryStructurePolicy',
        'App\Models\Employee\Payroll\Payroll' => 'App\Policies\Employee\Payroll\PayrollPolicy',
        'App\Models\Employee\Attendance\Attendance' => 'App\Policies\Employee\Attendance\AttendancePolicy',
        'App\Models\Employee\Attendance\WorkShift' => 'App\Policies\Employee\Attendance\WorkShiftPolicy',
        'App\Models\Employee\Attendance\Timesheet' => 'App\Policies\Employee\Attendance\TimesheetPolicy',
        'App\Models\Exam\Schedule' => 'App\Policies\Exam\SchedulePolicy',
        'App\Models\Exam\OnlineExam' => 'App\Policies\Exam\OnlineExamPolicy',
        'App\Models\Resource\Diary' => 'App\Policies\Resource\DiaryPolicy',
        'App\Models\Resource\OnlineClass' => 'App\Policies\Resource\OnlineClassPolicy',
        'App\Models\Resource\Assignment' => 'App\Policies\Resource\AssignmentPolicy',
        'App\Models\Resource\LessonPlan' => 'App\Policies\Resource\LessonPlanPolicy',
        'App\Models\Resource\LearningMaterial' => 'App\Policies\Resource\LearningMaterialPolicy',
        'App\Models\Resource\Download' => 'App\Policies\Resource\DownloadPolicy',
        'App\Models\Resource\Syllabus' => 'App\Policies\Resource\SyllabusPolicy',
        'App\Models\Calendar\Holiday' => 'App\Policies\Calendar\HolidayPolicy',
        'App\Models\Calendar\Event' => 'App\Policies\Calendar\EventPolicy',
        'App\Models\Reception\Enquiry' => 'App\Policies\Reception\EnquiryPolicy',
        'App\Models\Reception\VisitorLog' => 'App\Policies\Reception\VisitorLogPolicy',
        'App\Models\Reception\GatePass' => 'App\Policies\Reception\GatePassPolicy',
        'App\Models\Reception\Complaint' => 'App\Policies\Reception\ComplaintPolicy',
        'App\Models\Reception\Query' => 'App\Policies\Reception\QueryPolicy',
        'App\Models\Reception\CallLog' => 'App\Policies\Reception\CallLogPolicy',
        'App\Models\Reception\Correspondence' => 'App\Policies\Reception\CorrespondencePolicy',
        'App\Models\Library\Book' => 'App\Policies\Library\BookPolicy',
        'App\Models\Library\BookAddition' => 'App\Policies\Library\BookAdditionPolicy',
        'App\Models\Approval\Request' => 'App\Policies\Approval\RequestPolicy',
        'App\Models\Mess\MealLog' => 'App\Policies\Mess\MealLogPolicy',
        'App\Models\Inventory\Vendor' => 'App\Policies\Inventory\VendorPolicy',
        'App\Models\Inventory\StockCategory' => 'App\Policies\Inventory\StockCategoryPolicy',
        'App\Models\Inventory\StockItem' => 'App\Policies\Inventory\StockItemPolicy',
        'App\Models\Inventory\StockRequisition' => 'App\Policies\Inventory\StockRequisitionPolicy',
        'App\Models\Inventory\StockPurchase' => 'App\Policies\Inventory\StockPurchasePolicy',
        'App\Models\Inventory\StockReturn' => 'App\Policies\Inventory\StockReturnPolicy',
        'App\Models\Inventory\StockAdjustment' => 'App\Policies\Inventory\StockAdjustmentPolicy',
        'App\Models\Inventory\StockTransfer' => 'App\Policies\Inventory\StockTransferPolicy',
        'App\Models\Communication\Communication' => 'App\Policies\Communication\CommunicationPolicy',
        'App\Models\Communication\Announcement' => 'App\Policies\Communication\AnnouncementPolicy',
        'App\Models\Hostel\RoomAllocation' => 'App\Policies\Hostel\RoomAllocationPolicy',
        'App\Models\Recruitment\Vacancy' => 'App\Policies\Recruitment\VacancyPolicy',
        'App\Models\Recruitment\Application' => 'App\Policies\Recruitment\ApplicationPolicy',
        'App\Models\Form\Form' => 'App\Policies\Form\FormPolicy',
        'App\Models\Gallery' => 'App\Policies\GalleryPolicy',
        'App\Models\Activity\Trip' => 'App\Policies\Activity\TripPolicy',
    ];

    /**
     * Register any authentication / authorization services.
     *
     * @return void
     */
    public function boot()
    {
        Gate::before(function ($user, $ability) {
            return ($user->is_default || $user->hasRole('admin')) ? true : null;
        });

        // Gate::after(function ($user, $ability) {
        //     return $user->hasRole('admin');
        // });
    }
}
