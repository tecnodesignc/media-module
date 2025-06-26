<?php

namespace Modules\Media\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UploadLargeMediaRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'file_name' => [
                'required',
                'string',
            ],
            'file_type' => [
                'required',
                'string',
            ],
            'path' => [
                'required',
                'string',
            ],
            'chunk' => [
                'required',
                'integer',
                'min:0',
            ],
            'chunks' => [
                'required',
                'integer',
                'min:1',
            ],
            'file' => [
                'required',
            ],
        ];
    }

    public function messages(): array
    {
        $size = $this->getMaxFileSize();

        return [
            'file.max' => trans('media::media.file too large', ['size' => $size]),
        ];
    }

    public function authorize(): bool
    {
        return true;
    }

    private function getMaxFileSizeInKilobytes(): float|int
    {
        return $this->getMaxFileSize() * 1000;
    }

    private function getMaxFileSize()
    {
        return config('encore.media.config.max-file-size');
    }
}
