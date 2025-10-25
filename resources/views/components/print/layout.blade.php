@props([
    'type' => null,
    'spacing' => false,
])

<!DOCTYPE html>
<html>

<head>
    <title>{{ config('app.name') ?: env('APP_NAME') }}</title>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1.0, user-scalable=no">
    <script src="https://unpkg.com/vue@3/dist/vue.global.js"></script>
    <style>
        body {
            font-family: 'Helvetica';
            font-size: 14px;
            line-height: 16px;
        }

        body {
            @if ($type == 'centered')
                width: 800px;
            @endif
            @if ($type == 'full-page')
                width: auto;
                max-width: 95%;
                margin: 0 auto;
            @endif
            @empty($type)
                width: auto;
                max-width: 1200px;
                min-width: 600px;
            @endempty
            @if ($spacing)
                margin: 10px auto;
                padding: 20px;
            @else
                margin: 0 auto;
            @endif
        }

        h2 {
            font-weight: bold;
        }

        .heading {
            font-weight: 800;
            font-size: 120%;
            text-transform: uppercase;
            text-align: center;
        }

        .heading-left {
            font-weight: 800;
            font-size: 120%;
            text-transform: uppercase;
            text-align: left;
        }

        .sub-heading {
            font-weight: 600;
            font-size: 110%;
            text-align: center;
        }

        .sub-heading-left {
            font-weight: 600;
            font-size: 110%;
            text-align: left;
        }

        .sub-heading-right {
            font-weight: 600;
            font-size: 110%;
            text-align: right;
        }

        .sub-title {
            font-size: 90%;
        }

        .footer {
            margin-top: 10px;
            font-size: 90%;
        }

        .footer .timestamp {
            font-style: italic;
            text-align: right !important;
        }

        .text-center {
            text-align: center !important;
        }

        .text-right {
            text-align: right !important;
        }

        .underline {
            text-decoration: underline;
        }

        .font-weight-bold {
            font-weight: bold;
        }

        .font-120pc {
            font-size: 120%;
        }

        .font-110pc {
            font-size: 110%;
        }

        .font-90pc {
            font-size: 90% !important;
        }

        .font-85pc {
            font-size: 85% !important;
        }

        .font-80pc {
            font-size: 80% !important;
        }

        .text-xl {
            font-size: 140% !important;
        }

        .ml-1 {
            margin-left: 4px;
        }

        .m-2 {
            margin-left: 8px;
        }

        .ml-4 {
            margin-left: 16px;
        }

        .mt-1 {
            margin-top: 4px;
        }

        .mt-2 {
            margin-top: 8px;
        }

        .mt-4 {
            margin-top: 16px;
        }

        .mt-8 {
            margin-top: 32px;
        }

        .mt-12 {
            margin-top: 48px;
        }

        .mt-16 {
            margin-top: 64px;
        }

        .mt-24 {
            margin-top: 96px;
        }

        .mt-32 {
            margin-top: 128px;
        }

        .mx-4 {
            margin-left: 16px;
            margin-right: 16px;
        }

        .px-4 {
            padding-left: 16px;
            padding-right: 16px;
        }

        .py-1 {
            padding-top: 2px !important;
            padding-bottom: 2px !important;
        }

        .py-2 {
            padding-top: 4px !important;
            padding-bottom: 4px !important;
        }

        .border-dark {
            border-color: #888a8e !important;
        }

        table.table {
            width: 100%;
            border-collapse: collapse;
            border: .0625rem solid #888a8e;
        }

        table.inner-table {
            width: 100%;
            border-collapse: collapse;
        }

        table.inner-table tr td {
            border: 1px solid black;
        }

        table.inner-table tr td:first-child {
            border-left: 0px;
        }

        table.inner-table tr td:last-child {
            border-right: 0px;
        }

        table.outer-table {
            width: 100%;
            border-collapse: collapse;
        }

        table.outer-table tr td {
            border: 1px solid black;
        }

        table.outer-table tr td:first-child {}

        table.outer-table tr td:last-child {}

        table.table thead,
        table.table tfoot,
        table.inner-table thead,
        table.inner-table tfoot {
            display: table-header-group;
            vertical-align: middle;
            border-color: inherit;
            background-color: #e9ecf1;
        }

        table.table tr,
        table.inner-table tr {
            display: table-row;
            vertical-align: inherit;
            border-color: inherit;
        }

        table.table tr:nth-child(even) {
            background-color: rgba(0, 0, 0, .05);
        }

        table.table th,
        table.table tfoot td {
            font-weight: bold;
            text-align: left;
            padding: 10px;
        }


        table.inner-table th,
        table.inner-table tfoot td {
            font-weight: bold;
            text-align: left;
            padding: 5px;
        }

        table.table tbody,
        table.inner-table tbody {
            display: table-row-group;
            vertical-align: middle;
            border-color: inherit;
        }

        table.table td {
            padding: 5px 10px;
        }

        table.inner-table td {
            padding: 2px 4px;
        }

        table.cellpadding td {
            padding: 5px 10px;
        }

        .watermark-container {
            position: relative;
        }

        .watermark-image {
            position: absolute;
            top: 50%;
            left: 50%;
            width: 200px;
            opacity: 0.1;
            transform: translate(-50%, -50%);
        }

        .circular-border {
            border-radius: 50%;
            border: 1px solid #000;
            padding: 2px;
        }

        .list-container> :not(:last-child) {
            margin-bottom: 0.5rem;
        }

        .sidebar {
            position: fixed;
            z-index: 10;
            left: -200px;
            top: 0;
            bottom: 0;
            width: 200px;
            background-color: #1f2937;
            opacity: 1;
            color: #fff;
            transition: left 0.3s ease;
        }

        .sidebar.open {
            left: 0;
        }

        .menu-toggle {
            position: fixed;
            left: 10px;
            top: 10px;
            cursor: pointer;
        }

        @media print {

            .no-print,
            .no-print * {
                display: none !important;
            }
        }
    </style>
</head>

<body>
    <div id="app">
        {{ $slot }}
    </div>
</body>

</html>
