<?php

namespace App\Http\Controllers\Tenant\Manager;

use App\Http\Controllers\Controller;
use App\Models\Tenant\Order;
use App\Models\Tenant\OrderItem;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use Inertia\Inertia;
use Inertia\Response;

class ReportController extends Controller
{
    /**
     * Display reports dashboard
     */
    public function index(Request $request): Response
    {
        $period = $request->get('period', 'week'); // day, week, month, year, custom
        $startDate = $request->get('start_date');
        $endDate = $request->get('end_date');

        [$start, $end] = $this->getDateRange($period, $startDate, $endDate);

        // Sales summary
        $salesData = $this->getSalesSummary($start, $end);

        // Revenue chart data
        $revenueChartData = $this->getRevenueChartData($start, $end, $period);

        // Top products
        $topProducts = $this->getTopProducts($start, $end, 10);

        // Order statistics
        $orderStats = $this->getOrderStatistics($start, $end);

        // Peak hours
        $peakHours = $this->getPeakHours($start, $end);

        // Payment methods breakdown
        $paymentMethods = $this->getPaymentMethodsBreakdown($start, $end);

        return Inertia::render('Tenant/Manager/Reports/Index', [
            'period' => $period,
            'startDate' => $start->format('Y-m-d'),
            'endDate' => $end->format('Y-m-d'),
            'summary' => $salesData,
            'salesData' => $salesData,
            'revenueChart' => $revenueChartData,
            'topProducts' => $topProducts,
            'orderStats' => $orderStats,
            'peakHours' => $peakHours,
            'paymentMethods' => $paymentMethods,
        ]);
    }

    /**
     * Get date range based on period
     */
    protected function getDateRange(string $period, ?string $startDate, ?string $endDate): array
    {
        if ($period === 'custom' && $startDate && $endDate) {
            return [
                Carbon::parse($startDate)->startOfDay(),
                Carbon::parse($endDate)->endOfDay(),
            ];
        }

        $end = Carbon::now()->endOfDay();

        $start = match ($period) {
            'day' => Carbon::now()->startOfDay(),
            'week' => Carbon::now()->subDays(7)->startOfDay(),
            'month' => Carbon::now()->subDays(30)->startOfDay(),
            'year' => Carbon::now()->subYear()->startOfDay(),
            default => Carbon::now()->subDays(7)->startOfDay(),
        };

        return [$start, $end];
    }

    /**
     * Get sales summary
     */
    protected function getSalesSummary(Carbon $start, Carbon $end): array
    {
        $orders = Order::whereBetween('created_at', [$start, $end])
            ->where('payment_status', 'paid')
            ->get();

        $totalRevenue = $orders->sum('total');
        $totalOrders = $orders->count();
        $averageOrderValue = $totalOrders > 0 ? $totalRevenue / $totalOrders : 0;
        $totalDiscount = $orders->sum('discount');

        return [
            'total_revenue' => $totalRevenue,
            'total_orders' => $totalOrders,
            'average_order_value' => $averageOrderValue,
            'total_discount' => $totalDiscount,
        ];
    }

    /**
     * Get revenue chart data
     */
    protected function getRevenueChartData(Carbon $start, Carbon $end, string $period): array
    {
        $isSqlite = DB::connection()->getDriverName() === 'sqlite';

        $dateFormat = match ($period) {
            'day' => '%Y-%m-%d %H:00:00',
            'week', 'month' => '%Y-%m-%d',
            'year' => '%Y-%m',
            default => '%Y-%m-%d',
        };

        if ($isSqlite) {
            $groupBy = match ($period) {
                'day' => DB::raw("strftime('%H', created_at)"),
                'year' => DB::raw("strftime('%Y-%m', created_at)"),
                default => DB::raw("DATE(created_at)"),
            };
            $dateExpr = DB::raw("strftime('$dateFormat', created_at) as date");
        } else {
            $groupBy = match ($period) {
                'day' => 'HOUR(created_at)',
                'week', 'month' => 'DATE(created_at)',
                'year' => "DATE_FORMAT(created_at, '%Y-%m')",
                default => 'DATE(created_at)',
            };
            $dateExpr = DB::raw("DATE_FORMAT(created_at, '$dateFormat') as date");
        }

        $data = Order::whereBetween('created_at', [$start, $end])
            ->where('payment_status', 'paid')
            ->select(
                $dateExpr,
                DB::raw('SUM(total) as revenue'),
                DB::raw('COUNT(*) as orders')
            )
            ->groupBy('date')
            ->orderBy('date')
            ->get();

        $labels = [];
        $revenue = [];
        $orderCount = [];

        foreach ($data as $item) {
            $labels[] = $this->formatChartLabel($item->date, $period);
            $revenue[] = (float) $item->revenue;
            $orderCount[] = $item->orders;
        }

        return [
            'labels' => $labels,
            'revenue' => $revenue,
            'orders' => $orderCount,
        ];
    }

    /**
     * Format chart label based on period
     */
    protected function formatChartLabel(string $date, string $period): string
    {
        $carbon = Carbon::parse($date);

        return match ($period) {
            'day' => $carbon->format('H:00'),
            'week', 'month' => $carbon->format('d.m'),
            'year' => $carbon->format('M Y'),
            default => $carbon->format('d.m'),
        };
    }

    /**
     * Get top products
     */
    protected function getTopProducts(Carbon $start, Carbon $end, int $limit = 10): array
    {
        return OrderItem::join('orders', 'order_items.order_id', '=', 'orders.id')
            ->whereBetween('orders.created_at', [$start, $end])
            ->where('orders.payment_status', 'paid')
            ->select(
                'order_items.name',
                'order_items.variant_name',
                DB::raw('SUM(order_items.quantity) as total_quantity'),
                DB::raw('SUM(order_items.quantity * order_items.price) as total_revenue')
            )
            ->groupBy('order_items.name', 'order_items.variant_name')
            ->orderByDesc('total_quantity')
            ->limit($limit)
            ->get()
            ->map(function ($item) {
                return [
                    'name' => $item->name,
                    'variant_name' => $item->variant_name,
                    'quantity' => $item->total_quantity,
                    'revenue' => (float) $item->total_revenue,
                ];
            })
            ->toArray();
    }

    /**
     * Get order statistics
     */
    protected function getOrderStatistics(Carbon $start, Carbon $end): array
    {
        $orders = Order::whereBetween('created_at', [$start, $end]);

        $byType = $orders->clone()
            ->select('type', DB::raw('COUNT(*) as count'))
            ->groupBy('type')
            ->get()
            ->pluck('count', 'type')
            ->toArray();

        $byStatus = $orders->clone()
            ->select('status', DB::raw('COUNT(*) as count'))
            ->groupBy('status')
            ->get()
            ->pluck('count', 'status')
            ->toArray();

        return [
            'by_type' => $byType,
            'by_status' => $byStatus,
        ];
    }

    /**
     * Get peak hours
     */
    protected function getPeakHours(Carbon $start, Carbon $end): array
    {
        $isSqlite = DB::connection()->getDriverName() === 'sqlite';
        $hourExpr = $isSqlite
            ? DB::raw("CAST(strftime('%H', created_at) AS INTEGER) as hour")
            : DB::raw('HOUR(created_at) as hour');

        $data = Order::whereBetween('created_at', [$start, $end])
            ->select(
                $hourExpr,
                DB::raw('COUNT(*) as orders')
            )
            ->groupBy('hour')
            ->orderBy('hour')
            ->get();

        $hours = [];
        $orders = [];

        foreach ($data as $item) {
            $hours[] = sprintf('%02d:00', $item->hour);
            $orders[] = $item->orders;
        }

        return [
            'hours' => $hours,
            'orders' => $orders,
        ];
    }

    /**
     * Get payment methods breakdown
     */
    protected function getPaymentMethodsBreakdown(Carbon $start, Carbon $end): array
    {
        return Order::whereBetween('created_at', [$start, $end])
            ->where('payment_status', 'paid')
            ->select(
                'payment_method',
                DB::raw('COUNT(*) as count'),
                DB::raw('SUM(total) as revenue')
            )
            ->groupBy('payment_method')
            ->get()
            ->map(function ($item) {
                return [
                    'method' => $item->payment_method,
                    'count' => $item->count,
                    'revenue' => (float) $item->revenue,
                ];
            })
            ->toArray();
    }

    /**
     * Export report to CSV
     */
    public function exportCsv(Request $request)
    {
        $period = $request->get('period', 'week');
        $startDate = $request->get('start_date');
        $endDate = $request->get('end_date');

        [$start, $end] = $this->getDateRange($period, $startDate, $endDate);

        $orders = Order::with(['items', 'discountCode'])
            ->whereBetween('created_at', [$start, $end])
            ->where('payment_status', 'paid')
            ->orderBy('created_at', 'desc')
            ->get();

        $filename = 'raport_' . $start->format('Y-m-d') . '_' . $end->format('Y-m-d') . '.csv';

        $headers = [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => "attachment; filename=\"$filename\"",
        ];

        $callback = function () use ($orders) {
            $file = fopen('php://output', 'w');

            // BOM for Excel UTF-8 support
            fprintf($file, chr(0xEF) . chr(0xBB) . chr(0xBF));

            // Header
            fputcsv($file, [
                'Numer zamówienia',
                'Data',
                'Typ',
                'Klient',
                'Telefon',
                'Suma produktów',
                'Dostawa',
                'Rabat',
                'Razem',
                'Metoda płatności',
                'Status',
            ], ';');

            // Data
            foreach ($orders as $order) {
                fputcsv($file, [
                    $order->order_number,
                    $order->created_at->format('Y-m-d H:i:s'),
                    $order->type,
                    $order->customer_name,
                    $order->customer_phone,
                    number_format($order->subtotal, 2, ',', ''),
                    number_format($order->delivery_fee, 2, ',', ''),
                    number_format($order->discount, 2, ',', ''),
                    number_format($order->total, 2, ',', ''),
                    $order->payment_method,
                    $order->status,
                ], ';');
            }

            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }
}
