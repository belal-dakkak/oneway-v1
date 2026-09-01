<?php

namespace App\Http\Requests\Api;

use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;

class UpdateUserRequest extends FormRequest
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
            case 'GET':
            case 'DELETE':
                return [];
            case 'PATCH':
            case 'PUT':
            case 'POST':
                if (auth()->check())
                    $user =  User::query()->find(auth()->id());
                elseif ($userId = $this->request->get('user_id'))
                    $user = User::find($userId);
                else
                    $user = null;

                if (!$user)
                    return [];
                return [
                    'name' => 'max:100',
                    'email' => 'email|unique:users,email,'.$user->id,
                    'password' => 'confirmed|min:6',
                ];
            default:break;
        }
        return [];
    }
}
