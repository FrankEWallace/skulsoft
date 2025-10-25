<?php

namespace App\Http\Requests\Library;

use App\Enums\OptionType;
use App\Models\Library\Book;
use App\Models\Option;
use Illuminate\Foundation\Http\FormRequest;

class BookRequest extends FormRequest
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
        return [
            'title' => ['required', 'min:2', 'max:100'],
            'author' => ['nullable', 'uuid'],
            'publisher' => ['nullable', 'uuid'],
            'language' => ['nullable', 'uuid'],
            'topic' => ['nullable', 'uuid'],
            'category' => ['nullable', 'uuid'],
            'sub_title' => ['nullable', 'min:2', 'max:100'],
            'subject' => ['nullable', 'min:2', 'max:100'],
            'year_published' => ['nullable', 'min:2', 'max:10'],
            'volume' => ['nullable', 'min:1', 'max:20'],
            'isbn_number' => ['nullable', 'min:2', 'max:30'],
            'call_number' => ['nullable', 'min:1', 'max:30'],
            'edition' => ['nullable', 'min:1', 'max:30'],
            'type' => ['nullable', 'min:2', 'max:20'],
            'page' => ['nullable', 'integer'],
            'price' => ['nullable', 'integer'],
            'summary' => ['nullable', 'min:2', 'max:1000'],
        ];
    }

    public function withValidator($validator)
    {
        if (! $validator->passes()) {
            return;
        }

        $validator->after(function ($validator) {

            $bookUuid = $this->route('book.uuid');

            $existingRecord = Book::query()
                ->byTeam()
                ->when($bookUuid, function ($q, $bookUuid) {
                    $q->where('uuid', '!=', $bookUuid);
                })
                ->where(function ($q) {
                    $q->where('title', $this->title)->orWhere('isbn_number', $this->isbn_number);
                })->exists();

            if ($existingRecord) {
                $validator->errors()->add('title', __('validation.unique', ['attribute' => __('library.book.props.title')]));
                $validator->errors()->add('isbn_number', __('validation.unique', ['attribute' => __('library.book.props.isbn_number')]));
            }

            $this->whenFilled('author', function (string $input) {
                $author = Option::query()
                    ->whereType(OptionType::BOOK_AUTHOR->value)
                    ->whereUuid($input)
                    ->getOrFail(__('library.book.props.author'), 'author');

                $this->merge(['author_id' => $author->id]);
            });

            $this->whenFilled('publisher', function (string $input) {
                $publisher = Option::query()
                    ->whereType(OptionType::BOOK_PUBLISHER->value)
                    ->whereUuid($input)
                    ->getOrFail(__('library.book.props.publisher'), 'publisher');

                $this->merge(['publisher_id' => $publisher->id]);
            });

            $this->whenFilled('language', function (string $input) {
                $language = Option::query()
                    ->whereType(OptionType::BOOK_LANGUAGE->value)
                    ->whereUuid($input)
                    ->getOrFail(__('library.book.props.language'), 'language');

                $this->merge(['language_id' => $language->id]);
            });

            $this->whenFilled('topic', function (string $input) {
                $topic = Option::query()
                    ->whereType(OptionType::BOOK_TOPIC->value)
                    ->whereUuid($input)
                    ->getOrFail(__('library.book.props.topic'), 'topic');

                $this->merge(['topic_id' => $topic->id]);
            });

            $this->whenFilled('category', function (string $input) {
                $category = Option::query()
                    ->whereType(OptionType::BOOK_CATEGORY->value)
                    ->whereUuid($input)
                    ->getOrFail(__('library.book.props.category'), 'category');

                $this->merge(['category_id' => $category->id]);
            });

            $this->merge([
                'type' => $this->type ?? 'reference',
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
            'title' => __('library.book.props.title'),
            'author' => __('library.book.props.author'),
            'publisher' => __('library.book.props.publisher'),
            'language' => __('library.book.props.language'),
            'topic' => __('library.book.props.topic'),
            'category' => __('library.book.props.category'),
            'sub_title' => __('library.book.props.sub_title'),
            'subject' => __('library.book.props.subject'),
            'year_published' => __('library.book.props.year_published'),
            'volume' => __('library.book.props.volume'),
            'isbn_number' => __('library.book.props.isbn_number'),
            'call_number' => __('library.book.props.call_number'),
            'edition' => __('library.book.props.edition'),
            'type' => __('library.book.props.type'),
            'page' => __('library.book.props.page'),
            'price' => __('library.book.props.price'),
            'summary' => __('library.book.props.description'),
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
