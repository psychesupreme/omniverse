<?php

namespace App\Http\Requests\Api\V1;

use Illuminate\Foundation\Http\FormRequest;

class PushSyncRequest extends FormRequest
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
            'client_timestamp' => 'required|date',
            'data' => 'required|array',
            'data.outlets' => 'nullable|array',
            'data.outlets.*.id' => 'required',
            'data.outlets.*.name' => 'required|string',
            'data.outlets.*.location' => 'required|array',
            'data.outlets.*.location.latitude' => 'required|numeric',
            'data.outlets.*.location.longitude' => 'required|numeric',
            'data.outlets.*.version' => 'required|integer',
            'data.outlets.*.last_updated_at' => 'required|date',
            'data.tracking_logs' => 'nullable|array',
            'data.tracking_logs.*.id' => 'required',
            'data.tracking_logs.*.user_id' => 'required|integer',
            'data.tracking_logs.*.location' => 'required|array',
            'data.tracking_logs.*.location.latitude' => 'required|numeric',
            'data.tracking_logs.*.location.longitude' => 'required|numeric',
            'data.tracking_logs.*.speed' => 'required|numeric',
            'data.tracking_logs.*.recorded_at_mobile' => 'required|date',
            'data.tracking_logs.*.version' => 'required|integer',
            'data.tracking_logs.*.last_updated_at' => 'required|date',
        ];
    }
}
