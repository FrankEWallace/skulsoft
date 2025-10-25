<x-print.layout>
    @includeFirst([config('config.print.custom_path') . 'academic.header', 'print.academic.header'])

    <h1 class="heading">{{ $course->name }} {{ $course->division->period->name }}</h1>

    @if ($textbooks->count())
        <h1 class="sub-heading">{{ trans('academic.book_list.types.textbook') }}</h1>
        <table class="table">
            <thead>
                <tr>
                    <th>{{ trans('general.sno') }}</th>
                    <th>{{ trans('academic.subject.subject') }}</th>
                    <th>{{ trans('academic.book_list.props.title') }}</th>
                    <th>{{ trans('academic.book_list.props.publisher') }}</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($textbooks as $textbook)
                    <tr>
                        <td>{{ $loop->index + 1 }}</td>
                        <td>{{ $textbook->subject?->name }}</td>
                        <td>{{ $textbook->title }}</td>
                        <td>{{ $textbook->publisher }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    @endif

    @if ($notebooks->count())
        <h1 class="sub-heading">{{ trans('academic.book_list.types.notebook') }}</h1>
        <table class="table">
            <thead>
                <tr>
                    <th>{{ trans('general.sno') }}</th>
                    <th>{{ trans('academic.subject.subject') }}</th>
                    <th>{{ trans('academic.book_list.props.title') }}</th>
                    <th>{{ trans('academic.book_list.props.quantity') }}</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($notebooks as $notebook)
                    <tr>
                        <td>{{ $loop->index + 1 }}</td>
                        <td>{{ $notebook->subject?->name }}</td>
                        <td>{{ $notebook->title }}
                            @if ($notebook->pages)
                                ({{ $notebook->pages }})
                                {{ trans('academic.book_list.props.pages') }}
                            @endif
                        </td>
                        <td>{{ $notebook->quantity }} {{ trans('list.unit.pcs') }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    @endif
</x-print.layout>
