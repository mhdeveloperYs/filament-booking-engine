<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $title ?? 'Book an appointment' }}</title>
    {{-- Tailwind via CDN so the public page works with zero setup. Publish this
         view and swap it for your own stylesheet in production. --}}
    <script src="https://cdn.tailwindcss.com"></script>
    @livewireStyles
</head>
<body class="min-h-screen bg-gray-50 text-gray-900 antialiased">
    {{ $slot }}

    @livewireScripts
</body>
</html>
