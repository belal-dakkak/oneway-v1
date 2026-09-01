<?php

namespace App\Http\Requests\Api;

use Illuminate\Foundation\Http\FormRequest;

class ActivateUserRequest extends FormRequest
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
        switch($this->method()) {
            case 'POST':
                return [
                    'email' => 'required|exists:users,email',
                    'activation_code' => 'required'
                ];
            default:break;
        }
        return [];
    }

    public function messages(): array
    {
        return [
            'email.exists' => 'We could not find any email address that matches the email you sent',
        ];
    }
}
