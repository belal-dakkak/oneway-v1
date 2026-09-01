<?php

namespace App\Http\Requests;

use App\Models\Wallet;
use Illuminate\Foundation\Http\FormRequest;

class FabricRequest extends FormRequest
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
                    'name' => 'required',
                    'color' => 'required',
                    'yards' => 'required',
                    'user' => 'required',
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
            '*.numeric' => 'هذا الحقل يجب أن يكون رقما',
            '*.max' => 'لا تملك في صندوقك هذا المبلغ',
        ];
    }
}
