<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class BulkOrderActionRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'order_ids' => 'required|array|min:1',
            'order_ids.*' => 'required|exists:orders,id',
            'rider_id' => 'nullable|exists:users,id',
            'agent_id' => 'nullable|exists:users,id',
            'status' => 'nullable',
        ];
    }

    public function authorize(): bool
    {
        return true;
    }
}