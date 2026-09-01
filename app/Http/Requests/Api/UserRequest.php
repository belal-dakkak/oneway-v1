<?php

namespace App\Http\Requests\Api;

use App\Models\User;
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
                $user = User::where('email',$this->get('email'))->where('deleted',1)->first();
                if($user)
                    return [
                        'name' => 'required|string|min:2|max:100',
                        'password' => 'nullable',
                        'email' => 'email|unique:users,email,'.$user?$user->id: '|nullable'
                    ];
                else
                    return [
                        'name' => 'required|string|min:2|max:100',
                        'password' => 'nullable',
                        'email' => 'email|nullable'
                    ];
            case 'PUT':
            case 'PATCH':
                $user = $this->route()->user;
                return [
                    'name' => 'required|string|min:2|max:100',
                    'password' => 'nullable',
                    'email' => 'email|nullable|unique:users,email,'.$user?$user->id:'',
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
