<?php

namespace Modules\Media\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class MoveMediaRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'destinationFolder' => 'required',
        ];
    }

    public function authorize(): bool
    {
        return true;
    }

    public function messages(): array
    {
        return [];
    }
}
