<?php

namespace App\Support;

use App\Models\Approval\Request as ApprovalRequest;
use App\Models\Contact;
use App\Models\Dialogue;
use App\Models\Employee\Employee;
use App\Models\Post\Post;
use App\Models\Student\Student;
use Illuminate\Http\Request;

trait ValidateComment
{
    public function validateInput(Request $request)
    {
        $commentableType = null;
        $commentableId = null;

        if ($request->type == 'student' && $request->module_type == 'dialogue') {
            $student = Student::findSummaryByUuidOrFail($request->uuid);

            $dialogue = Dialogue::query()
                ->whereHasMorph(
                    'model',
                    [Contact::class],
                    function ($q) use ($student) {
                        $q->whereId($student->contact_id);
                    }
                )
                ->whereUuid($request->module_uuid)
                ->getOrFail(trans('student.dialogue.dialogue'));

            $commentableType = 'Dialogue';
            $commentableId = $dialogue->id;
        } elseif ($request->type == 'employee' && $request->module_type == 'dialogue') {
            $employee = Employee::findSummaryByUuidOrFail($request->uuid);

            $dialogue = Dialogue::query()
                ->whereHasMorph(
                    'model',
                    [Contact::class],
                    function ($q) use ($employee) {
                        $q->whereId($employee->contact_id);
                    }
                )
                ->whereUuid($request->module_uuid)
                ->getOrFail(trans('employee.dialogue.dialogue'));

            $commentableType = 'Dialogue';
            $commentableId = $dialogue->id;
        } elseif ($request->type == 'post') {
            $post = Post::findByUuidOrFail($request->uuid);

            $commentableType = 'Post';
            $commentableId = $post->id;
        } elseif ($request->type == 'approval_request') {
            $approvalRequest = ApprovalRequest::query()
                ->byTeam()
                ->whereUuid($request->uuid)
                ->getOrFail(trans('approval.request.request'));

            $commentableType = 'ApprovalRequest';
            $commentableId = $approvalRequest->id;
        }

        return [
            'commentable_type' => $commentableType,
            'commentable_id' => $commentableId,
        ];
    }
}
