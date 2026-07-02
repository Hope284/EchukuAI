<?php

namespace App\Extensions\PhoneCallAgent\System\Http\Requests\Train;

use App\Extensions\PhoneCallAgent\System\Http\Requests\Concerns\AuthorizesAgentOwnership;
use App\Extensions\PhoneCallAgent\System\Models\ExtPhoneCallAgent;
use App\Support\Security\SafeRemoteUrl;
use Closure;
use Illuminate\Foundation\Http\FormRequest;

class TrainUrlRequest extends FormRequest
{
    use AuthorizesAgentOwnership;

    public function rules(): array
    {
        return [
            'id'     => 'required|exists:' . (new ExtPhoneCallAgent)->getTable() . ',id',
            'url'    => [
                'required',
                'url',
                function (string $attribute, mixed $value, Closure $fail): void {
                    if (! SafeRemoteUrl::isAllowed((string) $value)) {
                        $fail(__('Only public HTTP or HTTPS URLs can be used.'));
                    }
                },
            ],
            'single' => ['required', 'in:1,0'],
        ];
    }
}
