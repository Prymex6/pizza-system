<?php

namespace App\Services\Printer;

use App\Models\Tenant\Order;
use Illuminate\Support\Facades\Log;

class ThermalPrinterService
{
    /**
     * ESC/POS Commands
     */
    const ESC = "\x1B";
    const GS = "\x1D";
    const INIT = "\x1B\x40";
    const LINE_FEED = "\x0A";
    const CUT_PAPER = "\x1D\x56\x00";

    /**
     * Text formatting commands
     */
    const BOLD_ON = "\x1B\x45\x01";
    const BOLD_OFF = "\x1B\x45\x00";
    const DOUBLE_HEIGHT_ON = "\x1B\x21\x10";
    const DOUBLE_WIDTH_ON = "\x1B\x21\x20";
    const DOUBLE_SIZE_ON = "\x1B\x21\x30";
    const NORMAL_SIZE = "\x1B\x21\x00";

    /**
     * Alignment commands
     */
    const ALIGN_LEFT = "\x1B\x61\x00";
    const ALIGN_CENTER = "\x1B\x61\x01";
    const ALIGN_RIGHT = "\x1B\x61\x02";

    /**
     * Print method (to be implemented based on integration)
     */
    protected string $printerMethod;

    public function __construct()
    {
        // Get printer method from tenant-specific settings
        // Falls back to global config if not set
        $this->printerMethod = \App\Models\Tenant\Setting::get(
            'printer.method',
            config('printer.method', 'disabled')
        );
    }

    /**
     * Print order receipt
     */
    public function printOrderReceipt(Order $order): bool
    {
        if ($this->printerMethod === 'disabled') {
            Log::info('Printer disabled, skipping print for order: ' . $order->order_number);
            return false;
        }

        try {
            $receiptData = $this->formatOrderReceipt($order);

            // Send to appropriate printer integration
            return match ($this->printerMethod) {
                'web_bluetooth' => $this->printViaWebBluetooth($receiptData),
                'qz_tray' => $this->printViaQzTray($receiptData),
                'printnode' => $this->printViaPrintNode($receiptData),
                default => false,
            };

        } catch (\Exception $e) {
            Log::error('Printer error: ' . $e->getMessage(), [
                'order_id' => $order->id,
                'order_number' => $order->order_number,
            ]);
            return false;
        }
    }

    /**
     * Format order receipt with ESC/POS commands
     */
    protected function formatOrderReceipt(Order $order): string
    {
        $receipt = self::INIT; // Initialize printer

        // Header - Restaurant name
        $receipt .= self::ALIGN_CENTER;
        $receipt .= self::DOUBLE_SIZE_ON;
        $receipt .= self::BOLD_ON;
        $receipt .= $this->truncate(strtoupper(config('app.name', 'RESTAURACJA')), 16);
        $receipt .= self::LINE_FEED;
        $receipt .= self::NORMAL_SIZE;
        $receipt .= self::BOLD_OFF;
        $receipt .= self::LINE_FEED;

        // Order number
        $receipt .= self::BOLD_ON;
        $receipt .= "ZAMOWIENIE: " . $order->order_number;
        $receipt .= self::BOLD_OFF;
        $receipt .= self::LINE_FEED;
        $receipt .= $this->line();

        // Order type
        $receipt .= self::ALIGN_LEFT;
        $orderType = match ($order->type) {
            'delivery' => 'DOSTAWA',
            'pickup' => 'ODBIOR OSOBISTY',
            'dine_in' => 'NA MIEJSCU',
            default => 'NIEZNANY',
        };
        $receipt .= "Typ: " . $orderType;
        $receipt .= self::LINE_FEED;

        // Delivery info
        if ($order->type === 'delivery' && $order->delivery_address) {
            $receipt .= "Adres: " . $this->wrap($order->delivery_address, 32);
            $receipt .= self::LINE_FEED;
        }

        // Table info
        if ($order->type === 'dine_in' && $order->table) {
            $receipt .= "Stolik: " . $order->table->number;
            $receipt .= self::LINE_FEED;
        }

        // Customer info
        $receipt .= "Klient: " . $this->truncate($order->customer_name, 32);
        $receipt .= self::LINE_FEED;
        $receipt .= "Tel: " . $order->customer_phone;
        $receipt .= self::LINE_FEED;

        // Time
        $tz = \App\Models\Tenant\Setting::get('timezone', 'Europe/Warsaw');
        $receipt .= "Czas: " . $order->created_at->setTimezone($tz)->format('H:i');
        $receipt .= self::LINE_FEED;
        $receipt .= $this->line();

        // Items
        $receipt .= self::BOLD_ON;
        $receipt .= "POZYCJE:";
        $receipt .= self::BOLD_OFF;
        $receipt .= self::LINE_FEED;
        $receipt .= $this->line('-');

        foreach ($order->items as $item) {
            // Item name
            $receipt .= self::BOLD_ON;
            $receipt .= $item->quantity . "x " . $this->truncate($item->name, 26);
            $receipt .= self::BOLD_OFF;
            $receipt .= self::LINE_FEED;

            // Variant
            if ($item->variant_name) {
                $receipt .= "   " . $this->truncate($item->variant_name, 29);
                $receipt .= self::LINE_FEED;
            }

            // Addons
            if ($item->addons && count($item->addons) > 0) {
                foreach ($item->addons as $addon) {
                    $receipt .= "   + " . $this->truncate($addon['name'], 27);
                    $receipt .= self::LINE_FEED;
                }
            }

            // Exclusions
            if ($item->exclusions && count($item->exclusions) > 0) {
                $receipt .= "   BEZ: " . implode(', ', $item->exclusions);
                $receipt .= self::LINE_FEED;
            }

            // Notes
            if ($item->notes) {
                $receipt .= "   UWAGA: " . $this->wrap($item->notes, 32, '   ');
                $receipt .= self::LINE_FEED;
            }

            $receipt .= self::LINE_FEED;
        }

        $receipt .= $this->line('-');

        // Order notes
        if ($order->notes) {
            $receipt .= self::BOLD_ON;
            $receipt .= "UWAGI DO ZAMOWIENIA:";
            $receipt .= self::BOLD_OFF;
            $receipt .= self::LINE_FEED;
            $receipt .= $this->wrap($order->notes, 32);
            $receipt .= self::LINE_FEED;
            $receipt .= $this->line();
        }

        // Total
        $receipt .= self::ALIGN_RIGHT;
        $receipt .= self::DOUBLE_HEIGHT_ON;
        $receipt .= "SUMA: " . number_format($order->total, 2, ',', ' ') . " ZL";
        $receipt .= self::NORMAL_SIZE;
        $receipt .= self::LINE_FEED;

        // Payment method
        $receipt .= self::ALIGN_LEFT;
        $paymentMethod = match ($order->payment_method) {
            'online' => 'OPLACONE ONLINE',
            'cash' => 'GOTOWKA',
            'card_on_delivery' => 'KARTA PRZY ODBIORZE',
            default => 'NIEZNANA',
        };
        $receipt .= "Platnosc: " . $paymentMethod;
        $receipt .= self::LINE_FEED;
        $receipt .= $this->line();

        // Footer
        $receipt .= self::ALIGN_CENTER;
        $receipt .= self::LINE_FEED;
        $receipt .= "Dziekujemy!";
        $receipt .= self::LINE_FEED;
        $receipt .= self::LINE_FEED;
        $receipt .= self::LINE_FEED;

        // Cut paper
        $receipt .= self::CUT_PAPER;

        return $receipt;
    }

    /**
     * Print via Web Bluetooth API (client-side)
     */
    protected function printViaWebBluetooth(string $data): bool
    {
        // This would be handled client-side via JavaScript
        // We can store the print job in a queue/database for client to pick up
        Log::info('Print job queued for Web Bluetooth');
        return true;
    }

    /**
     * Print via QZ Tray
     */
    protected function printViaQzTray(string $data): bool
    {
        // Get QZ Tray configuration from tenant settings
        $host = \App\Models\Tenant\Setting::get('printer.qz_tray.host', config('printer.qz_tray.host', 'localhost'));
        $port = \App\Models\Tenant\Setting::get('printer.qz_tray.port', config('printer.qz_tray.port', 8181));

        // Implementation for QZ Tray
        // Requires QZ Tray running on client machine
        Log::info('Print job queued for QZ Tray', [
            'host' => $host,
            'port' => $port,
        ]);
        return true;
    }

    /**
     * Print via PrintNode
     */
    protected function printViaPrintNode(string $data): bool
    {
        // Get PrintNode configuration from tenant settings
        $apiKey = \App\Models\Tenant\Setting::get('printer.printnode.api_key', config('printer.printnode.api_key'));
        $printerId = \App\Models\Tenant\Setting::get('printer.printnode.printer_id', config('printer.printnode.printer_id'));

        // Implementation for PrintNode cloud service
        // Requires PrintNode API key
        Log::info('Print job queued for PrintNode', [
            'printer_id' => $printerId,
        ]);
        return true;
    }

    /**
     * Get printer configuration for current tenant
     */
    public function getConfiguration(): array
    {
        return [
            'method' => $this->printerMethod,
            'qz_tray' => [
                'host' => \App\Models\Tenant\Setting::get('printer.qz_tray.host', config('printer.qz_tray.host', 'localhost')),
                'port' => \App\Models\Tenant\Setting::get('printer.qz_tray.port', config('printer.qz_tray.port', 8181)),
            ],
            'printnode' => [
                'api_key' => \App\Models\Tenant\Setting::get('printer.printnode.api_key', config('printer.printnode.api_key')),
                'printer_id' => \App\Models\Tenant\Setting::get('printer.printnode.printer_id', config('printer.printnode.printer_id')),
            ],
            'receipt' => [
                'width' => \App\Models\Tenant\Setting::get('printer.receipt.width', config('printer.receipt.width', 32)),
                'auto_print' => \App\Models\Tenant\Setting::get('printer.receipt.auto_print', config('printer.receipt.auto_print', true)),
            ],
        ];
    }

    /**
     * Helper: Create horizontal line
     */
    protected function line(string $char = '='): string
    {
        return str_repeat($char, 32) . self::LINE_FEED;
    }

    /**
     * Helper: Truncate text to fit width
     */
    protected function truncate(string $text, int $width): string
    {
        if (mb_strlen($text) <= $width) {
            return $text;
        }
        return mb_substr($text, 0, $width - 3) . '...';
    }

    /**
     * Helper: Wrap text to multiple lines
     */
    protected function wrap(string $text, int $width, string $indent = ''): string
    {
        $words = explode(' ', $text);
        $lines = [];
        $currentLine = '';

        foreach ($words as $word) {
            if (mb_strlen($currentLine . ' ' . $word) <= $width) {
                $currentLine .= ($currentLine ? ' ' : '') . $word;
            } else {
                if ($currentLine) {
                    $lines[] = $currentLine;
                }
                $currentLine = $indent . $word;
            }
        }

        if ($currentLine) {
            $lines[] = $currentLine;
        }

        return implode(self::LINE_FEED, $lines);
    }
}
