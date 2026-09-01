<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UserProductRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules(): array
    {
        switch($this->method()) {
            case 'POST':
                return [
                    'stock' => 'required',
                    'retail_price' => 'required',
                    'wholesale_price' => 'required',
                    'user' => 'required',
                    'product' => 'required',
                    'price_before_discount' => 'nullable|numeric',
                ];
            case 'PUT':
            case 'PATCH':
            case 'DELETE':
                return [];
            default:break;
        }
        return [];
    }

    public function messages(): array
    {
        return [
            '*.required' => 'هذا الحقل لا يجب أن يكون فارغ',
            '*.min' => 'هذا الحقل يجب أن يكون على الأقل من :min أحرف',
            '*.max' => 'هذا الحقل يجب أن يكون على الأكثر من :max أحرف'
        ];
    }
}
