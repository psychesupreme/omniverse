<?php

namespace App\Http\Requests\Api\V1;

use Illuminate\Foundation\Http\FormRequest;

class PullSyncRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'last_sync_timestamp' => 'nullable|date_format:Y-m-d H:i:s',
            'collections' => 'required|array',
            'collections.*' => 'required|string|in:outlets,tracking_logs,products',
        ];
    }
}
