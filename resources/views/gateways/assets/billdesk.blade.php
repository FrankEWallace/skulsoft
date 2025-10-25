@if (config('config.finance.enable_billdesk'))
    @if (config('config.finance.enable_live_billdesk_mode'))
        <script type="module" src="https://pay.billdesk.com/jssdk/v1/dist/billdesksdk/billdesksdk.esm.js"></script>
        <script nomodule src="https://pay.billdesk.com/jssdk/v1/dist/billdesksdk.js"></script>
        <link href="https://pay.billdesk.com/jssdk/v1/dist/billdesksdk/billdesksdk.css" rel="stylesheet">
    @else
        @if (config('config.finance.billdesk_version') == 1.2 || config('config.finance.billdesk_version') == '1.2')
            <script type="module" src="https://uat.billdesk.com/jssdk/v1/dist/billdesksdk/billdesksdk.esm.js"></script>
            <script nomodule="" src="https://uat.billdesk.com/jssdk/v1/dist/billdesksdk.js"></script>
            <link href="https://uat.billdesk.com/jssdk/v1/dist/billdesksdk/billdesksdk.css" rel="stylesheet">
        @elseif(config('config.finance.billdesk_version') == 1.5 || config('config.finance.billdesk_version') == '1.5')
            <script type="module" src="https://uat1.billdesk.com/merchant-uat/sdk/dist/billdesksdk/billdesksdk.esm.js"></script>
            <script nomodule="" src="https://uat1.billdesk.com/merchant-uat/sdk/dist/billdesksdk.js"></script>
            <link href="https://uat1.billdesk.com/merchant-uat/sdk/dist/billdesksdk/billdesksdk.css" rel="stylesheet">
        @endif
    @endif
@endif
