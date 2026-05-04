<!DOCTYPE html>
<html lang="pl">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Dziękujemy za wiadomość</title>
</head>
@php
  $primaryColor   = \App\Models\Tenant\Setting::get('theme_primary_color', '#dc2626');
  $restaurantName = \App\Models\Tenant\Setting::get('restaurant_name', config('app.name'));
  $restaurantPhone = \App\Models\Tenant\Setting::get('restaurant_phone');
@endphp
<body style="margin:0;padding:0;background-color:#f3f4f6;font-family:Arial,Helvetica,sans-serif;color:#1f2937;">

  <table width="100%" cellpadding="0" cellspacing="0" style="background-color:#f3f4f6;padding:40px 0;">
    <tr>
      <td align="center">
        <table width="560" cellpadding="0" cellspacing="0" style="max-width:560px;width:100%;">

          {{-- Header --}}
          <tr>
            <td align="center" style="padding:0 0 24px 0;">
              <p style="margin:0 0 4px 0;font-size:22px;font-weight:700;color:{{ $primaryColor }};">{{ $restaurantName }}</p>
              <p style="margin:0;font-size:13px;color:#6b7280;">Formularz kontaktowy</p>
            </td>
          </tr>

          {{-- Card --}}
          <tr>
            <td style="background:#ffffff;border-radius:12px;overflow:hidden;box-shadow:0 1px 3px rgba(0,0,0,.1);">

              {{-- Colored top bar --}}
              <table width="100%" cellpadding="0" cellspacing="0">
                <tr>
                  <td align="center" style="background:{{ $primaryColor }};padding:28px 32px;">
                    <p style="margin:0 0 6px 0;font-size:20px;font-weight:700;color:#ffffff;">Dziękujemy za wiadomość!</p>
                    <p style="margin:0;font-size:13px;color:#ffffff;opacity:.85;">{{ $restaurantName }}</p>
                  </td>
                </tr>
              </table>

              {{-- Body --}}
              <table width="100%" cellpadding="0" cellspacing="0">
                <tr>
                  <td style="padding:32px;">

                    <p style="margin:0 0 8px 0;font-size:15px;color:#374151;">Cześć, <strong>{{ $senderName }}</strong>!</p>
                    <p style="margin:0 0 20px 0;font-size:14px;color:#6b7280;line-height:1.7;">
                      Otrzymaliśmy Twoją wiadomość i postaramy się odpowiedzieć jak najszybciej. Poniżej znajdziesz kopię swojej wiadomości.
                    </p>

                    {{-- Message copy --}}
                    <p style="margin:0 0 8px 0;font-size:13px;font-weight:600;text-transform:uppercase;letter-spacing:.5px;color:#6b7280;">Twoja wiadomość</p>
                    <table width="100%" cellpadding="0" cellspacing="0" style="margin-bottom:24px;">
                      <tr>
                        <td style="background:#f9fafb;border-left:4px solid {{ $primaryColor }};border-radius:0 8px 8px 0;padding:14px 16px;font-size:14px;line-height:1.8;color:#374151;">
                          {{ nl2br(e($senderMessage)) }}
                        </td>
                      </tr>
                    </table>

                    @if($restaurantPhone)
                    <p style="margin:0;font-size:13px;color:#6b7280;">
                      Pilna sprawa? Zadzwoń do nas:
                      <a href="tel:{{ $restaurantPhone }}" style="color:{{ $primaryColor }};font-weight:600;text-decoration:none;">{{ $restaurantPhone }}</a>
                    </p>
                    @endif

                  </td>
                </tr>
              </table>

            </td>
          </tr>

          {{-- Footer --}}
          <tr>
            <td align="center" style="padding:24px 0 0 0;font-size:12px;color:#9ca3af;line-height:1.6;">
              &copy; {{ date('Y') }} {{ $restaurantName }} &middot; Dziękujemy za kontakt!
            </td>
          </tr>

        </table>
      </td>
    </tr>
  </table>

</body>
</html>
