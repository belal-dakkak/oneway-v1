<?php

namespace App\Http\Requests;

use App\Models\Wallet;
use App\Services\CurrencyService;
use App\Support\Country;
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
                $wallet = Wallet::query()->firstOrCreate(
                    ['user_id' => auth()->id(), 'currency_code' => 'USD'],
                    ['credit' => 0, 'debit' => 0]
                );
                $currency = Country::defaultCurrency((int) auth()->user()->country_id);
                $rate = app(CurrencyService::class)->rate($currency);
                $userCredit = max(0, (float) $wallet->credit - (float) $wallet->debit) * $rate;
                return [
                    'description' => 'required',
                    'amount' => 'required|numeric|min:0.0001|max:'.$userCredit
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
