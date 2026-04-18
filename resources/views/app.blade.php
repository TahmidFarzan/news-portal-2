<!DOCTYPE html>
<html>
    <head>
        <meta charset="utf-8" />
        <meta name="viewport" content="width=device-width, initial-scale=1" />
        <meta name="csrf-token" content="{{ csrf_token() }}">
        <meta name="copyright" content="Copyright &copy; {{ now()->format('Y') }}. All Rights &reg; Reserved by {{ config("app.url") }}">

        @routes

        @vite(['resources/css/app.css', 'resources/js/app.js'])

        @inertiaHead
    </head>

    <body>
        @inertia
    </body>
</html>
