<?php

namespace App\Services\Reception;

use App\Enums\Reception\ComplaintStatus;
use App\Models\Employee\Employee;
use App\Models\Incharge;
use App\Models\Reception\Complaint;
use App\Models\Reception\ComplaintLog;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class ComplaintActionService
{
    public function assign(Request $request, Complaint $complaint)
    {
        Incharge::firstOrCreate([
            'employee_id' => $request->employee_id,
            'model_type' => 'Complaint',
            'model_id' => $complaint->id,
        ]);
    }

    public function unassign(Complaint $complaint, string $employee)
    {
        $employee = Employee::query()
            ->byTeam()
            ->where('uuid', $employee)
            ->getOrFail(trans('employee.employee'));

        Incharge::where('employee_id', $employee?->id)
            ->where('model_type', 'Complaint')
            ->where('model_id', $complaint->id)
            ->delete();
    }

    public function addLog(Request $request, Complaint $complaint)
    {
        \DB::beginTransaction();

        $log = ComplaintLog::forceCreate([
            'complaint_id' => $complaint->id,
            'status' => $request->status,
            'action' => $request->action,
            'comment' => $request->comment,
            'remarks' => $request->remarks,
            'user_id' => auth()->id(),
        ]);

        if ($request->status == ComplaintStatus::RESOLVED->value) {
            $complaint->status = ComplaintStatus::RESOLVED;
            $complaint->resolved_at = now()->toDateTimeString();
        } else {
            $complaint->status = $request->status;
            $complaint->resolved_at = null;
        }

        $complaint->save();

        \DB::commit();
    }

    private function isEditable(Complaint $complaint, ComplaintLog $log): bool
    {
        $complaintLogs = ComplaintLog::query()
            ->whereComplaintId($complaint->id)
            ->where('id', '>', $log->id)
            ->get();

        if ($complaintLogs->count()) {
            throw ValidationException::withMessages(['message' => trans('reception.complaint.could_not_modify_if_not_last_log')]);
        }

        if (! auth()->user()->hasRole('admin') && $complaint->user_id != auth()->id()) {
            throw ValidationException::withMessages(['message' => trans('user.errors.permission_denied')]);
        }

        return true;
    }

    public function removeLog(Complaint $complaint, string $log)
    {
        $log = ComplaintLog::query()
            ->whereComplaintId($complaint->id)
            ->whereUuid($log)
            ->getOrFail(trans('reception.complaint.props.action'));

        $this->isEditable($complaint, $log);

        \DB::beginTransaction();

        $log->delete();

        $lastLog = ComplaintLog::query()
            ->whereComplaintId($complaint->id)
            ->orderBy('created_at', 'desc')
            ->first();

        if (! $lastLog) {
            $complaint->resolved_at = null;
            $complaint->status = ComplaintStatus::SUBMITTED;
        } elseif ($lastLog?->status == ComplaintStatus::RESOLVED) {
            $complaint->resolved_at = $lastLog->created_at;
            $complaint->status = ComplaintStatus::RESOLVED;
        } else {
            $complaint->resolved_at = null;
            $complaint->status = $lastLog->status;
        }

        $complaint->save();

        \DB::commit();
    }
}
