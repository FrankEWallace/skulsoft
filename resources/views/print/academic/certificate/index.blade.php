<x-print.layout type="full-page">
    @unless ($certificate->template->getConfig('has_custom_header'))
        @includeFirst([
            config('config.print.custom_path') . 'academic.certificate.header',
            'print.academic.certificate.header',
        ])
    @endunless

    {!! $certificate->content !!}
</x-print.layout>
