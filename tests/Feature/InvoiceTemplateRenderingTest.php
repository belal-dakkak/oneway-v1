<?php

namespace Tests\Feature;

use App\Models\WebsiteOrder;
use App\Models\Order;
use App\Models\User;
use App\Services\InvoiceDataService;
use Illuminate\Support\Collection;
use Tests\TestCase;

class InvoiceTemplateRenderingTest extends TestCase
{
    public function test_syrian_invoice_uses_website_contacts_and_positions_seller_left_of_buyer(): void
    {
        $seller = new User([
            'name' => 'Syrian shop', 'country_id' => User::COUNTRY_SYRIA,
            'phone' => 'shop-phone', 'email' => 'shop@example.test', 'address' => 'Aleppo branch',
        ]);
        $order = new Order([
            'barcode' => 'SY-LAYOUT-1', 'curr_type' => 'USD', 'curr_rate' => 1,
            'type' => Order::TYPE_CASH, 'total_price' => 5, 'paid_price' => 5,
            'remain_price' => 0, 'first_name' => 'Buyer',
            'phone' => '+963 911 111 111', 'address' => 'Buyer street',
        ]);
        $order->id = 81;
        $order->created_at = now();
        $order->setRelation('seller', $seller);
        $order->setRelation('items', new Collection());

        $service = new InvoiceDataService();
        $identity = $service->identityForOrder($order, ['phone' => '+963 900 000 001', 'email' => 'syria@example.test']);
        $this->assertSame('+963 900 000 001', $identity['phone']);
        $this->assertSame('syria@example.test', $identity['email']);
        $this->assertSame('', $service->identityForOrder($order, [])['email']);

        $data = $service->forOrder($order);
        $data += ['invoiceIdentity' => $identity, 'invoiceCountryId' => User::COUNTRY_SYRIA, 'settings' => [], 'Currency' => 'USD', 'user_role' => 'shop'];
        $html = view('includes.invoice_template', $data)->render();
        $this->assertStringContainsString('dir="ltr"', $html);
        $this->assertLessThan(strpos($html, 'BILL TO'), strpos($html, 'Syrian shop'));
        $this->assertStringContainsString('syria@example.test', $html);
        $this->assertStringContainsString('Aleppo branch', $html);
        $this->assertStringContainsString('Buyer street', $html);
        $this->assertStringContainsString('+963 911 111 111', $html);
        $this->assertStringNotContainsString('shop@example.test', $html);
        $this->assertStringContainsString('DESCRIPTION / الوصف', $html);
        $this->assertStringContainsString('QTY / الكمية', $html);
        $this->assertStringStartsWith('%PDF-', app('dompdf.wrapper')->loadHTML($html)->output());
    }

    public function test_invoice_pdf_view_and_print_templates_tolerate_optional_data(): void
    {
        $order = new WebsiteOrder([
            'barcode' => 'SY-INVOICE-1',
            'country_id' => 4,
            'curr_type' => 'SYP',
            'curr_rate' => 13000,
            'display_currency' => 'USD',
            'display_rate' => 13000,
            'total_price_before_discount' => 260000,
            'discount' => 0,
            'total_price' => 260000,
            'paid_price' => 0,
            'remain_price' => 260000,
            'shipping_fee' => 0,
            'cod_fee' => 0,
            'payment_type' => 'cod',
        ]);
        $order->id = 99;
        $order->created_at = now();
        $order->updated_at = now();
        $order->setRelation('items', new Collection());

        $data = (new InvoiceDataService())->forOrder($order);
        $data['settings'] = [];
        $data['Currency'] = $data['currency'];
        $data['user_role'] = 'shop';

        foreach (['receipts.pdfReceipt', 'receipts.pdfReceiptShipper', 'includes.invoice_template', 'includes.printer'] as $view) {
            $html = view($view, $data)->render();
            $this->assertStringContainsString('SY-INVOICE-1', $html);
            $this->assertStringContainsString('SYP', $html);
            $this->assertStringNotContainsString('Ajman Industrial', $html);
            $this->assertStringNotContainsString('Sharjah City', $html);
        }
    }
}
