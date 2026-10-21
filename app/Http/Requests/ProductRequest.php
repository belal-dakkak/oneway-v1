<?php

namespace App\Http\Requests;

use App\Support\Country;
use Illuminate\Foundation\Http\FormRequest;

class ProductRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        return [
            'name' => 'required',
            'name_en' => 'required',
            'retail_price' => 'required|numeric|min:0',
            'price_before_discount' => 'nullable|numeric|min:0',
            'details' => 'required',
            'details_en' => 'required',
            'selected_category' => 'required',
            'cost_price' => 'required|numeric|min:0',
            'sale_price' => 'nullable|numeric|min:0',
            'country' => 'required',
            'country.value' => 'nullable|integer|in:1,2,3,4',
            'country.id' => 'nullable|integer|in:1,2,3,4',
            'price_currency_code' => 'required|string|in:USD,AED,SYP',
            'selected_products' => 'required|array|min:1',
            'selected_products.*.is_cover' => 'nullable|boolean',
        ];
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            $currency = strtoupper((string) $this->input('price_currency_code'));
            $pattern = $currency === 'SYP' ? '/^\d+$/' : '/^\d+(?:\.\d{1,2})?$/' ;
            foreach (['cost_price', 'retail_price', 'sale_price', 'price_before_discount'] as $field) {
                $value = $this->input($field);
                if ($value === null || $value === '') {
                    continue;
                }
                if (!preg_match($pattern, (string) $value)) {
                    $validator->errors()->add($field, $currency === 'SYP'
                        ? 'يجب إدخال مبلغ صحيح دون كسور.'
                        : 'يجب ألا يتجاوز السعر منزلتين عشريتين.');
                }
            }

            $country = $this->input('country');
            $countryId = is_array($country) ? (int) ($country['value'] ?? $country['id'] ?? 0) : 0;
            if ($countryId && $countryId !== Country::globalProductId()) {
                $allowed = Country::currencyCodes($countryId);
                if (!in_array($currency, $allowed, true)) {
                    $validator->errors()->add('price_currency_code', 'عملة السعر لا تتوافق مع بلد المنتج.');
                }
            }

            $coverCount = collect($this->input('selected_products', []))
                ->filter(function ($product) {
                    return filter_var($product['is_cover'] ?? false, FILTER_VALIDATE_BOOLEAN);
                })
                ->count();
            if ($coverCount > 1) {
                $validator->errors()->add('selected_products', 'يمكن اختيار صورة غلاف واحدة فقط.');
            }
        });
    }

    public function messages()
    {
        return [
          '*.required' => 'هذا الحقل مطلوب'
        ];
    }
}
