<?php

namespace App\Http\Requests;

use App\Support\EventInfo;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class RsvpRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'full_name' => is_string($this->full_name) ? preg_replace('/\s+/', ' ', trim($this->full_name)) : $this->full_name,
            'email' => is_string($this->email) ? trim($this->email) : $this->email,
            'pledge_amount' => is_string($this->pledge_amount) ? preg_replace('/[^0-9]/', '', $this->pledge_amount) : $this->pledge_amount,
        ]);
    }

    public function rules(): array
    {
        $isUpdate = $this->route('token') !== null;

        return [
            'title' => ['required', Rule::in(config('event.rsvp.titles'))],
            'full_name' => ['required', 'string', 'min:2', 'max:120'],
            'email' => $isUpdate ? ['prohibited'] : ['required', 'email:rfc', 'max:190'],
            'phone' => ['required', 'string', 'regex:/^\+?[0-9 ()\-]{7,20}$/'],
            'organisation' => ['nullable', 'string', 'max:160'],
            'attendance' => ['required', Rule::in(['yes', 'no', 'maybe'])],
            'party_size' => ['exclude_if:attendance,no', 'required', 'integer', 'min:1', 'max:'.EventInfo::maxPartySize()],
            'dietary_needs' => ['nullable', 'string', 'max:500'],
            'pledge_amount' => ['nullable', 'integer', 'min:'.config('event.donations.min_amount'), 'max:'.config('event.donations.max_amount')],
            'message' => ['nullable', 'string', 'max:1000'],
            'privacy' => $isUpdate ? ['nullable'] : ['accepted'],
            'website' => ['nullable', 'max:0'], // honeypot
            'invite_code' => ['nullable', 'string', 'max:20'],
        ];
    }

    public function messages(): array
    {
        return [
            'phone.regex' => 'Please enter a valid phone number, e.g. 0803 000 0000 or +234 803 000 0000.',
            'party_size.max' => 'Each RSVP may reserve up to '.EventInfo::maxPartySize().' seats, including yourself.',
            'privacy.accepted' => 'Please confirm you have read the privacy notice.',
            'pledge_amount.min' => 'The minimum pledge is ₦'.number_format(config('event.donations.min_amount')).'.',
            'website.max' => 'Your submission could not be processed.',
            'email.prohibited' => 'To change your email address, please contact the planning committee.',
        ];
    }

    public function attributes(): array
    {
        return ['full_name' => 'full name', 'party_size' => 'number of seats', 'pledge_amount' => 'pledge amount'];
    }
}
