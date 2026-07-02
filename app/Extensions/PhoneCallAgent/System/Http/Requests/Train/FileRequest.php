<?php

namespace App\Extensions\PhoneCallAgent\System\Http\Requests\Train;

use App\Extensions\PhoneCallAgent\System\Http\Requests\Concerns\AuthorizesAgentOwnership;
use App\Extensions\PhoneCallAgent\System\Models\ExtPhoneCallAgent;
use Illuminate\Foundation\Http\FormRequest;

class FileRequest extends FormRequest
{
    use AuthorizesAgentOwnership;

    public function rules(): array
    {
        return [
            'id'   => 'required|exists:' . (new ExtPhoneCallAgent)->getTable() . ',id',
            'file' => ['required', 'file', 'max:10240', 'mimes:pdf,txt,doc,docx'],
        ];
    }
}
