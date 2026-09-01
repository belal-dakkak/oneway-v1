<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UserRequest extends FormRequest
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
                    'name' => 'required|string|min:2|max:100',
                    'password' => 'nullable',
                    'email' => 'email|unique:users,email|nullable',
                    'country_id' => 'required'
                ];
            case 'PUT':
            case 'PATCH':
                $user = $this->route()->user;
                return [
                    'name' => 'required|string|min:2|max:100',
                    'password' => 'nullable',
                    'email' => 'email|nullable|unique:users,email,'.$user->id,
                    'country_id' => 'required'
                ];
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
            '*.max' => 'هذا الحقل يجب أن يكون على الأكثر من :max أحرف',
            'password.confirmed' => 'يجب ان تطابق كلمة السر مع التأكيد',
            'email.email' => 'يجب أن يكون الإيميل بصيغة إيميل',
            'email.unique' => 'الإيميل تم استخدامه من أجل حساب آخر',
        ];
    }
}
