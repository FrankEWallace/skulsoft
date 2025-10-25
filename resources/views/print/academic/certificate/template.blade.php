<x-print.layout type="centered">
    @includeFirst([
        config('config.print.custom_path') . 'academic.certificate.header',
        'print.academic.certificate.header',
    ])

    {!! $certificateTemplate->content !!}
</x-print.layout>
