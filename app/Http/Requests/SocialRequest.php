<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class SocialRequest extends FormRequest
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
            case 'GET':
            case 'PATCH':
            case 'PUT':
            case 'DELETE':
                return [];
            case 'POST':
                return [
                    'email' => 'required|email',
                    'phone_number'=>'nullable|unique:users,phone_number'
                ];
            default:break;
        }
        return [];
    }

    /**
     * Get the error messages for the defined validation rules.
     *
     * @return array
     */
    public function messages(): array
    {
        return [
            'email.unique' => 'email address has already been taken',
            'email.email' => 'please enter a valid email address',
            'first_name.required' => 'first name is required'
        ];
    }
}
