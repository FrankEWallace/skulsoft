<x-print.layout type="centered">

    <div class="watermark-container">
        <img class="watermark-image" src="{{ url(config('config.assets.logo')) }}">

        @includeFirst(['print.custom.header', 'print.header'])

        <h2 class="heading text-center">
            {{ trans('approval.request.request') }}
            @if (Arr::get($approvalRequest, 'status.value') == 'approved')
                <span style="color: green;">({{ trans('approval.statuses.approved') }})</span>
            @endif
        </h2>

        <table class="mt-2" width="100%" border="0" cellspacing="4" cellpadding="0">
            <tr>
                <td width="50%" valign="top">
                    <div class="sub-heading-left">{{ trans('approval.request.props.code_number') }}:
                        {{ Arr::get($approvalRequest, 'code_number') }} /
                        {{ trans('approval.request.props.id_number') }}: {{ $idNumber }}</div>
                </td>
                <td width="50%" valign="top">
                    <div class="sub-heading text-right">{{ trans('approval.request.props.date') }}:
                        {{ Arr::get($approvalRequest, 'date.formatted') }}</div>
                </td>
            </tr>
        </table>

        <div class="sub-heading">{{ trans('approval.request.props.title') }}: {{ Arr::get($approvalRequest, 'title') }}
        </div>
        {{-- <div class="sub-heading">{{ Arr::get($approvalRequest, 'requester.name') }}
            ({{ Arr::get($approvalRequest, 'requester.designation') }})</div> --}}

        <table class="mt-2 table" width="100%" border="0" cellspacing="4" cellpadding="0">
            <tr>
                <th>{{ trans('employee.department.department') }}</th>
                <td class="text-right">{{ Arr::get($approvalRequest, 'type.department.name') }}</td>
                <th>{{ trans('approval.type.type') }}</th>
                <td class="text-right">{{ Arr::get($approvalRequest, 'type.name') }}</td>
            </tr>
            <tr>
                <th>{{ trans('approval.type.props.category') }}</th>
                <td class="text-right">{{ Arr::get($approvalRequest, 'type.category.label') }}</td>
                <th>{{ trans('approval.request.props.priority') }}</th>
                <td class="text-right">{{ Arr::get($approvalRequest, 'priority.name') }}</td>
            </tr>
            <tr>
                <th>{{ trans('approval.request.group.group') }}</th>
                <td class="text-right">{{ Arr::get($approvalRequest, 'group.name') }}</td>
                <th>{{ trans('approval.request.nature.nature') }}</th>
                <td class="text-right">{{ Arr::get($approvalRequest, 'nature.name') }}</td>
            </tr>
            <tr>
                <th>{{ trans('approval.request.props.amount') }}</th>
                <td class="text-right">{{ Arr::get($approvalRequest, 'amount.formatted') }}</td>
                <th>{{ trans('approval.request.props.status') }}</th>
                <td class="text-right">{{ Arr::get($approvalRequest, 'status.label') }}</td>
            </tr>
            <tr>
                <th>{{ trans('approval.request.props.due_date') }}</th>
                <td class="text-right">{{ Arr::get($approvalRequest, 'due_date.formatted') }}</td>
                <th>{{ trans('approval.request.props.requester') }}</th>
                <td class="text-right">
                    {{ Arr::get($approvalRequest, 'requester.name') }}
                    ({{ Arr::get($approvalRequest, 'requester.designation') }})
                </td>
            </tr>
            <tr>
                <th>{{ trans('general.created_at') }}</th>
                <td class="text-right">{{ Arr::get($approvalRequest, 'created_at.formatted') }}</td>
                <th>{{ trans('general.updated_at') }}</th>
                <td class="text-right">{{ Arr::get($approvalRequest, 'updated_at.formatted') }}</td>
            </tr>
        </table>

        @if (Arr::get($approvalRequest, 'type.category.value') == 'item_based')
            <h2 class="mt-4 sub-heading">
                {{ trans('approval.type_approval', ['type' => trans('approval.categories.item_based')]) }}</h2>
            <table class="mt-2 table" width="100%" border="0" cellspacing="4" cellpadding="0">
                <tr>
                    <th>{{ trans('approval.request.item') }}</th>
                    @if (Arr::get($approvalRequest, 'type.item_based_type') == 'item_with_quantity')
                        <th>{{ trans('inventory.stock_item.props.quantity') }}</th>
                        <th>{{ trans('inventory.stock_item.props.unit') }}</th>
                        <th>{{ trans('inventory.stock_item.props.price') }}</th>
                    @else
                        <th>{{ trans('finance.transaction.props.amount') }}</th>
                    @endif
                </tr>
                @foreach (Arr::get($approvalRequest, 'items') as $item)
                    <tr>
                        <td>{{ Arr::get($item, 'item') }}</td>
                        @if (Arr::get($approvalRequest, 'type.item_based_type') == 'item_with_quantity')
                            <td>{{ Arr::get($item, 'quantity') }}</td>
                            <td>{{ Arr::get($item, 'unit') }}</td>
                            <td>{{ Arr::get($item, 'price.formatted') }}</td>
                        @else
                            <td>{{ Arr::get($item, 'amount.formatted') }}</td>
                        @endif
                    </tr>
                    @if (Arr::get($item, 'description'))
                        <tr>
                            <td colspan="4" class="font-90pc">{{ Arr::get($item, 'description') }}</td>
                        </tr>
                    @endif
                @endforeach
            </table>
        @endif

        @if (Arr::get($approvalRequest, 'type.category.value') == 'payment_based')
            <h2 class="mt-4 sub-heading">
                {{ trans('approval.type_approval', ['type' => trans('approval.categories.payment_based')]) }}</h2>
            <table class="mt-2 table" width="100%" border="0" cellspacing="4" cellpadding="0">
                <tr>
                    <th>{{ trans('finance.transaction.props.payee') }}</th>
                    <td class="text-right">{{ Arr::get($approvalRequest, 'payment.vendor') }}</td>
                    <th>{{ trans('approval.request.props.amount') }}</th>
                    <td class="text-right">{{ Arr::get($approvalRequest, 'payment.amount.formatted') }}</td>
                </tr>
                <tr>
                    <th>{{ trans('finance.transaction.props.invoice_number') }}</th>
                    <td class="text-right">{{ Arr::get($approvalRequest, 'payment.invoice_number') }}</td>
                    <th>{{ trans('finance.transaction.props.invoice_date') }}</th>
                    <td class="text-right">{{ Arr::get($approvalRequest, 'payment.invoice_date.formatted') }}</td>
                </tr>
                <tr>
                    <th>{{ trans('finance.transaction.props.mode') }}</th>
                    <td class="text-right">{{ Arr::get($approvalRequest, 'payment.mode') }}</td>
                    <th>{{ trans('finance.transaction.props.details') }}</th>
                    <td class="text-right">{{ Arr::get($approvalRequest, 'payment.details') }}</td>
                </tr>
            </table>
        @endif

        @if (Arr::get($approvalRequest, 'type.category.value') == 'contact_based')
            <h2 class="mt-4 sub-heading">
                {{ trans('approval.type_approval', ['type' => trans('approval.categories.contact_based')]) }}</h2>
            <table class="mt-2 table" width="100%" border="0" cellspacing="4" cellpadding="0">
                <tr>
                    <th>{{ trans('contact.props.name') }}</th>
                    <td class="text-right">{{ Arr::get($approvalRequest, 'contact.name') }}</td>
                    <th>{{ trans('contact.props.contact_number') }}</th>
                    <td class="text-right">{{ Arr::get($approvalRequest, 'contact.contact_number') }}</td>
                </tr>
                <tr>
                    <th>{{ trans('contact.props.email') }}</th>
                    <td class="text-right">{{ Arr::get($approvalRequest, 'contact.email') }}</td>
                    <th>{{ trans('contact.props.website') }}</th>
                    <td class="text-right">{{ Arr::get($approvalRequest, 'contact.website') }}</td>
                </tr>
                <tr>
                    <th>{{ trans('contact.props.tax_number') }}</th>
                    <td class="text-right">{{ Arr::get($approvalRequest, 'contact.tax_number') }}</td>
                    <th>{{ trans('contact.props.address.address') }}</th>
                    <td class="text-right">{{ Arr::get($approvalRequest, 'contact.address_display') }}</td>
                </tr>
            </table>
        @endif

        @if (Arr::get($approvalRequest, 'purpose'))
            <h2 class="mt-4 sub-heading">
                {{ trans('approval.type_approval', ['type' => trans('approval.categories.other')]) }}</h2>
            <p class="mt-4 font-90pc">{{ Arr::get($approvalRequest, 'purpose') }}</p>
        @endif

        <p class="mt-4 font-90pc">{{ Arr::get($approvalRequest, 'description') }}</p>

        <h2 class="mt-4 sub-heading">{{ trans('approval.approvers') }}</h2>
        <table class="mt-2 table" width="100%" border="0" cellspacing="4" cellpadding="0">
            <tr>
                <th>{{ trans('approval.approver') }}</th>
                <th>{{ trans('approval.request.props.status') }}</th>
                <th>{{ trans('approval.request.props.received_at') }}</th>
                <th>{{ trans('approval.request.props.processed_at') }}</th>
                <th>{{ trans('approval.request.props.duration') }}</th>
            </tr>
            @foreach (Arr::get($approvalRequest, 'records') as $record)
                <tr>
                    <td>{{ Arr::get($record, 'employee.name') }}
                        ({{ Arr::get($record, 'employee.designation') }})
                    </td>
                    <td>{{ Arr::get($record, 'status.label') }}</td>
                    <td>{{ Arr::get($record, 'received_at.formatted') }}</td>
                    <td>{{ Arr::get($record, 'processed_at.formatted') }}
                    </td>
                    <td>{{ Arr::get($record, 'duration') }}</td>
                </tr>
                <tr>
                    <td colspan="4" class="font-90pc">{{ Arr::get($record, 'comment') }}</td>
                </tr>
            @endforeach
        </table>

        <div class="mt-4 text-right">
            <h2>{{ trans('approval.authorized_signatory') }}</h2>
        </div>

        <div class="mt-4">
            <p>{{ trans('general.printed_at') }}: {{ \Cal::dateTime(now())->formatted }}</p>
        </div>

    </div>
</x-print.layout>
