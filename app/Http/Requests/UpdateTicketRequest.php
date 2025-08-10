<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateTicketRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        $ticket = $this->route('ticket');
        
        // Allow if user is authenticated and either:
        // - User is assigned to the ticket
        // - User has admin/manager role
        // - User is the creator of the ticket
        // return auth()->check() && (
        //     $ticket->assigned_user_id === auth()->id() ||
        //     auth()->user()->hasAnyRole(['admin', 'manager']) ||
        //     $ticket->created_by === auth()->id() // if you track ticket creator
        // );

        return true; // For simplicity, allow all users. Adjust as needed.
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $ticket = $this->route('ticket');
        
        return [
            // Foreign key relationships (optional)
            'client_id' => ['sometimes', 'nullable', 'exists:clients,id'],
            'contact_id' => ['sometimes', 'nullable', 'exists:contacts,id'],
            'vendor_id' => ['sometimes', 'nullable', 'exists:vendors,id'],
            'assigned_user_id' => ['sometimes', 'nullable', 'exists:users,id'],

            // Client information
            'client_name' => ['sometimes', 'required', 'string', 'max:255'],
            'client_phone' => ['sometimes', 'nullable', 'string', 'max:20', 'regex:/^[\+\-\(\)\s\d]+$/'],
            'client_email' => ['sometimes', 'nullable', 'email', 'max:255'],

            // Ticket details
            'subject' => ['sometimes', 'required', 'string', 'max:255'],
            'description' => ['sometimes', 'required', 'string', 'max:5000'],
            'priority' => ['sometimes', 'required', Rule::in(['low', 'medium', 'high', 'urgent'])],
            'category' => ['sometimes', 'required', 'string', 'max:100'],
            'status' => ['sometimes', 'required', Rule::in(['open', 'in_progress', 'pending', 'resolved', 'closed'])],

            // Call information
            'call_duration' => ['sometimes', 'nullable', 'integer', 'min:0', 'max:9999'],

            // Additional fields for updates
            'resolution_notes' => ['sometimes', 'nullable', 'string', 'max:2000'],
            'internal_notes' => ['sometimes', 'nullable', 'string', 'max:2000'],
        ];
    }

    /**
     * Get custom validation messages.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'client_name.required' => 'Client name is required.',
            'client_phone.regex' => 'Please enter a valid phone number.',
            'client_email.email' => 'Please enter a valid email address.',
            'subject.required' => 'Ticket subject is required.',
            'subject.max' => 'Subject cannot exceed 255 characters.',
            'description.required' => 'Ticket description is required.',
            'description.max' => 'Description cannot exceed 5000 characters.',
            'priority.required' => 'Priority is required.',
            'priority.in' => 'Priority must be one of: low, medium, high, urgent.',
            'status.required' => 'Status is required.',
            'status.in' => 'Status must be one of: open, in_progress, pending, resolved, closed.',
            'category.required' => 'Category is required.',
            'call_duration.integer' => 'Call duration must be a number.',
            'call_duration.min' => 'Call duration cannot be negative.',
            'call_duration.max' => 'Call duration cannot exceed 9999 minutes.',
            'resolution_notes.max' => 'Resolution notes cannot exceed 2000 characters.',
            'internal_notes.max' => 'Internal notes cannot exceed 2000 characters.',
            'client_id.exists' => 'Selected client does not exist.',
            'contact_id.exists' => 'Selected contact does not exist.',
            'vendor_id.exists' => 'Selected vendor does not exist.',
            'assigned_user_id.exists' => 'Selected user does not exist.',
        ];
    }

    /**
     * Get custom attribute names for validation errors.
     *
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'client_name' => 'client name',
            'client_phone' => 'client phone',
            'client_email' => 'client email',
            'assigned_user_id' => 'assigned user',
            'call_duration' => 'call duration',
            'resolution_notes' => 'resolution notes',
            'internal_notes' => 'internal notes',
        ];
    }

    /**
     * Prepare the data for validation.
     */
    protected function prepareForValidation(): void
    {
        // Clean up phone number if provided
        if ($this->has('client_phone') && $this->client_phone) {
            $this->merge([
                'client_phone' => preg_replace('/[^\+\-\(\)\s\d]/', '', $this->client_phone)
            ]);
        }

        // Convert empty strings to null for nullable fields
        $nullableFields = [
            'client_id', 'contact_id', 'vendor_id', 'assigned_user_id', 
            'client_phone', 'client_email', 'call_duration', 
            'resolution_notes', 'internal_notes'
        ];
        
        foreach ($nullableFields as $field) {
            if ($this->has($field) && $this->$field === '') {
                $this->merge([$field => null]);
            }
        }

        // Auto-set resolved timestamp if status is being changed to resolved
        if ($this->has('status') && $this->status === 'resolved' && !$this->has('resolved_at')) {
            $this->merge(['resolved_at' => now()]);
        }

        // Auto-set closed timestamp if status is being changed to closed
        if ($this->has('status') && $this->status === 'closed' && !$this->has('closed_at')) {
            $this->merge(['closed_at' => now()]);
        }
    }

    /**
     * Configure the validator instance.
     */
    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            $ticket = $this->route('ticket');

            // Ensure at least one contact method is provided if client info is being updated
            if ($this->has('client_name') || $this->has('client_phone') || $this->has('client_email')) {
                $phone = $this->client_phone ?? $ticket->client_phone;
                $email = $this->client_email ?? $ticket->client_email;
                
                if (empty($phone) && empty($email)) {
                    $validator->errors()->add('client_contact', 'Either client phone or email must be provided.');
                }
            }

            // Validate that only one relationship type is set
            $currentRelationships = [
                'client_id' => $this->client_id ?? $ticket->client_id,
                'contact_id' => $this->contact_id ?? $ticket->contact_id,
                'vendor_id' => $this->vendor_id ?? $ticket->vendor_id,
            ];

            $activeRelationships = collect($currentRelationships)
                ->filter(fn($value) => !empty($value));

            if ($activeRelationships->count() > 1) {
                $validator->errors()->add('relationships', 'A ticket can only belong to one entity (client, contact, or vendor).');
            }

            // Require resolution notes when marking as resolved
            if ($this->has('status') && $this->status === 'resolved') {
                if (empty($this->resolution_notes) && empty($ticket->resolution_notes)) {
                    $validator->errors()->add('resolution_notes', 'Resolution notes are required when marking ticket as resolved.');
                }
            }

            // Prevent reopening closed tickets without proper permission
            if ($ticket->status === 'closed' && $this->has('status') && $this->status !== 'closed') {
                if (!auth()->user()->hasAnyRole(['admin', 'manager'])) {
                    $validator->errors()->add('status', 'You do not have permission to reopen a closed ticket.');
                }
            }
        });
    }

    /**
     * Get only the fields that should be updated.
     */
    public function getUpdateData(): array
    {
        $data = $this->validated();
        
        // Add metadata for tracking changes
        if (!empty($data)) {
            $data['updated_by'] = auth()->id();
        }

        return $data;
    }
}