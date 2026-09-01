<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class BranchRequest extends FormRequest
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
            'name_en' => 'required',
            'name_ar' => 'required',
            'address_en' => 'required',
            'address_ar' => 'required',
            'latitude' => 'required',
            'longitude' => 'required'
        ];
    }

    public function messages()
    {
        return [
            '*.required' => 'هذا الحقل لا يجب أن يكون فارغ'
        ];
    }
}
