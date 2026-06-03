<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $survey->title ?: 'Supplier Emissions Survey' }}</title>
</head>
<body style="margin:0; padding:0; background-color:#f4f6f8; font-family: Arial, Helvetica, sans-serif; color:#333;">
    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background-color:#f4f6f8; padding:24px 0;">
        <tr>
            <td align="center">
                <table role="presentation" width="600" cellpadding="0" cellspacing="0" style="max-width:600px; width:100%; background:#ffffff; border-radius:8px; overflow:hidden; box-shadow:0 1px 4px rgba(0,0,0,0.08);">
                    {{-- Header --}}
                    <tr>
                        <td style="background-color:#2e7d32; padding:24px 32px;">
                            <h1 style="margin:0; color:#ffffff; font-size:20px; font-weight:bold;">
                                {{ $companyName ?? config('mail.from.name') }}
                            </h1>
                            <p style="margin:4px 0 0; color:#d7ecd8; font-size:13px;">Greenhouse Gas Emissions Data Request</p>
                        </td>
                    </tr>

                    {{-- Body --}}
                    <tr>
                        <td style="padding:32px;">
                            <p style="margin:0 0 16px; font-size:15px;">
                                Dear {{ $supplierName ?: 'Supplier' }},
                            </p>

                            @if($isReminder)
                                <p style="margin:0 0 16px; font-size:15px; line-height:1.6;">
                                    This is a friendly reminder that {{ $companyName ?? 'we' }} requested your help completing an
                                    emissions data survey<strong>{{ $survey->title ? ': "' . $survey->title . '"' : '' }}</strong>.
                                    We have not yet received your response.
                                </p>
                            @else
                                <p style="margin:0 0 16px; font-size:15px; line-height:1.6;">
                                    {{ $companyName ?? 'We' }} is collecting greenhouse gas emissions data from suppliers as part of our
                                    Scope 3 carbon accounting. You have been invited to complete the survey
                                    <strong>{{ $survey->title ?: 'Supplier Emissions Survey' }}</strong>.
                                </p>
                            @endif

                            @if($survey->description)
                                <p style="margin:0 0 16px; font-size:14px; line-height:1.6; color:#555;">
                                    {{ $survey->description }}
                                </p>
                            @endif

                            @if($dueDate)
                                <p style="margin:0 0 16px; font-size:14px;">
                                    <strong>Please respond by:</strong>
                                    {{ \Illuminate\Support\Carbon::parse($dueDate)->format('F j, Y') }}
                                </p>
                            @endif

                            {{-- CTA --}}
                            <table role="presentation" cellpadding="0" cellspacing="0" style="margin:24px 0;">
                                <tr>
                                    <td style="border-radius:6px; background-color:#2e7d32;">
                                        <a href="{{ $link }}"
                                           style="display:inline-block; padding:13px 28px; font-size:15px; color:#ffffff; text-decoration:none; font-weight:bold; border-radius:6px;">
                                            Complete the Survey
                                        </a>
                                    </td>
                                </tr>
                            </table>

                            <p style="margin:0 0 8px; font-size:13px; color:#777;">
                                If the button does not work, copy and paste this link into your browser:
                            </p>
                            <p style="margin:0 0 16px; font-size:13px; word-break:break-all;">
                                <a href="{{ $link }}" style="color:#2e7d32;">{{ $link }}</a>
                            </p>

                            @if($expiresAt)
                                <p style="margin:0; font-size:12px; color:#999;">
                                    This link expires on {{ \Illuminate\Support\Carbon::parse($expiresAt)->format('F j, Y') }}.
                                </p>
                            @endif
                        </td>
                    </tr>

                    {{-- Footer --}}
                    <tr>
                        <td style="padding:20px 32px; background-color:#f0f2f4; border-top:1px solid #e3e6e8;">
                            <p style="margin:0; font-size:12px; color:#999; line-height:1.5;">
                                This email was sent by {{ $companyName ?? config('mail.from.name') }} regarding emissions data collection.
                                If you believe you received this in error, please disregard it.
                            </p>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>
</html>
