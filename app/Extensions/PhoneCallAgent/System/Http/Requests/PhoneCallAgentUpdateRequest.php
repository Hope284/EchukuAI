<?php

namespace App\Extensions\PhoneCallAgent\System\Http\Requests;

use App\Extensions\PhoneCallAgent\System\Enums\BookingProviderEnum;
use App\Extensions\PhoneCallAgent\System\Http\Requests\Concerns\AuthorizesAgentOwnership;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

class PhoneCallAgentUpdateRequest extends FormRequest
{
    use AuthorizesAgentOwnership;

    protected function prepareForValidation(): void
    {
        $data = ['user_id' => Auth::id()];
        if ($this->filled('service_type')) {
            $data['provider'] = $this->input('service_type') === 'direct' ? 'twilio' : 'elevenlabs';
        }

        $this->merge($data);
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'id'                    => ['required', 'integer', 'exists:ext_phone_call_agents,id'],
            'user_id'               => ['required', 'integer', 'exists:users,id'],
            'uuid'                  => ['required', 'string'],
            'title'                 => ['required', 'string'],
            'welcome_message'       => ['sometimes', 'nullable', 'string'],
            'instructions'          => ['sometimes', 'nullable', 'string'],
            'language'              => ['sometimes', 'nullable', 'string'],
            'provider'              => ['sometimes', 'nullable', 'string', 'in:twilio,elevenlabs'],
            'voice_id'              => ['sometimes', 'nullable', 'string'],
            'max_call_duration'     => ['sometimes', 'nullable', 'integer', 'min:30', 'max:3600'],
            'silence_timeout'       => ['sometimes', 'nullable', 'integer', 'min:5', 'max:300'],
            'active'                => ['sometimes', 'boolean'],
            'booking_enabled'       => ['sometimes', 'boolean'],
            'booking_provider'      => ['sometimes', 'nullable', Rule::enum(BookingProviderEnum::class)],
            'booking_event_type_id' => ['sometimes', 'nullable', 'string', 'max:255'],
            'booking_api_key'       => ['sometimes', 'nullable', 'string', 'max:2000'],
        ];
    }
}
