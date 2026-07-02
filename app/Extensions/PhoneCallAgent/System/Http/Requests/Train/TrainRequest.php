<?php

namespace App\Extensions\PhoneCallAgent\System\Http\Requests\Train;

use App\Extensions\PhoneCallAgent\System\Http\Requests\Concerns\AuthorizesAgentOwnership;
use App\Extensions\PhoneCallAgent\System\Models\ExtPhoneCallAgent;
use App\Extensions\PhoneCallAgent\System\Models\ExtPhoneCallAgentTrain;
use Illuminate\Foundation\Http\FormRequest;

class TrainRequest extends FormRequest
{
    use AuthorizesAgentOwnership;

    public function rules(): array
    {
        return [
            'id'     => 'required|exists:' . (new ExtPhoneCallAgent)->getTable() . ',id',
            'data'   => 'required|array',
            'data.*' => 'required|exists:' . (new ExtPhoneCallAgentTrain)->getTable() . ',id',
        ];
    }
}
