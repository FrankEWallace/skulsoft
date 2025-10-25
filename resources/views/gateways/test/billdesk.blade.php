<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <title>Billdesk Test Payment</title>
    @include('gateways.assets.billdesk')
    <script src="https://code.jquery.com/jquery-3.6.4.min.js"></script>
</head>

<body>
    <script>
        $(document).ready(function() {
            var flowConfig = {
                merchantId: "{{ $merchantId }}",
                bdOrderId: "{{ $bdOrderId }}",
                authToken: "{{ $authToken }}",
                childWindow: {{ $childWindow ? 'true' : 'false' }},
                returnUrl: "{{ $returnUrl }}",
            };

            var responseHandler = function(txn) {
                console.log("callback received status:: ", txn.status);
                console.log("callback received response:: ", txn.response);
            };

            var config = {
                responseHandler: responseHandler,
                merchantLogo: 'https://scriptmint.com/images/logo-light.png',
                flowConfig: flowConfig,
                flowType: "payments",
            };

            window.loadBillDeskSdk(config);
        });
    </script>
</body>


</html>
