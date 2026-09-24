@props(['title', 'greetingName'])

<!DOCTYPE html>
<html lang="en">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="robots" content="noindex,nofollow">
        <title>{{ $title }}</title>
        @vite('resources/css/dashboard-prototype.css')
    </head>
    <body class="prototype-body">
        <div class="operator-shell">
            <x-operator-sidebar />

            <div class="operator-workspace">
                <x-operator-topbar />

                <main class="operator-main">
                    {{ $slot }}
                </main>
            </div>
        </div>
    </body>
</html>
