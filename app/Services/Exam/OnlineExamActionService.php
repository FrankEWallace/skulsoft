<?php

namespace App\Services\Exam;

use App\Models\Exam\OnlineExam;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class OnlineExamActionService
{
    public function updateStatus(Request $request, OnlineExam $onlineExam)
    {
        if (! $onlineExam->can_update_status) {
            throw ValidationException::withMessages([
                'message' => trans('user.errors.permission_denied'),
            ]);
        }

        if (! in_array($request->status, ['publish', 'unpublish', 'publish_result', 'unpublish_result'])) {
            throw ValidationException::withMessages([
                'message' => trans('general.errors.invalid_action'),
            ]);
        }

        if (! $onlineExam->is_completed && in_array($request->status, ['publish_result', 'unpublish_result'])) {
            throw ValidationException::withMessages([
                'message' => trans('exam.online_exam.could_not_publish_result_without_completing_exam'),
            ]);
        }

        if ($request->status == 'publish' && $onlineExam->published_at->value) {
            throw ValidationException::withMessages([
                'message' => trans('exam.online_exam.already_published'),
            ]);
        }

        if ($request->status == 'unpublish' && ! $onlineExam->published_at->value) {
            throw ValidationException::withMessages([
                'message' => trans('exam.online_exam.already_unpublished'),
            ]);
        }

        if ($request->status == 'publish' && $onlineExam->cancelled_at->value) {
            throw ValidationException::withMessages([
                'message' => trans('exam.online_exam.already_cancelled'),
            ]);
        }

        $startDateTime = Carbon::parse($onlineExam->date->value.' '.$onlineExam->start_time->value);

        if ($startDateTime->isPast() && in_array($request->status, ['publish', 'unpublish'])) {
            throw ValidationException::withMessages([
                'message' => trans('exam.online_exam.could_not_update_status_if_date_time_passed_start_date_time'),
            ]);
        }

        if ($request->status == 'publish' && $onlineExam->questions()->count() == 0) {
            throw ValidationException::withMessages([
                'message' => trans('exam.online_exam.could_not_publish_without_questions'),
            ]);
        }

        if ($onlineExam->submissions()->count() > 0 && in_array($request->status, ['publish', 'unpublish'])) {
            throw ValidationException::withMessages([
                'message' => trans('exam.online_exam.could_not_update_status_with_submissions'),
            ]);
        }

        if (in_array($request->status, ['publish_result', 'unpublish_result'])) {
            $onlineExam->result_published_at = $request->status == 'publish_result' ? now()->toDateTimeString() : null;
            $onlineExam->save();

            return;
        }

        $onlineExam->published_at = $request->status == 'publish' ? now()->toDateTimeString() : null;
        $onlineExam->save();
    }
}
