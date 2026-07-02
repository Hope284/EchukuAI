<?php

namespace App\Extensions\PhoneCallAgent\System\Http\Requests\Train;

use App\Extensions\PhoneCallAgent\System\Http\Requests\Concerns\AuthorizesAgentOwnership;
use App\Extensions\PhoneCallAgent\System\Models\ExtPhoneCallAgent;
use Illuminate\Foundation\Http\FormRequest;

class TextRequest extends FormRequest
{
    use AuthorizesAgentOwnership;

    public function rules(): array
    {
        return [
            'id'      => 'required|exists:' . (new ExtPhoneCallAgent)->getTable() . ',id',
            'title'   => ['required', 'string', 'max:255'],
            'content' => ['required', 'string', 'max:100000'],
        ];
    }
}
