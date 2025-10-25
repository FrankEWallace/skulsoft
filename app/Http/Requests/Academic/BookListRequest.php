<?php

namespace App\Http\Requests\Academic;

use App\Enums\Academic\BookListType;
use App\Models\Academic\BookList;
use App\Models\Academic\Course;
use App\Models\Academic\Subject;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Enum;

class BookListRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        $rules = [
            'course' => 'required|uuid',
            'subject' => 'nullable|uuid',
            'type' => ['required', new Enum(BookListType::class)],
            'title' => 'required|string|min:3|max:100',
            'author' => 'nullable|string|min:3|max:100',
            'publisher' => 'nullable|string|min:3|max:100',
            'quantity' => 'required_if:type,notebook|integer|min:1|max:100',
            'pages' => 'nullable|integer|min:1|max:1000',
            'description' => 'nullable|string|max:1000',
        ];

        return $rules;
    }

    public function withValidator($validator)
    {
        if (! $validator->passes()) {
            return;
        }

        $validator->after(function ($validator) {
            $uuid = $this->route('book_list');

            $course = Course::query()
                ->byPeriod()
                ->where('uuid', $this->course)
                ->getOrFail(trans('academic.course.course'), 'course');

            $subject = $this->subject ? Subject::query()
                ->byPeriod()
                ->where('uuid', $this->subject)
                ->getOrFail(trans('academic.batch.batch'), 'subject') : null;

            $existingRecords = BookList::query()
                ->byPeriod()
                ->when($uuid, function ($q, $uuid) {
                    $q->where('uuid', '!=', $uuid);
                })
                ->where('course_id', $course->id)
                ->where('subject_id', $subject?->id)
                ->whereTitle($this->title)
                ->exists();

            if ($existingRecords) {
                $validator->errors()->add('name', trans('validation.unique', ['attribute' => trans('academic.book_list.book_list')]));
            }

            $this->merge([
                'course_id' => $course->id,
                'subject_id' => $subject?->id,
            ]);
        });
    }

    /**
     * Translate fields with user friendly name.
     *
     * @return array
     */
    public function attributes()
    {
        return [
            'course' => __('academic.course.course'),
            'subject' => __('academic.subject.subject'),
            'type' => __('academic.book_list.props.type'),
            'title' => __('academic.book_list.props.title'),
            'author' => __('academic.book_list.props.author'),
            'publisher' => __('academic.book_list.props.publisher'),
            'quantity' => __('academic.book_list.props.quantity'),
            'pages' => __('academic.book_list.props.pages'),
            'description' => __('academic.book_list.props.description'),
        ];
    }

    /**
     * Get the error messages for the defined validation rules.
     *
     * @return array
     */
    public function messages()
    {
        return [];
    }
}
