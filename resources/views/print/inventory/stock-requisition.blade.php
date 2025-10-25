<x-print.layout type="centered">
    @includeFirst([config('config.print.custom_path') . 'header', 'print.header'])

    <h2 class="heading">{{ trans('inventory.stock_requisition.slip') }}</h2>

    <table class="table" border="1">
        <tr>
            <th>{{ trans('general.sno') }}</th>
            <td>{{ Arr::get($stockRequisition, 'code_number') }}</td>
            <th>{{ trans('inventory.stock_requisition.props.date') }}</th>
            <td>{{ Arr::get($stockRequisition, 'date.formatted') }}</td>
        </tr>
        <tr>
            <th>{{ trans('inventory.vendor.vendor') }}</th>
            <td colspan="3">{{ Arr::get($stockRequisition, 'vendor.name') }}</td>
        </tr>
        @if (Arr::get($stockRequisition, 'employee.name'))
            <tr>
                <th>{{ trans('inventory.stock_requisition.props.requested_by') }}</th>
                <td colspan="3">{{ Arr::get($stockRequisition, 'employee.name') }}
                    ({{ Arr::get($stockRequisition, 'employee.designation') }})</td>
            </tr>
        @endif
    </table>

    <table class="mt-2 table" width="100%" border="1" cellspacing="4" cellpadding="0">
        <thead>
            <tr>
                <th width="10%">{{ trans('general.sno') }}</th>
                <th>{{ trans('inventory.item') }}</th>
                <th width="20%" class="text-right">{{ trans('inventory.stock_requisition.props.quantity') }}</th>
            </tr>
        </thead>
        @foreach (Arr::get($stockRequisition, 'items', []) as $item)
            <tbody>
                <tr>
                    <td>{{ $loop->index + 1 }}</td>
                    <td>
                        {{ Arr::get($item, 'item.name') }}
                        @if (Arr::get($item, 'description'))
                            <div class="font-90pc">{{ Arr::get($item, 'description') }}</div>
                        @endif
                    </td>
                    <td class="text-right">
                        {{ Arr::get($item, 'quantity') }} {{ Arr::get($item, 'item.unit') }}
                    </td>
                </tr>
            </tbody>
        @endforeach
    </table>
    @if (Arr::get($stockRequisition, 'message_to_vendor'))
        <div class="mt-8">
            <p class="font-weight-bold">{{ Arr::get($stockRequisition, 'message_to_vendor') }}</p>
        </div>
    @endif
    <div class="mt-8">
        <p class="text-right">{{ trans('print.authorized_signatory') }}</p>
    </div>
    <div class="mt-4">
        <p>{{ trans('general.printed_at') }}: {{ \Cal::dateTime(now())->formatted }}
        </p>
    </div>
</x-print.layout>
