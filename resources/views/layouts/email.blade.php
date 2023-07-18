<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
</head>
<body style="padding:0; margin:0;">
<table style="width:100%">
    <tr style="background:#000;">
        <td style="border-bottom:1px solid #eee;">
            <table width="100%" cellpadding="10">
                <tr>
                    <td>
                        <a href="{{ URL::to('/') }}">
                            <img src="{{ asset('assets/img/email/dynafios.png') }}" alt="DYNAFIOS Logo"/>
                        </a>
                    </td>
                </tr>
            </table>
        </td>
    </tr>
    <tr>
        <td>
            <table width="100%" cellpadding="10">
                <tr>
                    <td>@yield('body')</td>
                </tr>
            </table>
        </td>
    </tr>
</table>
</body>
</html>