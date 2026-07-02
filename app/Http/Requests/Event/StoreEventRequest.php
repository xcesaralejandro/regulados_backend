<?php

namespace App\Http\Requests\Event;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class StoreEventRequest extends FormRequest
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
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'event_category_id' => 'required|exists:event_categories,id',
            'title'             => 'required|string|max:255',
            'description'       => 'nullable|string',
            'location'          => 'nullable|string',
            'notes'             => 'nullable|string',
            'visibility'        => 'required|in:public,contacts,private',
            'start_at'          => 'required|date',
            'end_at'            => 'required|date|after_or_equal:start_at',
        ];
    }
}
