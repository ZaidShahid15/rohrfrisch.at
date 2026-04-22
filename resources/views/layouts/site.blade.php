<!doctype html>
<html lang="@yield('html_lang', 'en-US')">
<head>
    <title>@yield('title', 'RohrFrisch')</title>
    @yield('head')
    <style>
        @media (max-width: 767px) {
            body {
                padding-bottom: 104px;
            }

            .elementor-element-1260dbe.elementor-hidden-desktop {
                display: none !important;
            }

            .elementor-1990 .elementor-element.elementor-element-c77c2af {
                display: flex !important;
                flex-direction: column !important;
                align-items: stretch !important;
            }

            .elementor-1990 .elementor-element.elementor-element-1f32ff8,
            .elementor-1990 .elementor-element.elementor-element-ea25feb,
            .elementor-1990 .elementor-element.elementor-element-e84c596 {
                width: 100% !important;
                max-width: 100% !important;
                display: flex !important;
                flex-direction: column !important;
                align-items: stretch !important;
            }

            .elementor-1990 .elementor-element.elementor-element-a5dfaef > .elementor-widget-container {
                margin: 0 0 12px 0 !important;
            }

            .elementor-element.elementor-element-78137cc .elementor-form-fields-wrapper {
                display: flex !important;
                flex-direction: column !important;
                align-items: stretch !important;
                gap: 12px !important;
                margin-top: 14px !important;
            }

            .elementor-element.elementor-element-78137cc .elementor-field-group {
                width: 100% !important;
                max-width: 100% !important;
                flex: 0 0 100% !important;
                margin: 0 !important;
            }

            .elementor-element.elementor-element-78137cc .elementor-button {
                width: 100% !important;
            }

            .elementor-element.elementor-element-e84c596 {
                position: static !important;
                width: 100% !important;
                margin-top: 18px !important;
                min-height: auto !important;
            }

            .elementor-element.elementor-element-e84c596 .elementor-widget-container,
            .elementor-element.elementor-element-e84c596 p,
            .elementor-element.elementor-element-e84c596 p span {
                display: block !important;
                width: 100% !important;
                margin: 0 !important;
                white-space: normal !important;
                overflow-wrap: break-word !important;
                line-height: 1.6 !important;
                text-align: left !important;
            }

            .scroll-top {
                display: none !important;
            }
        }
    </style>
</head>
<body @yield('body_attributes')>
    @yield('content')
    @if (session('form_success'))
        <script>
            window.addEventListener('load', function () {
                alert(@json(session('form_success')));
            });
        </script>
    @endif
    @yield('scripts')
</body>
</html>
