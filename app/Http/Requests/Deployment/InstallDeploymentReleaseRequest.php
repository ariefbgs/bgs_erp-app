<?php

namespace App\Http\Requests\Deployment;

use Illuminate\Foundation\Http\FormRequest;

final class InstallDeploymentReleaseRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'application_release_id' => [
                'required',
                'integer',
                'min:1',
                'exists:application_releases,id',
            ],
        ];
    }
}
