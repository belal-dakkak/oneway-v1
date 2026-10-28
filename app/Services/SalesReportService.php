<?php

namespace App\Services;

use App\Models\Order;
use App\Models\Refund;
use App\Models\User;
use App\Support\Country;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class SalesReportService
{
    private $clientDebtOrders;

    public function __construct(ClientDebtOrderScope $clientDebtOrders)
    {
        $this->clientDebtOrders = $clientDebtOrders;
    }

    public function orders(Request $request, bool $debts = false, ?array $with = null, bool $paginate = true): array
    {
        $with = $with ?: ['buyer', 'productItems', 'seller', 'shipper', 'items.product.productColor', 'items.refunds'];
        if (!in_array('items.refunds', $with, true)) {
            $with[] = 'items.refunds';
        }

        $query = $this->baseOrderQuery($request, $debts)->with($with);
        $field = (string) $request->input('field');
        $direction = $request->input('direction') === 'asc' ? 'asc' : 'desc';
        if (in_array($field, ['type', 'id', 'total_price', 'remain_price', 'paid_price'], true)) {
            $query->orderBy("orders.{$field}", $direction);
        } else {
            $query->orderByDesc('orders.id');
        }

        $result = $paginate ? $query->paginate(10)->withQueryString() : $query->get();
        $orders = $result instanceof Collection ? $result : $result->getCollection();
        $this->decorateOrders($orders);

        $report = $this->summary($request, $debts);
        if ($debts) {
            $report['debt_summary'] = $this->debtSummary($request);
        }

        return array_merge($report, [
            'rows' => $result,
            'orders' => $result,
        ]);
    }

    /**
     * Summarize every outstanding order matching the active filters. The
     * calculation intentionally runs before pagination and counts orders, not
     * product quantities.
     */
    public function debtSummary(Request $request): array
    {
        $source = $this->baseOrderQuery($request, true)
            ->join('users as debt_summary_sellers', 'debt_summary_sellers.id', '=', 'orders.seller_id');

        $currencyExpression = "CASE
            WHEN debt_summary_sellers.country_id = ?
                AND UPPER(COALESCE(orders.curr_type, 'USD')) IN ('AED', 'USD')
                THEN 'AED'
            ELSE UPPER(COALESCE(orders.curr_type, 'USD'))
        END";

        $source
            ->select([])
            ->selectRaw("{$currencyExpression} as currency_code", [Country::UAE])
            ->addSelect([
                'orders.id as order_id',
                'orders.total_price as invoice_total',
                'orders.remain_price as remaining_total',
            ]);

        // Aggregate the projected currency in an outer query. MySQL/MariaDB
        // with ONLY_FULL_GROUP_BY can reject a selected CASE expression even
        // when that expression is repeated verbatim in GROUP BY.
        $rows = DB::query()
            ->fromSub($source->toBase(), 'outstanding_debt_orders')
            ->select('currency_code')
            ->selectRaw('COUNT(order_id) as orders_count')
            ->selectRaw('COALESCE(SUM(invoice_total), 0) as invoice_total')
            ->selectRaw('COALESCE(SUM(remaining_total), 0) as remaining_total')
            ->groupBy('currency_code')
            ->orderBy('currency_code')
            ->get();

        $totals = [];
        $ordersCount = 0;
        foreach ($rows as $row) {
            $currency = strtoupper((string) ($row->currency_code ?: 'USD'));
            $decimals = $currency === 'SYP' ? 0 : 2;
            $count = (int) $row->orders_count;
            $ordersCount += $count;
            $totals[$currency] = [
                'invoice_total' => round((float) $row->invoice_total, $decimals),
                'remaining_total' => round((float) $row->remaining_total, $decimals),
                'orders_count' => $count,
            ];
        }

        return [
            'orders_count' => $ordersCount,
            'totals_by_currency' => $totals,
        ];
    }

    public function monthly(Request $request): array
    {
        $report = $this->summary($request, false, true);
        $shops = User::query()
            ->whereIn('role_id', [User::ROLE_SHOP, User::ROLE_WAREHOUSE])
            ->where('country_id', auth()->user()->country_id)
            ->get();

        $report['orders'] = $report['rows'];
        $report['shops'] = transformDataForVue($shops);
        $report['filters'] = $request->only(['search', 'field', 'direction', 'shop', 'date', 'start_date', 'end_date']);
        $report['currency'] = count($report['totals_by_currency']) === 1
            ? array_key_first($report['totals_by_currency']) : null;
        return $report;
    }

    public function summary(Request $request, bool $debts = false, bool $useMonthlyDefault = false): array
    {
        [$start, $end] = $this->period($request, $useMonthlyDefault);
        $ordersQuery = $this->baseOrderQuery($request, $debts);
        if ($start) {
            $ordersQuery->whereBetween('orders.created_at', [$start, $end]);
        }
        $orders = $ordersQuery->with(['seller', 'items.product', 'items.refunds'])->get();

        $refundsQuery = Refund::query()->with(['orderItem.order.seller', 'orderItem.product']);
        $this->constrainRefunds($refundsQuery, $request, $debts);
        if ($start) {
            $refundsQuery->whereBetween('refunds.created_at', [$start, $end]);
        }
        $refunds = $refundsQuery->get();

        $rows = [];
        $totals = [];
        $positiveProfits = [];
        foreach ($orders as $order) {
            $currency = strtoupper((string) ($order->curr_type ?: 'USD'));
            if ($debts) {
                $currency = $this->clientDebtOrders->effectiveOrderCurrency(
                    (int) optional($order->seller)->country_id,
                    $currency
                );
            }
            $date = $this->localDate($order->created_at, (int) optional($order->seller)->country_id);
            $key = $date . '|' . $order->seller_id . '|' . $currency;
            $row = &$this->row($rows, $key, $date, (int) $order->seller_id, optional($order->seller)->name, $currency);
            $profitMetrics = $this->orderProfitMetrics($order);
            $metrics = $profitMetrics['sale'];
            foreach ($metrics as $field => $value) {
                $row[$field] += $value;
            }
            if ($profitMetrics['net_profit'] > 0) {
                $positiveProfits[$currency] = ($positiveProfits[$currency] ?? 0)
                    + $profitMetrics['net_profit'];
            }
            unset($row);
        }

        foreach ($refunds as $refund) {
            $order = optional($refund->orderItem)->order;
            if (!$order || $this->isSignedReturn($order, $refund->orderItem)) {
                continue;
            }
            $currency = strtoupper((string) ($refund->currency_code ?: $order->curr_type ?: 'USD'));
            if ($debts) {
                $currency = $this->clientDebtOrders->effectiveOrderCurrency(
                    (int) optional($order->seller)->country_id,
                    $currency
                );
            }
            $date = $this->localDate($refund->created_at, (int) optional($order->seller)->country_id);
            $key = $date . '|' . $order->seller_id . '|' . $currency;
            $row = &$this->row($rows, $key, $date, (int) $order->seller_id, optional($order->seller)->name, $currency);
            $row['refund_total'] += $this->refundTotal($refund, $order);
            $row['refund_qty'] += abs((int) $refund->qty);
            $row['refund_net'] += $this->refundNet($refund, $order);
            $row['refund_tax'] += $this->refundTax($refund);
            $row['refund_profit'] += $this->refundTotal($refund, $order) - $this->refundCost($refund, $order);
            unset($row);
        }

        foreach ($rows as &$row) {
            $row['net_sales'] = $row['gross_sales'] - $row['refund_total'];
            $row['net_qty'] = $row['gross_qty'] - $row['refund_qty'];
            $row['net_amount'] = $row['gross_net'] - $row['refund_net'];
            $row['tax_amount'] = $row['gross_tax'] - $row['refund_tax'];
            $row['net_profit'] = $row['gross_profit'] - $row['refund_profit'];

            // Backwards-compatible names consumed by the current Vue screens/PDFs.
            $row['count'] = $row['net_qty'];
            $row['price_without_tax'] = $row['net_amount'];
            $row['tax_value'] = $row['tax_amount'];
            $row['total_price'] = $row['net_sales'];
            $row['total_refund'] = $row['refund_total'];

            $currency = $row['currency'];
            if (!isset($totals[$currency])) {
                $totals[$currency] = $this->emptyMetrics();
            }
            foreach (array_keys($this->emptyMetrics()) as $field) {
                $totals[$currency][$field] += $row[$field];
            }
            unset($row);
        }

        foreach ($totals as $currency => &$total) {
            $decimals = $currency === 'SYP' ? 0 : 2;
            foreach ($total as $field => $value) {
                $total[$field] = in_array($field, ['gross_qty', 'refund_qty', 'net_qty'], true)
                    ? (int) $value : round($value, $decimals);
            }
            $total['total'] = $total['net_sales'];
            $total['net'] = $total['net_amount'];
            $total['tax'] = $total['tax_amount'];
            $total['refunds'] = $total['refund_total'];
            $total['count'] = $total['net_qty'];
        }
        unset($total);

        $profitsByCurrency = [];
        foreach (array_keys($totals) as $currency) {
            $profitsByCurrency[$currency] = round(
                max(0, (float) ($positiveProfits[$currency] ?? 0)),
                $currency === 'SYP' ? 0 : 2
            );
        }

        usort($rows, fn ($a, $b) => [$b['date'], $b['shop_name'], $b['currency']] <=> [$a['date'], $a['shop_name'], $a['currency']]);
        $single = count($totals) === 1 ? reset($totals) : null;
        $singlePositiveProfit = count($profitsByCurrency) === 1 ? reset($profitsByCurrency) : 0;

        return [
            'rows' => array_values($rows),
            'totals_by_currency' => $totals,
            'total' => $single['net_sales'] ?? 0,
            'count' => array_sum(array_column($totals, 'net_qty')),
            'total_price_without_tax' => $single['net_amount'] ?? 0,
            'total_tax_value' => $single['tax_amount'] ?? 0,
            'totalRefunds' => $single['refund_total'] ?? 0,
            'profit' => $singlePositiveProfit,
            'profits_by_currency' => $profitsByCurrency,
        ];
    }

    private function baseOrderQuery(Request $request, bool $debts): Builder
    {
        $country = (int) auth()->user()->country_id;
        $query = Order::query()->select('orders.*')
            ->whereHas('seller', fn ($seller) => $seller->where('country_id', $country))
            ->when(auth()->user()->role_id !== User::ROLE_ADMIN, fn ($q) => $q->where('seller_id', auth()->id()))
            ->when($debts, fn ($q) => $this->clientDebtOrders->applyAuthoritativeOutstanding($q, 'orders'))
            ->when($request->input('type'), fn ($q, $type) => $q->where('type', $type))
            ->when($request->input('order_type'), fn ($q, $type) => $q->where('order_type', $type))
            ->when($request->input('search'), fn ($q, $search) => $q->where('barcode', 'like', "%{$search}%"))
            ->when($request->input('buyer_id', $request->input('buyer')), fn ($q, $buyer) => $q->where('buyer_id', $buyer))
            ->when($request->input('seller_id', $request->input('shop')), fn ($q, $seller) => $q->where('seller_id', $seller))
            ->when($request->input('shipper_id'), fn ($q, $shipper) => $q->where('shipper_id', $shipper));

        if ($buyerName = $request->input('buyerName')) {
            $query->whereHas('buyer', fn ($buyer) => $buyer->where('name', 'like', "%{$buyerName}%"));
        }
        [$start, $end] = $this->period($request, false);
        if ($start) {
            $query->whereBetween('orders.created_at', [$start, $end]);
        }
        return $query;
    }

    private function constrainRefunds(Builder $query, Request $request, bool $debts): void
    {
        $country = (int) auth()->user()->country_id;
        $query->whereHas('orderItem.order', function ($order) use ($request, $country, $debts) {
            $order->whereHas('seller', fn ($seller) => $seller->where('country_id', $country))
                ->when(auth()->user()->role_id !== User::ROLE_ADMIN, fn ($q) => $q->where('seller_id', auth()->id()))
                ->when($debts, fn ($q) => $this->clientDebtOrders->applyAuthoritativeOutstanding($q, 'orders'))
                ->when($request->input('type'), fn ($q, $type) => $q->where('type', $type))
                ->when($request->input('order_type'), fn ($q, $type) => $q->where('order_type', $type))
                ->when($request->input('buyer_id', $request->input('buyer')), fn ($q, $buyer) => $q->where('buyer_id', $buyer))
                ->when($request->input('seller_id', $request->input('shop')), fn ($q, $seller) => $q->where('seller_id', $seller))
                ->when($request->input('shipper_id'), fn ($q, $shipper) => $q->where('shipper_id', $shipper));
            if ($search = $request->input('search')) {
                $order->where('barcode', 'like', "%{$search}%");
            }
            if ($buyerName = $request->input('buyerName')) {
                $order->whereHas('buyer', fn ($buyer) => $buyer->where('name', 'like', "%{$buyerName}%"));
            }
        });
    }

    private function decorateOrders(Collection $orders): void
    {
        foreach ($orders as $order) {
            $metrics = $this->orderProfitMetrics($order);
            $sale = $metrics['sale'];
            $order->setAttribute('currency_code', strtoupper((string) ($order->curr_type ?: 'USD')));
            $order->setAttribute('gross_total', $sale['gross_sales']);
            $order->setAttribute('refund_total', $metrics['refund_total']);
            $order->setAttribute('net_total', $sale['gross_sales'] - $metrics['refund_total']);
            $order->setAttribute('gross_qty', $sale['gross_qty']);
            $order->setAttribute('returned_qty', $metrics['refund_qty']);
            $order->setAttribute('net_qty', $sale['gross_qty'] - $metrics['refund_qty']);
            $order->setAttribute('gross_profit', $sale['gross_profit']);
            $order->setAttribute('refund_profit', $metrics['refund_profit']);
            $order->setAttribute('net_profit', $metrics['net_profit']);
        }
    }

    private function orderProfitMetrics(Order $order): array
    {
        $sale = $this->saleMetrics($order);
        $refundTotal = $refundQty = $refundProfit = 0;

        foreach ($order->items as $item) {
            foreach ($item->refunds as $refund) {
                if ($this->isSignedReturn($order, $item)) {
                    continue;
                }
                $refundTotal += $this->refundTotal($refund, $order);
                $refundQty += abs((int) $refund->qty);
                $refundProfit += $this->refundTotal($refund, $order) - $this->refundCost($refund, $order);
            }
        }

        return [
            'sale' => $sale,
            'refund_total' => $refundTotal,
            'refund_qty' => $refundQty,
            'refund_profit' => $refundProfit,
            'net_profit' => $sale['gross_profit'] - $refundProfit,
        ];
    }

    private function saleMetrics(Order $order): array
    {
        $rate = (float) ($order->curr_rate ?: 1);
        $grossQty = $grossNet = $grossTax = $cost = 0;
        foreach ($order->items as $item) {
            $qty = (int) ($item->sold_qty ?? ((int) $item->qty + (int) $item->refunds()->sum('qty')));
            $signedQty = $this->isSignedReturn($order, $item) ? -abs($qty) : $qty;
            $grossQty += $signedQty;
            $grossNet += abs((float) ($item->price_without_tax_paid ?: $item->item_price_paid ?: ((float) $item->item_price * $rate))) * $signedQty;
            $grossTax += abs((float) ($item->tax_value_paid ?: 0)) * $signedQty;
            $cost += abs((float) ($item->unit_cost ?? optional($item->product)->wholesale_price ?? 0) * $rate) * $signedQty;
        }
        $grossSales = (float) $order->total_price;
        // Order-level values are the immutable invoice snapshot and include the
        // exact discount/tax rounding used at sale time. Item sums are retained
        // only as a fallback for historical rows that predate those snapshots.
        $snapshotNet = (float) ($order->price_without_tax ?? 0);
        $snapshotTax = (float) ($order->tax_value ?? 0);
        if ($this->isSignedReturn($order)) {
            $snapshotNet = -abs($snapshotNet);
            $snapshotTax = -abs($snapshotTax);
        }
        if ($snapshotNet != 0.0 || $snapshotTax != 0.0 || $grossSales == 0.0) {
            $grossNet = $snapshotNet;
            $grossTax = $snapshotTax;
        }
        return [
            'gross_sales' => $grossSales,
            'gross_qty' => $grossQty,
            'gross_net' => $grossNet,
            'gross_tax' => $grossTax,
            'gross_profit' => $grossSales - $cost,
        ];
    }

    private function refundTotal(Refund $refund, Order $order): float
    {
        return abs((float) ($refund->total_price_paid ?: ((float) $refund->total_price * (float) ($order->curr_rate ?: 1))));
    }

    private function refundNet(Refund $refund, Order $order): float
    {
        if ($refund->net_amount !== null) {
            return abs((float) $refund->net_amount);
        }
        $item = $refund->orderItem;
        return abs((float) ($item->price_without_tax_paid ?: $item->item_price_paid ?: 0) * (int) $refund->qty);
    }

    private function refundTax(Refund $refund): float
    {
        return abs($refund->tax_amount !== null
            ? (float) $refund->tax_amount
            : (float) optional($refund->orderItem)->tax_value_paid * (int) $refund->qty);
    }

    private function refundCost(Refund $refund, Order $order): float
    {
        if ($refund->cost_amount !== null) {
            return abs((float) $refund->cost_amount);
        }
        $item = $refund->orderItem;
        return abs((float) ($item->unit_cost ?? optional($item->product)->wholesale_price ?? 0)
            * (float) ($order->curr_rate ?: 1) * (int) $refund->qty);
    }

    private function isSignedReturn(Order $order, $item = null): bool
    {
        if ((float) $order->total_price < 0) {
            return true;
        }

        if (!$item) {
            return false;
        }

        return (int) $item->qty < 0
            || ($item->sold_qty !== null && (int) $item->sold_qty < 0)
            || (float) $item->item_price < 0
            || (float) $item->total_price < 0
            || (float) $item->item_price_paid < 0
            || (float) $item->total_price_paid < 0;
    }

    private function &row(array &$rows, string $key, string $date, int $shopId, ?string $shopName, string $currency): array
    {
        if (!isset($rows[$key])) {
            $rows[$key] = array_merge([
                'shop_id' => $shopId,
                'shop_name' => $shopName ?: '',
                'date' => $date,
                'currency' => $currency,
                'currency_code' => $currency,
            ], $this->emptyMetrics());
        }
        return $rows[$key];
    }

    private function emptyMetrics(): array
    {
        return [
            'gross_sales' => 0, 'refund_total' => 0, 'net_sales' => 0,
            'gross_qty' => 0, 'refund_qty' => 0, 'net_qty' => 0,
            'gross_net' => 0, 'refund_net' => 0, 'net_amount' => 0,
            'gross_tax' => 0, 'refund_tax' => 0, 'tax_amount' => 0,
            'gross_profit' => 0, 'refund_profit' => 0, 'net_profit' => 0,
        ];
    }

    private function period(Request $request, bool $monthlyDefault): array
    {
        $countryId = (int) auth()->user()->country_id;
        $timezone = Country::timezone($countryId);
        $storageTimezone = (string) config('app.timezone', 'UTC');
        if ($request->filled('date')) {
            $local = Carbon::parse($request->input('date'), $timezone);
            return [
                $local->copy()->startOfDay()->setTimezone($storageTimezone),
                $local->copy()->endOfDay()->setTimezone($storageTimezone),
            ];
        }
        if ($request->filled('start_date') || $request->filled('end_date')) {
            $start = Carbon::parse($request->input('start_date') ?: $request->input('end_date'), $timezone)
                ->startOfDay()->setTimezone($storageTimezone);
            $end = Carbon::parse($request->input('end_date') ?: $request->input('start_date'), $timezone)
                ->endOfDay()->setTimezone($storageTimezone);
            return [$start, $end];
        }
        if ($monthlyDefault) {
            $now = Carbon::now($timezone);
            return [
                $now->copy()->startOfMonth()->setTimezone($storageTimezone),
                $now->copy()->endOfDay()->setTimezone($storageTimezone),
            ];
        }
        return [null, null];
    }

    private function localDate($date, int $countryId): string
    {
        return Carbon::parse($date)->timezone(Country::timezone($countryId ?: Country::UAE))->format('Y-m-d');
    }
}
