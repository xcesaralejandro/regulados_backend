<?php

namespace App\Http\Requests\EventSeries;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class StoreEventSeriesRequest extends FormRequest
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
            'start_at'          => 'required|date_format:H:i:s',
            'end_at'            => 'required|date_format:H:i:s|after:start_at',
            'repeat_from'       => 'required|required_with:repeat_to,repeat_days|date',
            'repeat_to'         => 'required|required_with:repeat_from,repeat_days|date|after_or_equal:repeat_from',
            'repeat_days'       => 'required|required_with:repeat_from,repeat_to|array',
            'repeat_days.*'     => 'in:monday,tuesday,wednesday,thursday,friday,saturday,sunday',
        ];
    }
}
