<!doctype html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>RohrFrisch Website | Neue Anfrage</title>
</head>
<body style="margin:0; padding:24px 0; background-color:#f4f7fb; font-family:Arial, Helvetica, sans-serif; color:#1f2937;">
    <table role="presentation" cellpadding="0" cellspacing="0" border="0" width="100%" style="background-color:#f4f7fb;">
        <tr>
            <td align="center" style="padding:0 16px;">
                <table role="presentation" cellpadding="0" cellspacing="0" border="0" width="100%" style="max-width:680px; background-color:#ffffff; border-radius:20px; overflow:hidden; border:1px solid #e5e7eb;">
                    <tr>
                        <td style="background:linear-gradient(135deg, #1d2d7a 0%, #2745b5 100%); padding:28px 32px; color:#ffffff;">
                            <div style="font-size:30px; font-weight:700; line-height:1.1;">RohrFrisch</div>
                            <div style="margin-top:8px; font-size:14px; letter-spacing:0.08em; text-transform:uppercase; color:#dbe6ff;">
                                Abfluss &amp; Rohrreinigung
                            </div>
                        </td>
                    </tr>

                    <tr>
                        <td style="padding:32px;">
                            <div style="display:inline-block; background-color:#eef2ff; color:#2745b5; font-size:12px; font-weight:700; letter-spacing:0.08em; text-transform:uppercase; padding:8px 12px; border-radius:999px;">
                                Neue Formularanfrage
                            </div>

                            <h1 style="margin:18px 0 10px; font-size:28px; line-height:1.25; color:#111827;">
                                RohrFrisch Website
                            </h1>

                            <p style="margin:0 0 24px; font-size:15px; line-height:1.7; color:#4b5563;">
                                Ueber die Website ist eine neue Nachricht eingegangen. Die wichtigsten Angaben finden Sie unten in der Zusammenfassung.
                            </p>

                            <table role="presentation" cellpadding="0" cellspacing="0" border="0" width="100%" style="margin-bottom:24px; border-collapse:separate; border-spacing:0; background-color:#f9fafb; border:1px solid #e5e7eb; border-radius:16px; overflow:hidden;">
                                <tr>
                                    <td style="padding:16px 20px; border-bottom:1px solid #e5e7eb; width:180px; font-size:13px; font-weight:700; color:#6b7280;">
                                        Formular
                                    </td>
                                    <td style="padding:16px 20px; border-bottom:1px solid #e5e7eb; font-size:15px; color:#111827;">
                                        {{ ucfirst($payload['form_kind']) }}
                                    </td>
                                </tr>
                                <tr>
                                    <td style="padding:16px 20px; width:180px; font-size:13px; font-weight:700; color:#6b7280;">
                                        Quellseite
                                    </td>
                                    <td style="padding:16px 20px; font-size:15px; color:#111827; word-break:break-word;">
                                        @if (!empty($payload['source_url']))
                                            <a href="{{ $payload['source_url'] }}" style="color:#2745b5; text-decoration:none;">{{ $payload['source_url'] }}</a>
                                        @else
                                            Nicht verfuegbar
                                        @endif
                                    </td>
                                </tr>
                            </table>

                            <table role="presentation" cellpadding="0" cellspacing="0" border="0" width="100%" style="border-collapse:separate; border-spacing:0; border:1px solid #e5e7eb; border-radius:16px; overflow:hidden;">
                                @foreach($payload['fields'] as $label => $value)
                                    <tr>
                                        <td style="padding:16px 20px; width:180px; vertical-align:top; background-color:#f9fafb; border-bottom:1px solid #e5e7eb; font-size:13px; font-weight:700; color:#6b7280;">
                                            {{ $label }}
                                        </td>
                                        <td style="padding:16px 20px; vertical-align:top; border-bottom:1px solid #e5e7eb; font-size:15px; line-height:1.7; color:#111827;">
                                            {!! nl2br(e($value)) !!}
                                        </td>
                                    </tr>
                                @endforeach
                            </table>

                            <p style="margin:24px 0 0; font-size:13px; line-height:1.7; color:#6b7280;">
                                Diese E-Mail wurde automatisch ueber das Formular der RohrFrisch Website erstellt.
                            </p>
                        </td>
                    </tr>

                    <tr>
                        <td style="padding:20px 32px; background-color:#111827; color:#d1d5db; font-size:12px; line-height:1.7;">
                            RohrFrisch<br>
                            Schnelle Hilfe bei Abfluss- und Rohrreinigungsanfragen
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>
</html>
