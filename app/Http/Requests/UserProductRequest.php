<?php

namespace App\Http\Requests;

use App\Models\User;
use App\Services\CurrencyService;
use App\Services\InventoryTransferService;
use App\Support\Country;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UserProductRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        if (!$this->isMethod('post')) {
            return;
        }

        $destination = $this->input('destination_user_id', $this->input('user'));
        if (is_array($destination)) {
            $destination = $destination['id'] ?? null;
        }

        $product = $this->input('product_color_id', $this->input('product'));
        if (is_array($product)) {
            $product = $product['product_color_id'] ?? $product['id'] ?? null;
        }

        $merchant = $this->input('merchant_id', $this->input('merchant'));
        if (is_array($merchant)) {
            $merchant = $merchant['id'] ?? null;
        }

        $items = $this->decodeArray($this->input('items'));
        if (!$items) {
            $items = $this->decodeArray($this->input('lsizes'));
        }

        // Backwards compatibility for the old one-size transfer modal.
        if (!$items && $this->filled('stock') && ($this->filled('size') || $this->filled('barcode'))) {
            $items = [[
                'size' => $this->input('size'),
                'barcode' => $this->input('barcode'),
                'quantity' => $this->input('stock'),
            ]];
        }

        $items = collect($items ?: [])->map(function ($item) {
            if (!is_array($item)) {
                return $item;
            }

            return [
                'size' => $item['size'] ?? null,
                'barcode' => $item['barcode'] ?? null,
                'quantity' => $item['quantity'] ?? $item['qty'] ?? $item['stock'] ?? null,
            ];
        })->filter(function ($item) {
            return is_array($item) && (int) ($item['quantity'] ?? 0) > 0;
        })->values()->all();

        $this->merge([
            'destination_user_id' => $destination,
            'product_color_id' => $product,
            'merchant_id' => $merchant ?: null,
            'currency_code' => strtoupper((string) $this->input('currency_code', 'USD')),
            'source_type' => strtolower((string) $this->input(
                'source_type',
                InventoryTransferService::SOURCE_INVENTORY
            )),
            'items' => $items,
        ]);
    }

    public function rules(): array
    {
        if (!$this->isMethod('post')) {
            return [];
        }

        $countryId = (int) optional($this->user())->country_id;
        $currencyCodes = array_values(array_unique(array_merge(
            Country::currencyCodes($countryId),
            ['USD']
        )));

        return [
            'destination_user_id' => ['required', 'integer', 'exists:users,id'],
            'product_color_id' => ['required', 'integer', 'exists:product_colors,id'],
            'merchant_id' => ['nullable', 'integer', 'exists:users,id'],
            'retail_price' => ['required', 'numeric', 'gt:0'],
            'wholesale_price' => ['required', 'numeric', 'gt:0'],
            'price_before_discount' => ['nullable', 'numeric', 'min:0'],
            'currency_code' => ['required', Rule::in($currencyCodes)],
            'source_type' => ['required', Rule::in([
                InventoryTransferService::SOURCE_CATALOG,
                InventoryTransferService::SOURCE_INVENTORY,
            ])],
            'items' => ['required', 'array', 'min:1'],
            'items.*.size' => ['required', 'string', 'max:255'],
            'items.*.barcode' => ['required', 'string', 'max:255', 'distinct'],
            'items.*.quantity' => ['required', 'integer', 'min:1'],
        ];
    }

    public function withValidator($validator): void
    {
        if (!$this->isMethod('post')) {
            return;
        }

        $validator->after(function ($validator) {
            if (!$this->user()) {
                return;
            }

            if (!$validator->errors()->has('currency_code')) {
                try {
                    app(CurrencyService::class)->rate((string) $this->input('currency_code'));
                } catch (\InvalidArgumentException $exception) {
                    $validator->errors()->add('currency_code', 'سعر صرف العملة غير صالح أو غير محدد.');
                }
            }

            if ($validator->errors()->isNotEmpty()) {
                return;
            }

            $countryId = (int) $this->user()->country_id;
            $destination = User::query()->find($this->input('destination_user_id'));
            if (!$destination || (int) $destination->country_id !== $countryId ||
                !in_array((int) $destination->role_id, [User::ROLE_SHOP, User::ROLE_WAREHOUSE], true)) {
                $validator->errors()->add('destination_user_id', 'الوجهة المختارة غير متاحة في هذا البلد.');
            }

            if ($this->input('source_type') === InventoryTransferService::SOURCE_INVENTORY &&
                (int) $this->user()->role_id === User::ROLE_WAREHOUSE &&
                (int) $this->input('destination_user_id') === (int) $this->user()->id) {
                $validator->errors()->add('destination_user_id', 'يجب أن تكون جهة الاستلام مختلفة عن المستودع المرسل.');
            }

            if ($this->filled('merchant_id')) {
                $merchant = User::query()->find($this->input('merchant_id'));
                if (!$merchant || (int) $merchant->country_id !== $countryId ||
                    (int) $merchant->role_id !== User::ROLE_MERCHANT) {
                    $validator->errors()->add('merchant_id', 'التاجر المختار غير متاح في هذا البلد.');
                }
            }
        });
    }

    public function messages(): array
    {
        return [
            '*.required' => 'هذا الحقل مطلوب.',
            '*.numeric' => 'يجب إدخال رقم صحيح.',
            '*.gt' => 'يجب أن تكون القيمة أكبر من صفر.',
            'items.min' => 'اختر مقاسًا واحدًا على الأقل وحدد كميته.',
            'items.*.quantity.min' => 'يجب أن تكون الكمية أكبر من صفر.',
        ];
    }

    private function decodeArray($value): ?array
    {
        if (is_array($value)) {
            return $value;
        }

        if (!is_string($value) || trim($value) === '') {
            return null;
        }

        $decoded = json_decode($value, true);
        return is_array($decoded) ? $decoded : null;
    }
}
