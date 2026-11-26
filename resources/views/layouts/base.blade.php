<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'BARGO Inventory') — SLSU Hinunangan</title>
    <link rel="icon" type="image/png" href="{{ asset('images/slsu-logo.png') }}">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @stack('scripts')
</head>
<body class="min-h-screen">
    @yield('body')
</body>
</html>
