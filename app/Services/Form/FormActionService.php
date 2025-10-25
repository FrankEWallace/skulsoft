<?php

namespace App\Services\Form;

use App\Models\Form\Form;
use App\Models\Form\Submission;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class FormActionService
{
    public function updateStatus(Request $request, Form $form)
    {
        $request->validate([
            'status' => 'required|in:publish,draft',
        ]);

        if ($form->status === $request->status) {
            return;
        }

        if ($form->published_at->value && Submission::where('form_id', $form->id)->exists()) {
            throw ValidationException::withMessages(['message' => trans('form.could_not_modify_after_submission')]);
        }

        if ($request->status == 'publish') {

            if ($form->due_date->value < today()->toDateString()) {
                throw ValidationException::withMessages(['message' => trans('form.could_not_publish_after_due_date')]);
            }

            $form->published_at = now()->toDateTimeString();
        } else {
            $form->published_at = null;
        }

        $form->save();
    }
}
