<?php

namespace App\Services\Dashboard;

use App\Http\Resources\Form\FormSummaryResource;
use App\Models\Form\Form;
use Illuminate\Http\Request;

class FormListService
{
    public function fetch(Request $request)
    {
        $forms = Form::query()
            ->when(auth()->user()->hasAnyRole(['student', 'guardian']), function ($q) {
                $q->whereIn('audience->student_type', ['all', 'division_wise', 'course_wise', 'batch_wise']);
            }, function ($q) {
                $q->whereIn('audience->employee_type', ['all', 'designation_wise', 'department_wise']);
            })
            ->accessible()
            ->whereNotNull('published_at')
            ->where('due_date', '>=', today()->toDateString())
            ->whereDoesntHave('submissions', function ($query) {
                $query->where('user_id', auth()->id());
            })
            ->get();

        return FormSummaryResource::collection($forms);
    }
}
