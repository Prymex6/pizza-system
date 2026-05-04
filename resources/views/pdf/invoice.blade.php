<!DOCTYPE html>
<html lang="pl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Faktura VAT {{ $order->invoice_number ?? $order->order_number }}</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: Arial, sans-serif; font-size: 14px; color: #333; background: #fff; }
        .page { max-width: 800px; margin: 0 auto; padding: 40px; }
        .header { display: flex; justify-content: space-between; margin-bottom: 40px; padding-bottom: 20px; border-bottom: 2px solid #e5e7eb; }
        .company-name { font-size: 22px; font-weight: bold; color: #1e40af; }
        .invoice-title { font-size: 28px; font-weight: bold; color: #374151; text-align: right; }
        .invoice-number { font-size: 14px; color: #6b7280; }
        .addresses { display: grid; grid-template-columns: 1fr 1fr; gap: 40px; margin-bottom: 30px; }
        .address-block h3 { font-size: 11px; text-transform: uppercase; color: #9ca3af; letter-spacing: 0.05em; margin-bottom: 8px; }
        .address-block p { line-height: 1.6; }
        .items-table { width: 100%; border-collapse: collapse; margin-bottom: 20px; }
        .items-table th { background: #f9fafb; padding: 10px 12px; text-align: left; font-size: 12px; text-transform: uppercase; color: #6b7280; border-bottom: 1px solid #e5e7eb; }
        .items-table td { padding: 10px 12px; border-bottom: 1px solid #f3f4f6; }
        .items-table .text-right { text-align: right; }
        .totals { float: right; width: 280px; }
        .totals table { width: 100%; }
        .totals td { padding: 6px 0; }
        .totals .total-row td { font-size: 16px; font-weight: bold; border-top: 2px solid #374151; padding-top: 10px; margin-top: 5px; }
        .totals .label { color: #6b7280; }
        .totals .amount { text-align: right; }
        .meta { clear: both; margin-top: 40px; padding-top: 20px; border-top: 1px solid #e5e7eb; font-size: 12px; color: #9ca3af; }
        .badge { display: inline-block; padding: 4px 12px; border-radius: 9999px; font-size: 12px; font-weight: 600; }
        .badge-paid { background: #d1fae5; color: #065f46; }
        .badge-unpaid { background: #fee2e2; color: #991b1b; }
        .print-btn { position: fixed; top: 20px; right: 20px; padding: 10px 20px; background: #1e40af; color: white; border: none; border-radius: 8px; cursor: pointer; font-size: 14px; font-weight: 600; }
        @media print {
            .print-btn { display: none; }
            .no-print { display: none !important; }
            .page { padding: 0; }
        }
    </style>
</head>
<body>

<button class="print-btn" onclick="window.print()">Drukuj / Zapisz PDF</button>

<div class="page">
    <!-- Header -->
    <div class="header">
        <div>
            <div class="company-name">{{ $settings['restaurant_name'] ?? 'Restauracja' }}</div>
            @if(!empty($settings['restaurant_address']))
                <p style="color: #6b7280; margin-top: 4px;">{{ $settings['restaurant_address'] }}</p>
            @endif
            @if(!empty($settings['restaurant_phone']))
                <p style="color: #6b7280;">Tel: {{ $settings['restaurant_phone'] }}</p>
            @endif
            @if(!empty($settings['restaurant_email']))
                <p style="color: #6b7280;">{{ $settings['restaurant_email'] }}</p>
            @endif
            @if(!empty($settings['restaurant_nip']))
                <p style="color: #6b7280; margin-top: 4px;">NIP: {{ $settings['restaurant_nip'] }}</p>
            @endif
        </div>
        <div>
            <div class="invoice-title">Faktura VAT</div>
            <div class="invoice-number" style="margin-top: 8px; font-weight: 600; color: #374151;">Nr: {{ $order->invoice_number ?? $order->order_number }}</div>
            <div class="invoice-number">Data wystawienia: {{ $order->created_at->format('d.m.Y') }}</div>
            <div class="invoice-number">Data sprzedaży: {{ $order->created_at->format('d.m.Y') }}</div>
            <div style="margin-top: 8px;">
                <span class="{{ $order->payment_status === 'paid' ? 'badge badge-paid' : 'badge badge-unpaid' }}">
                    {{ $order->payment_status === 'paid' ? 'OPŁACONE' : 'NIEOPŁACONE' }}
                </span>
            </div>
        </div>
    </div>

    <!-- Addresses -->
    <div class="addresses">
        <div class="address-block">
            <h3>Sprzedawca</h3>
            <p>
                <strong>{{ $settings['restaurant_name'] ?? 'Restauracja' }}</strong><br>
                @if(!empty($settings['restaurant_address'])){{ $settings['restaurant_address'] }}<br>@endif
                @if(!empty($settings['restaurant_nip']))NIP: {{ $settings['restaurant_nip'] }}<br>@endif
                @if(!empty($settings['restaurant_phone']))Tel: {{ $settings['restaurant_phone'] }}<br>@endif
            </p>
        </div>
        <div class="address-block">
            <h3>Nabywca</h3>
            <p>
                <strong>{{ $order->customer_name ?? 'Klient' }}</strong><br>
                @if($order->delivery_address)
                    {{ $order->delivery_address }}<br>
                    {{ $order->delivery_city }}{{ $order->delivery_postal_code ? ', ' . $order->delivery_postal_code : '' }}<br>
                @endif
                @if($order->customer_phone)Tel: {{ $order->customer_phone }}<br>@endif
                @if($order->customer_email){{ $order->customer_email }}<br>@endif
                @if($order->buyer_nip)<strong>NIP: {{ $order->buyer_nip }}</strong><br>@endif
            </p>
            @if(!$order->buyer_nip)
            <form id="nipForm" style="margin-top: 10px; display: flex; gap: 6px;" class="no-print">
                <input type="text" id="nipInput" placeholder="NIP nabywcy (opcjonalnie)" maxlength="15"
                    style="border: 1px solid #d1d5db; border-radius: 4px; padding: 4px 8px; font-size: 12px; flex: 1;">
                <button type="button" onclick="addNip()"
                    style="padding: 4px 12px; background: #1e40af; color: white; border: none; border-radius: 4px; font-size: 12px; cursor: pointer;">Dodaj</button>
            </form>
            @endif
        </div>
    </div>

    <!-- Order items -->
    <table class="items-table">
        <thead>
            <tr>
                <th>Pozycja</th>
                <th class="text-right">Ilość</th>
                <th class="text-right">Cena jedn.</th>
                <th class="text-right">Wartość</th>
            </tr>
        </thead>
        <tbody>
            @foreach($order->items as $item)
            <tr>
                <td>
                    {{ $item->name }}
                    @if($item->variant_name) <span style="color: #9ca3af; font-size: 12px;">({{ $item->variant_name }})</span>@endif
                    @if($item->addons)
                        @php $addons = is_string($item->addons) ? json_decode($item->addons, true) : $item->addons; @endphp
                        @if($addons && count($addons))
                            <br><span style="color: #9ca3af; font-size: 11px;">+ {{ implode(', ', array_column($addons, 'name')) }}</span>
                        @endif
                    @endif
                </td>
                <td class="text-right">{{ $item->quantity }}</td>
                <td class="text-right">{{ number_format($item->price, 2, ',', ' ') }} zł</td>
                <td class="text-right">{{ number_format($item->price * $item->quantity, 2, ',', ' ') }} zł</td>
            </tr>
            @endforeach
        </tbody>
    </table>

    <!-- VAT breakdown -->
    <div style="margin-bottom: 20px; clear: both;">
        <h4 style="font-size: 11px; text-transform: uppercase; color: #9ca3af; letter-spacing: 0.05em; margin-bottom: 6px;">Zestawienie VAT</h4>
        <table style="width: 100%; border-collapse: collapse; font-size: 13px;">
            <thead>
                <tr style="background: #f9fafb;">
                    <th style="padding: 6px 10px; text-align: left; border: 1px solid #e5e7eb; color: #6b7280; font-size: 11px; text-transform: uppercase;">Stawka</th>
                    <th style="padding: 6px 10px; text-align: right; border: 1px solid #e5e7eb; color: #6b7280; font-size: 11px; text-transform: uppercase;">Netto</th>
                    <th style="padding: 6px 10px; text-align: right; border: 1px solid #e5e7eb; color: #6b7280; font-size: 11px; text-transform: uppercase;">VAT</th>
                    <th style="padding: 6px 10px; text-align: right; border: 1px solid #e5e7eb; color: #6b7280; font-size: 11px; text-transform: uppercase;">Brutto</th>
                </tr>
            </thead>
            <tbody>
                @foreach($vatGroups as $vg)
                @if($vg['gross'] > 0)
                <tr>
                    <td style="padding: 6px 10px; border: 1px solid #e5e7eb;">{{ $vg['rate'] }}</td>
                    <td style="padding: 6px 10px; border: 1px solid #e5e7eb; text-align: right;">{{ number_format($vg['net'], 2, ',', ' ') }} zł</td>
                    <td style="padding: 6px 10px; border: 1px solid #e5e7eb; text-align: right;">{{ number_format($vg['vat'], 2, ',', ' ') }} zł</td>
                    <td style="padding: 6px 10px; border: 1px solid #e5e7eb; text-align: right;">{{ number_format($vg['gross'], 2, ',', ' ') }} zł</td>
                </tr>
                @endif
                @endforeach
            </tbody>
        </table>
    </div>

    <!-- Totals -->
    <div class="totals">
        <table>
            <tr>
                <td class="label">Suma częściowa:</td>
                <td class="amount">{{ number_format($order->subtotal, 2, ',', ' ') }} zł</td>
            </tr>
            @if($order->delivery_fee > 0)
            <tr>
                <td class="label">Dostawa:</td>
                <td class="amount">{{ number_format($order->delivery_fee, 2, ',', ' ') }} zł</td>
            </tr>
            @endif
            @if($order->discount > 0)
            <tr>
                <td class="label">Rabat:</td>
                <td class="amount" style="color: #059669;">-{{ number_format($order->discount, 2, ',', ' ') }} zł</td>
            </tr>
            @endif
            <tr class="total-row">
                <td class="label"><strong>RAZEM:</strong></td>
                <td class="amount"><strong>{{ number_format($order->total, 2, ',', ' ') }} zł</strong></td>
            </tr>
        </table>
    </div>

    <!-- Meta -->
    <div class="meta">
        <p>Typ zamówienia: {{ ['delivery' => 'Dostawa', 'pickup' => 'Odbiór', 'dine_in' => 'Na miejscu'][$order->type] ?? $order->type }}</p>
        <p>Metoda płatności: {{ ['cash' => 'Gotówka', 'card_on_delivery' => 'Karta', 'online' => 'Online'][$order->payment_method] ?? $order->payment_method }}</p>
        @if($order->paid_at)<p>Opłacono: {{ $order->paid_at->format('d.m.Y H:i') }}</p>@endif
        <p style="margin-top: 12px; font-size: 11px;">Dokument wygenerowany przez system {{ config('app.name') }}. Niniejszy dokument stanowi potwierdzenie złożonego zamówienia.</p>
    </div>
</div>

<script>
function addNip() {
    const nip = document.getElementById('nipInput').value.trim();
    if (!nip) return;
    const url = new URL(window.location.href);
    url.searchParams.set('nip', nip);
    window.location.href = url.toString();
}
</script>
</body>
</html>
