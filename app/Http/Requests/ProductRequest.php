<?php

namespace App\Http\Requests;

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
        ];
    }

    public function messages()
    {
        return [
          '*.required' => 'هذا الحقل مطلوب'
        ];
    }
}
