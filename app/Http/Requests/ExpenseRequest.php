<?php

namespace App\Http\Requests;

use App\Models\Wallet;
use Illuminate\Foundation\Http\FormRequest;

class ExpenseRequest extends FormRequest
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
                $userCredit = Wallet::query()->firstOrCreate(['user_id' => auth()->id()])->credit;
                return [
                    'description' => 'required',
                    'amount' => 'required|numeric|max:'.$userCredit
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
