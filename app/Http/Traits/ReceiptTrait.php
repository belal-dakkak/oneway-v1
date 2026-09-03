<?php

namespace App\Http\Traits;

use App\Services\InvoiceDataService;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;
use niklasravnsborg\LaravelPdf\Facades\Pdf;

trait ReceiptTrait
{
    public function generatePDF($orderId, string $source = InvoiceDataService::SOURCE_ORDER)
    {
        $result = $this->getExportData($orderId, $source);
        $pdf = PDF::loadView('receipts.pdfReceipt', $result);
        $fileName = 'orders/pdf/' . $source . '/' . $orderId . '/' . generateRandomString() . '.pdf';

        return Storage::disk('public')->put($fileName, $pdf->output()) ? $fileName : false;
    }

    protected function getExportData($orderId, string $source = InvoiceDataService::SOURCE_ORDER): array
    {
        $service = app(InvoiceDataService::class);
        $order = $service->resolve($source, (int) $orderId);

        return $service->forOrder($order);
    }

    protected function getExportDataForModel(Model $order): array
    {
        return app(InvoiceDataService::class)->forOrder($order);
    }
}
