<!doctype html>
<html lang="@yield('html_lang', 'en-US')">
<head>
    <title>@yield('title', 'RohrFrisch')</title>
    @yield('head')
</head>
<body @yield('body_attributes')>
    @yield('content')
    @yield('scripts')
</body>
</html>
