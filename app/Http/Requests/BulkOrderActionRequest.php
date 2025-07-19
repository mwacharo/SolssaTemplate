<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class BulkOrderActionRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'order_ids' => 'required|array|min:1',
            'order_ids.*' => 'exists:orders,id',
            'rider_id' => 'nullable|exists:riders,id',
            'agent_id' => 'nullable',
            'status' => 'nullable|string', // optionally refine this
        ];
    }

    public function authorize(): bool
    {
        return true;
    }
}