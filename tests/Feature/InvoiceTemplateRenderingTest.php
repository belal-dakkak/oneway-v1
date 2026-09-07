<?php

namespace Tests\Feature;

use App\Models\WebsiteOrder;
use App\Services\InvoiceDataService;
use Illuminate\Support\Collection;
use Tests\TestCase;

class InvoiceTemplateRenderingTest extends TestCase
{
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
        }
    }
}
