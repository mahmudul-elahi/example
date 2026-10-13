<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="color-scheme" content="light">
    <title>@yield('title', config('app.name'))</title>
</head>
<body style="margin:0;padding:0;background-color:#f4f4f5;color:#18181b;font-family:-apple-system,BlinkMacSystemFont,'Segoe UI',Roboto,Helvetica,Arial,sans-serif;">
    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="background-color:#f4f4f5;">
        <tr>
            <td align="center" style="padding:32px 16px;">
                <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="max-width:520px;background-color:#ffffff;border-radius:12px;">
                    <tr>
                        <td style="padding:28px 32px 4px;font-size:16px;font-weight:700;letter-spacing:-0.01em;color:#18181b;">
                            {{ config('app.name') }}
                        </td>
                    </tr>
                    <tr>
                        <td style="padding:20px 32px 32px;font-size:15px;line-height:1.6;color:#3f3f46;">
                            @yield('content')
                        </td>
                    </tr>
                    <tr>
                        <td style="padding:16px 32px 28px;border-top:1px solid #f4f4f5;font-size:12px;line-height:1.6;color:#a1a1aa;">
                            @yield('footer', 'If you did not expect this email, you can safely ignore it.')
                        </td>
                    </tr>
                </table>
                <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="max-width:520px;">
                    <tr>
                        <td align="center" style="padding:16px 8px 0;font-size:12px;color:#a1a1aa;">
                            &copy; {{ date('Y') }} {{ config('app.name') }}
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>
</html>
