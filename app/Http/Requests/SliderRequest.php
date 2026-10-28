<?php

namespace App\Http\Requests;

use App\Support\UploadedMediaPath;
use Illuminate\Foundation\Http\FormRequest;

class SliderRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $place = $this->input('place', $this->input('value'));
        $type = is_array($place) ? ($place['value'] ?? null) : $place;

        $country = $this->input('country');
        $countryId = is_array($country)
            ? ($country['value'] ?? $country['id'] ?? null)
            : $country;
        if ($countryId === '') {
            $countryId = null;
        }

        $this->merge([
            'type' => $type,
            'country_id' => $countryId,
            'image_path' => UploadedMediaPath::normalize($this->input('image')),
        ]);
    }

    public function rules(): array
    {
        return [
            'type' => ['required', 'integer', 'in:1,2,3'],
            'country_id' => ['nullable', 'integer', 'in:1,2,4'],
            'image_path' => [
                $this->isMethod('post') ? 'required' : 'nullable',
                'string',
                'max:255',
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'type.required' => 'يرجى اختيار موضع السلايدر.',
            'type.in' => 'موضع السلايدر المحدد غير صالح.',
            'country_id.in' => 'الدولة المحددة غير صالحة.',
            'image_path.required' => 'صورة السلايدر مطلوبة.',
        ];
    }
}
