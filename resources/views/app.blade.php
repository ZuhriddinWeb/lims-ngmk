<!doctype html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="description" content="Единая система управления лабораторной информацией АО «Навоийский горно-металлургический комбинат»">
    <link rel="icon" type="image/png" href="/images/ngmk.png">

    {{-- Устанавливаем тему до первой отрисовки, чтобы не было «моргания». --}}
    <script>
        (function () {
            try {
                var t = localStorage.getItem('lims_theme') || 'dark';
                document.documentElement.setAttribute('data-theme', t);
            } catch (e) {
                document.documentElement.setAttribute('data-theme', 'dark');
            }
        })();
    </script>

    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @inertiaHead
</head>
<body>
    @inertia
</body>
</html>
