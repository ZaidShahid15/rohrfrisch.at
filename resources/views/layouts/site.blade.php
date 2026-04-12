<!doctype html>
<html lang="@yield('html_lang', 'en-US')">
<head>
    <title>@yield('title', 'RohrFrisch')</title>
    <style>
        .testi-card_quote img {
            width: 72px;
            height: 72px;
            object-fit: contain;
        }

        .testi-card_review i.fas.fa-star {
            display: inline-block;
            width: 18px;
            height: 18px;
            margin-right: 4px;
            font-style: normal;
            font-size: 0;
            line-height: 1;
            vertical-align: middle;
        }

        .testi-card_review i.fas.fa-star::before {
            content: "★";
            display: inline-block;
            color: #e83a15;
            font-size: 16px;
            line-height: 1;
        }

        .scroll-top::after {
            content: "\2191";
            font-family: Arial, sans-serif;
            font-size: 22px;
            font-weight: 700;
            line-height: 1;
        }
    </style>
    @yield('head')
</head>
<body @yield('body_attributes')>
    @yield('content')
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            document.querySelectorAll('form.wpcf7-form, form.elementor-form').forEach(function (form) {
                form.addEventListener('submit', function (event) {
                    if (form.dataset.nativeSubmit === '1') {
                        return;
                    }

                    event.preventDefault();
                    event.stopImmediatePropagation();
                    form.dataset.nativeSubmit = '1';
                    HTMLFormElement.prototype.submit.call(form);
                }, true);
            });
        });
    </script>
    @yield('scripts')
</body>
</html>
