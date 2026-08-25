<?php

namespace App\Http\Requests\Deployment;

use Illuminate\Foundation\Http\FormRequest;

class UploadDeploymentPackageRequest extends FormRequest
{
    public function authorize(): bool
    {
        /*
         * Authorization remains enforced by the explicit
         * deployment_upload route middleware.
         */
        return true;
    }

    public function rules(): array
    {
        return [
            'package' => [
                'required',
                'file',
                'mimes:zip',
                'max:102400',
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'package.required' => 'Package deployment wajib dipilih.',
            'package.file' => 'Package deployment harus berupa file.',
            'package.mimes' => 'Package deployment harus menggunakan format ZIP.',
            'package.max' => 'Ukuran package deployment maksimal 100 MB.',
        ];
    }
}