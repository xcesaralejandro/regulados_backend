<?php

namespace App\Http\Requests\EventSeries;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class UpdateEventSeriesRequest extends FormRequest
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
            'event_category_id' => 'sometimes|required|exists:event_categories,id',
            'title'             => 'sometimes|required|string|max:255',
            'description'       => 'nullable|string',
            'location'          => 'nullable|string',
            'notes'             => 'nullable|string',
            'visibility'        => 'sometimes|required|in:public,contacts,private',
            'start_at'          => 'required_with:end_at|date_format:H:i:s',
            'end_at'            => 'required_with:start_at|date_format:H:i:s|after:start_at',
        ];
    }
}
