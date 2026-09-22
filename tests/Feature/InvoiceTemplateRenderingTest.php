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
    public function test_a4_invoice_uses_the_fixed_branch_directory_and_keeps_seller_identity_before_buyer(): void
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
        $this->assertBranchDirectory($html);
        $this->assertStringContainsString('Buyer street', $html);
        $this->assertStringContainsString('+963 911 111 111', $html);
        $this->assertStringNotContainsString('shop@example.test', $html);
        $this->assertStringNotContainsString('syria@example.test', $html);
        $this->assertStringContainsString('DESCRIPTION / الوصف', $html);
        $this->assertStringContainsString('QTY / الكمية', $html);
        $this->assertStringContainsString('سياسة الاستبدال / Exchange Policy', $html);
        $this->assertStringNotContainsString('ط§', $html);
        $this->assertStringNotContainsString('ظ„', $html);
        $this->assertStringNotContainsString("\u{FFFD}", $html);
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
            if ($view === 'includes.invoice_template') {
                $this->assertBranchDirectory($html);
            } else {
                $this->assertStringNotContainsString('Ajman Industrial', $html);
            }
            $this->assertStringNotContainsString('Sharjah City', $html);
        }
    }

    private function assertBranchDirectory(string $html): void
    {
        foreach ([
            'United Arab Emirates',
            'Branch 1 :</span> Ajman Industrial 2 Beirut Street',
            '+971 545 516 995',
            '+971 564 533 655',
            'Syria (Aleppo)',
            'Branch 2 :</span> Aleppo',
            '+963 947 900 555',
            '+963 958 900 555',
            'Lebanon, Beirut',
            'Branch 3 :</span> Lebanon Beirut',
            '+961 81 730 725',
            'Türkiye',
            'Branch 4 :</span> Türkiye Istanbul Merter',
            '+905 004 001 621',
            'href="http://www.oneway.fashion"',
            'href="mailto:theoneway.fashion@gmail.com"',
        ] as $expected) {
            $this->assertStringContainsString($expected, $html);
        }
    }
}
