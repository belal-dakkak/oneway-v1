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

class SalesReportService
{
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
        return array_merge($report, ['orders' => $result]);
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
        $orders = $ordersQuery->with(['seller', 'items'])->get();

        $refundsQuery = Refund::query()->with(['orderItem.order.seller', 'orderItem.product']);
        $this->constrainRefunds($refundsQuery, $request, $debts);
        if ($start) {
            $refundsQuery->whereBetween('refunds.created_at', [$start, $end]);
        }
        $refunds = $refundsQuery->get();

        $rows = [];
        $totals = [];
        foreach ($orders as $order) {
            $currency = strtoupper((string) ($order->curr_type ?: 'USD'));
            $date = $this->localDate($order->created_at, (int) optional($order->seller)->country_id);
            $key = $date . '|' . $order->seller_id . '|' . $currency;
            $row = &$this->row($rows, $key, $date, (int) $order->seller_id, optional($order->seller)->name, $currency);
            $metrics = $this->saleMetrics($order);
            foreach ($metrics as $field => $value) {
                $row[$field] += $value;
            }
            unset($row);
        }

        foreach ($refunds as $refund) {
            $order = optional($refund->orderItem)->order;
            if (!$order) {
                continue;
            }
            $currency = strtoupper((string) ($refund->currency_code ?: $order->curr_type ?: 'USD'));
            $date = $this->localDate($refund->created_at, (int) optional($order->seller)->country_id);
            $key = $date . '|' . $order->seller_id . '|' . $currency;
            $row = &$this->row($rows, $key, $date, (int) $order->seller_id, optional($order->seller)->name, $currency);
            $row['refund_total'] += $this->refundTotal($refund, $order);
            $row['refund_qty'] += (int) $refund->qty;
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

        usort($rows, fn ($a, $b) => [$b['date'], $b['shop_name'], $b['currency']] <=> [$a['date'], $a['shop_name'], $a['currency']]);
        $single = count($totals) === 1 ? reset($totals) : null;

        return [
            'rows' => array_values($rows),
            'totals_by_currency' => $totals,
            'total' => $single['net_sales'] ?? 0,
            'count' => array_sum(array_column($totals, 'net_qty')),
            'total_price_without_tax' => $single['net_amount'] ?? 0,
            'total_tax_value' => $single['tax_amount'] ?? 0,
            'totalRefunds' => $single['refund_total'] ?? 0,
            'profit' => $single['net_profit'] ?? 0,
            'profits_by_currency' => collect($totals)->map(fn ($total) => $total['net_profit'])->all(),
        ];
    }

    private function baseOrderQuery(Request $request, bool $debts): Builder
    {
        $country = (int) auth()->user()->country_id;
        $query = Order::query()->select('orders.*')
            ->whereHas('seller', fn ($seller) => $seller->where('country_id', $country))
            ->when(auth()->user()->role_id !== User::ROLE_ADMIN, fn ($q) => $q->where('seller_id', auth()->id()))
            ->when($debts, fn ($q) => $q->where('remain_price', '>', 0))
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
                ->when($debts, fn ($q) => $q->where('remain_price', '>', 0))
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
            $sale = $this->saleMetrics($order);
            $refundTotal = $refundQty = $refundProfit = 0;
            foreach ($order->items as $item) {
                foreach ($item->refunds as $refund) {
                    $refundTotal += $this->refundTotal($refund, $order);
                    $refundQty += (int) $refund->qty;
                    $refundProfit += $this->refundTotal($refund, $order) - $this->refundCost($refund, $order);
                }
            }
            $order->setAttribute('currency_code', strtoupper((string) ($order->curr_type ?: 'USD')));
            $order->setAttribute('gross_total', $sale['gross_sales']);
            $order->setAttribute('refund_total', $refundTotal);
            $order->setAttribute('net_total', $sale['gross_sales'] - $refundTotal);
            $order->setAttribute('gross_qty', $sale['gross_qty']);
            $order->setAttribute('returned_qty', $refundQty);
            $order->setAttribute('net_qty', $sale['gross_qty'] - $refundQty);
            $order->setAttribute('gross_profit', $sale['gross_profit']);
            $order->setAttribute('refund_profit', $refundProfit);
            $order->setAttribute('net_profit', $sale['gross_profit'] - $refundProfit);
        }
    }

    private function saleMetrics(Order $order): array
    {
        $rate = (float) ($order->curr_rate ?: 1);
        $grossQty = $grossNet = $grossTax = $cost = 0;
        foreach ($order->items as $item) {
            $qty = (int) ($item->sold_qty ?? ((int) $item->qty + (int) $item->refunds()->sum('qty')));
            $grossQty += $qty;
            $grossNet += (float) ($item->price_without_tax_paid ?: $item->item_price_paid ?: ((float) $item->item_price * $rate)) * $qty;
            $grossTax += (float) ($item->tax_value_paid ?: 0) * $qty;
            $cost += (float) ($item->unit_cost ?? optional($item->product)->wholesale_price ?? 0) * $rate * $qty;
        }
        $grossSales = (float) $order->total_price;
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
        return (float) ($refund->total_price_paid ?: ((float) $refund->total_price * (float) ($order->curr_rate ?: 1)));
    }

    private function refundNet(Refund $refund, Order $order): float
    {
        if ($refund->net_amount !== null) {
            return (float) $refund->net_amount;
        }
        $item = $refund->orderItem;
        return (float) ($item->price_without_tax_paid ?: $item->item_price_paid ?: 0) * (int) $refund->qty;
    }

    private function refundTax(Refund $refund): float
    {
        return $refund->tax_amount !== null
            ? (float) $refund->tax_amount
            : (float) optional($refund->orderItem)->tax_value_paid * (int) $refund->qty;
    }

    private function refundCost(Refund $refund, Order $order): float
    {
        if ($refund->cost_amount !== null) {
            return (float) $refund->cost_amount;
        }
        $item = $refund->orderItem;
        return (float) ($item->unit_cost ?? optional($item->product)->wholesale_price ?? 0)
            * (float) ($order->curr_rate ?: 1) * (int) $refund->qty;
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
        if ($request->filled('date')) {
            $local = Carbon::parse($request->input('date'), $timezone);
            return [$local->copy()->startOfDay()->utc(), $local->copy()->endOfDay()->utc()];
        }
        if ($request->filled('start_date') || $request->filled('end_date')) {
            $start = Carbon::parse($request->input('start_date') ?: $request->input('end_date'), $timezone)->startOfDay()->utc();
            $end = Carbon::parse($request->input('end_date') ?: $request->input('start_date'), $timezone)->endOfDay()->utc();
            return [$start, $end];
        }
        if ($monthlyDefault) {
            $now = Carbon::now($timezone);
            return [$now->copy()->startOfMonth()->utc(), $now->copy()->endOfDay()->utc()];
        }
        return [null, null];
    }

    private function localDate($date, int $countryId): string
    {
        return Carbon::parse($date)->timezone(Country::timezone($countryId ?: Country::UAE))->format('Y-m-d');
    }
}
