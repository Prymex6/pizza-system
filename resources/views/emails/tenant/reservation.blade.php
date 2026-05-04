<!DOCTYPE html>
<html lang="pl">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Rezerwacja</title>
</head>
@php
  $primaryColor = \App\Models\Tenant\Setting::get('theme_primary_color', '#dc2626');
  $restaurantName = \App\Models\Tenant\Setting::get('restaurant_name', config('app.name'));
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
              <p style="margin:0;font-size:13px;color:#6b7280;">Rezerwacje</p>
            </td>
          </tr>

          {{-- Card --}}
          <tr>
            <td style="background:#ffffff;border-radius:12px;overflow:hidden;box-shadow:0 1px 3px rgba(0,0,0,.1);">

              {{-- Colored top bar --}}
              <table width="100%" cellpadding="0" cellspacing="0">
                <tr>
                  <td align="center" style="background:{{ $primaryColor }};padding:28px 32px;">
                    <p style="margin:0 0 6px 0;font-size:20px;font-weight:700;color:#ffffff;">{{ $restaurantName }}</p>
                    <p style="margin:0;font-size:13px;color:#ffffff;opacity:.85;">Rezerwacja stolika</p>
                  </td>
                </tr>
              </table>

              {{-- Body --}}
              <table width="100%" cellpadding="0" cellspacing="0">
                <tr>
                  <td style="padding:32px;">

                    <p style="margin:0 0 16px 0;font-size:15px;color:#374151;">Cześć, <strong>{{ $reservation->customer_name }}</strong>!</p>

                    @php
                      $cfg = match($event) {
                        'confirmed' => ['icon' => '✅', 'title' => 'Rezerwacja potwierdzona!', 'desc' => 'Twoja rezerwacja została potwierdzona. Czekamy na Ciebie!', 'bg' => '#dcfce7', 'color' => '#15803d'],
                        'cancelled' => ['icon' => '❌', 'title' => 'Rezerwacja anulowana', 'desc' => 'Niestety Twoja rezerwacja została anulowana. Przepraszamy za niedogodności.', 'bg' => '#fee2e2', 'color' => '#b91c1c'],
                        default     => ['icon' => '📅', 'title' => 'Rezerwacja przyjęta!', 'desc' => 'Twoja rezerwacja została przyjęta i oczekuje na potwierdzenie.', 'bg' => '#fef9c3', 'color' => '#92400e'],
                      };
                    @endphp

                    {{-- Status card --}}
                    <table width="100%" cellpadding="0" cellspacing="0" style="margin-bottom:20px;">
                      <tr>
                        <td align="center" style="background:{{ $cfg['bg'] }};padding:24px;border-radius:10px;text-align:center;">
                          <p style="margin:0 0 10px 0;font-size:48px;line-height:1;">{{ $cfg['icon'] }}</p>
                          <p style="margin:0 0 6px 0;font-size:20px;font-weight:700;color:{{ $cfg['color'] }};">{{ $cfg['title'] }}</p>
                          <p style="margin:0;font-size:14px;color:{{ $cfg['color'] }};opacity:.85;">{{ $cfg['desc'] }}</p>
                        </td>
                      </tr>
                    </table>

                    {{-- Details box --}}
                    <table width="100%" cellpadding="0" cellspacing="0" style="margin-bottom:20px;">
                      <tr>
                        <td style="background:#f9fafb;border-radius:8px;padding:16px 20px;">
                          <table width="100%" cellpadding="0" cellspacing="0">
                            <tr>
                              <td style="padding:6px 0;font-size:14px;color:#6b7280;width:40%;">Data:</td>
                              <td style="padding:6px 0;font-size:14px;font-weight:600;color:#111827;">{{ \Carbon\Carbon::parse($reservation->reservation_date)->format('d.m.Y') }}</td>
                            </tr>
                            <tr>
                              <td style="padding:6px 0;font-size:14px;color:#6b7280;">Godzina:</td>
                              <td style="padding:6px 0;font-size:14px;font-weight:600;color:#111827;">{{ \Illuminate\Support\Str::substr($reservation->reservation_time, 0, 5) }}</td>
                            </tr>
                            <tr>
                              <td style="padding:6px 0;font-size:14px;color:#6b7280;">Liczba osób:</td>
                              <td style="padding:6px 0;font-size:14px;font-weight:600;color:#111827;">{{ $reservation->party_size }}</td>
                            </tr>
                            @if($reservation->notes)
                            <tr>
                              <td style="padding:6px 0;font-size:14px;color:#6b7280;">Uwagi:</td>
                              <td style="padding:6px 0;font-size:14px;font-weight:600;color:#111827;">{{ $reservation->notes }}</td>
                            </tr>
                            @endif
                          </table>
                        </td>
                      </tr>
                    </table>

                    @if(\App\Models\Tenant\Setting::get('restaurant_phone'))
                    <p style="margin:0;font-size:13px;color:#6b7280;text-align:center;">
                      Pytania? Zadzwoń:
                      <a href="tel:{{ \App\Models\Tenant\Setting::get('restaurant_phone') }}" style="color:{{ $primaryColor }};font-weight:600;text-decoration:none;">{{ \App\Models\Tenant\Setting::get('restaurant_phone') }}</a>
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
              &copy; {{ date('Y') }} {{ $restaurantName }}. Wiadomość wysłana automatycznie &mdash; nie odpowiadaj na ten e-mail.
            </td>
          </tr>

        </table>
      </td>
    </tr>
  </table>

</body>
</html>
