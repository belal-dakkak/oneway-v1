<?php

namespace App\Services;

use DateTimeInterface;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

class UserProductModelGroupingService
{
    public const MODELS_PER_PAGE = 10;

    /**
     * Return the business model code encoded in a color barcode.
     *
     * Barcodes shorter than five characters cannot safely identify a model,
     * so they receive a color-specific fallback key.
     */
    public function modelCode(?string $barcode, $fallbackColorId): string
    {
        $normalized = $this->normalizeBarcode($barcode);

        if (mb_strlen($normalized) < 5) {
            return 'COLOR:' . (string) $fallbackColorId;
        }

        return 'MODEL:' . mb_substr($normalized, 0, 5);
    }

    /**
     * Paginate complete model groups while returning their original rows.
     *
     * The paginator total and links represent model groups. The page data is
     * flattened so the existing inventory tables can keep rendering one row
     * per color/price/size without any client-side data migration.
     */
    public function paginate(
        Collection $items,
        Request $request,
        callable $barcodeResolver,
        callable $fallbackColorResolver,
        callable $activityResolver,
        callable $rowOrderResolver,
        int $modelsPerPage = self::MODELS_PER_PAGE
    ): LengthAwarePaginator {
        $groups = $items
            ->groupBy(function ($item) use ($barcodeResolver, $fallbackColorResolver) {
                return $this->modelCode(
                    $barcodeResolver($item),
                    $fallbackColorResolver($item)
                );
            })
            ->map(function (Collection $rows, string $modelCode) use (
                $barcodeResolver,
                $activityResolver,
                $rowOrderResolver
            ) {
                $orderedRows = $rows->sort(function ($left, $right) use ($barcodeResolver, $rowOrderResolver) {
                    $barcodeComparison = strnatcasecmp(
                        $this->normalizeBarcode($barcodeResolver($left)),
                        $this->normalizeBarcode($barcodeResolver($right))
                    );

                    if ($barcodeComparison !== 0) {
                        return $barcodeComparison;
                    }

                    return strnatcasecmp(
                        (string) $rowOrderResolver($left),
                        (string) $rowOrderResolver($right)
                    );
                })->values();

                return [
                    'model_code' => $modelCode,
                    'latest_activity' => $rows->max(function ($row) use ($activityResolver) {
                        return $this->activityTimestamp($activityResolver($row));
                    }) ?: 0,
                    'rows' => $orderedRows,
                ];
            })
            ->sort(function (array $left, array $right) {
                if ($left['latest_activity'] !== $right['latest_activity']) {
                    return $right['latest_activity'] <=> $left['latest_activity'];
                }

                return strnatcasecmp($left['model_code'], $right['model_code']);
            })
            ->values();

        $page = max(1, (int) $request->query('page', 1));
        $pageGroups = $groups->forPage($page, $modelsPerPage);
        $pageRows = $pageGroups
            ->flatMap(function (array $group) {
                return $group['rows'];
            })
            ->values();

        $paginator = new LengthAwarePaginator(
            $pageRows,
            $groups->count(),
            $modelsPerPage,
            $page,
            [
                'path' => $request->url(),
                'pageName' => 'page',
            ]
        );

        return $paginator->appends($request->query());
    }

    private function normalizeBarcode(?string $barcode): string
    {
        $barcode = preg_replace('/\s+/u', '', trim((string) $barcode));

        return mb_strtoupper($barcode ?: '', 'UTF-8');
    }

    private function activityTimestamp($value): int
    {
        if ($value instanceof DateTimeInterface) {
            return $value->getTimestamp();
        }

        if (is_numeric($value)) {
            return (int) $value;
        }

        $timestamp = strtotime((string) $value);

        return $timestamp === false ? 0 : $timestamp;
    }
}
