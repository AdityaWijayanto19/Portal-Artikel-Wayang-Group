<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reset Password</title>
</head>
<body style="margin: 0; padding: 0; background-color: #f4f7f9; font-family: Arial, Helvetica, sans-serif; -webkit-font-smoothing: antialiased;">

    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background-color: #f4f7f9; padding: 40px 0;">
        <tr>
            <td align="center">
                <table role="presentation" width="600" cellpadding="0" cellspacing="0" style="max-width: 600px; width: 100%; background-color: #ffffff; border-radius: 12px; overflow: hidden; box-shadow: 0 4px 24px rgba(0,0,0,0.06);">

                    {{-- Header --}}
                    <tr>
                        <td align="center" style="padding: 36px 40px 24px;">
                            <!--[if mso]>
                            <table role="presentation" cellpadding="0" cellspacing="0" width="48" align="center"><tr><td align="center">
                            <![endif]-->
                            <img src="https://artikel.wayang.group/images/logo.png"
                                 alt="Portal Artikel Wayang"
                                 width="48"
                                 style="display: block; width: 48px; height: auto; margin: 0 auto;">
                            <!--[if mso]>
                            </td></tr></table>
                            <![endif]-->
                            <h1 style="margin: 12px 0 0; font-size: 17px; font-weight: 700; color: #2d2d2d; letter-spacing: -0.3px;">
                                Portal Artikel Wayang
                            </h1>
                        </td>
                    </tr>

                    {{-- Divider --}}
                    <tr>
                        <td style="padding: 0 40px;">
                            <table role="presentation" width="100%" cellpadding="0" cellspacing="0">
                                <tr>
                                    <td style="border-top: 1px solid #eee; font-size: 0; line-height: 0;">&nbsp;</td>
                                </tr>
                            </table>
                        </td>
                    </tr>

                    {{-- Body --}}
                    <tr>
                        <td style="padding: 28px 40px 0;">
                            <p style="margin: 0 0 16px; font-size: 14px; line-height: 1.7; color: #555555;">
                                Halo <strong style="color: #2d2d2d;">{{ $user->name }}</strong>,
                            </p>

                            <p style="margin: 0 0 16px; font-size: 14px; line-height: 1.7; color: #555555;">
                                Kami menerima permintaan untuk mengatur ulang password akun Anda di <strong style="color: #2d2d2d;">Portal Artikel Wayang</strong>.
                            </p>

                            <p style="margin: 0 0 24px; font-size: 14px; line-height: 1.7; color: #555555;">
                                Klik tombol di bawah ini untuk mengatur password baru:
                            </p>

                            {{-- CTA Button --}}
                            <table role="presentation" cellpadding="0" cellspacing="0" style="margin-bottom: 28px;">
                                <tr>
                                    <td align="center" style="background-color: #c59b27; border-radius: 6px;">
                                        <a href="{{ $resetUrl }}"
                                           target="_blank"
                                           style="display: inline-block; padding: 13px 32px; font-size: 14px; font-weight: 600; color: #ffffff; text-decoration: none; letter-spacing: 0.2px;">
                                            Atur Password Baru
                                        </a>
                                    </td>
                                </tr>
                            </table>

                            {{-- Expiry Notice --}}
                            <p style="margin: 0 0 20px; font-size: 13px; line-height: 1.6; color: #999999;">
                                ⏱ Link ini akan kedaluwarsa dalam <strong style="color: #777;">60 menit</strong>.
                            </p>

                            {{-- Disclaimer --}}
                            <p style="margin: 0; font-size: 13px; line-height: 1.6; color: #999999;">
                                Jika Anda tidak meminta reset password, abaikan email ini. Tidak ada perubahan yang dilakukan pada akun Anda.
                            </p>
                        </td>
                    </tr>

                    {{-- Footer --}}
                    <tr>
                        <td style="padding: 36px 40px 32px;">
                            <table role="presentation" width="100%" cellpadding="0" cellspacing="0">
                                <tr>
                                    <td style="border-top: 1px solid #eee; font-size: 0; line-height: 0;">&nbsp;</td>
                                </tr>
                            </table>
                            <p style="margin: 20px 0 0; font-size: 12px; line-height: 1.5; color: #bbbbbb; text-align: center;">
                                © {{ date('Y') }} Portal Artikel Wayang. All rights reserved.
                            </p>
                        </td>
                    </tr>

                </table>
            </td>
        </tr>
    </table>

</body>
</html>
