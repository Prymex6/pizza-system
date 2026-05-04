<!DOCTYPE html>
<html lang="pl">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Dziękujemy za wiadomość</title>
</head>
<body style="margin:0;padding:0;background-color:#f3f4f6;font-family:Arial,Helvetica,sans-serif;color:#1f2937;">

  <table width="100%" cellpadding="0" cellspacing="0" style="background-color:#f3f4f6;padding:40px 0;">
    <tr>
      <td align="center">
        <table width="560" cellpadding="0" cellspacing="0" style="max-width:560px;width:100%;">

          {{-- Header --}}
          <tr>
            <td align="center" style="padding:0 0 24px 0;">
              <p style="margin:0 0 4px 0;font-size:22px;font-weight:700;color:#1d4ed8;">{{ config('app.name') }}</p>
              <p style="margin:0;font-size:13px;color:#6b7280;">System zarządzania restauracją</p>
            </td>
          </tr>

          {{-- Card --}}
          <tr>
            <td style="background:#ffffff;border-radius:12px;overflow:hidden;box-shadow:0 1px 3px rgba(0,0,0,.1);">

              {{-- Colored top bar --}}
              <table width="100%" cellpadding="0" cellspacing="0">
                <tr>
                  <td align="center" style="background:#1d4ed8;padding:28px 32px;">
                    <p style="margin:0 0 6px 0;font-size:20px;font-weight:700;color:#ffffff;">Dziękujemy za wiadomość!</p>
                    <p style="margin:0;font-size:13px;color:#ffffff;opacity:.85;">{{ config('app.name') }} &middot; {{ parse_url(config('app.url'), PHP_URL_HOST) }}</p>
                  </td>
                </tr>
              </table>

              {{-- Body --}}
              <table width="100%" cellpadding="0" cellspacing="0">
                <tr>
                  <td style="padding:32px;">

                    <p style="margin:0 0 8px 0;font-size:15px;color:#374151;">Cześć, <strong>{{ $inquiry->name }}</strong>!</p>
                    <p style="margin:0 0 20px 0;font-size:14px;color:#6b7280;line-height:1.7;">
                      Otrzymaliśmy Twoje zapytanie i odpowiemy na podany adres e-mail w ciągu 24 godzin w dni robocze. Poniżej znajdziesz kopię swojej wiadomości.
                    </p>

                    {{-- Message copy --}}
                    <p style="margin:0 0 8px 0;font-size:13px;font-weight:600;text-transform:uppercase;letter-spacing:.5px;color:#6b7280;">Twoja wiadomość</p>
                    <table width="100%" cellpadding="0" cellspacing="0" style="margin-bottom:24px;">
                      <tr>
                        <td style="background:#f9fafb;border-left:4px solid #1d4ed8;border-radius:0 8px 8px 0;padding:14px 16px;font-size:14px;line-height:1.8;color:#374151;">
                          {{ nl2br(e($inquiry->message)) }}
                        </td>
                      </tr>
                    </table>

                    @if($inquiry->subject)
                    <p style="margin:0 0 16px 0;font-size:13px;color:#6b7280;">
                      Temat: <strong style="color:#374151;">{{ $inquiry->subject }}</strong>
                    </p>
                    @endif

                    <p style="margin:0;font-size:13px;color:#6b7280;">
                      Masz pytania? Napisz ponownie przez
                      <a href="{{ rtrim(config('app.url'), '/') }}/#kontakt" style="color:#1d4ed8;font-weight:600;text-decoration:none;">{{ parse_url(config('app.url'), PHP_URL_HOST) }}</a>
                    </p>

                  </td>
                </tr>
              </table>

            </td>
          </tr>

          {{-- Footer --}}
          <tr>
            <td align="center" style="padding:24px 0 0 0;font-size:12px;color:#9ca3af;line-height:1.6;">
              &copy; {{ date('Y') }} {{ config('app.name') }} &middot; Dziękujemy za kontakt!
            </td>
          </tr>

        </table>
      </td>
    </tr>
  </table>

</body>
</html>
