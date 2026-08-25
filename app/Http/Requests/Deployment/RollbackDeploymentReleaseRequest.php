<?php

namespace App\Http\Requests\Deployment;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class RollbackDeploymentReleaseRequest extends FormRequest
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
                Rule::exists('application_releases', 'id')
                    ->where('status', 'installed'),
            ],
        ];
    }
}
