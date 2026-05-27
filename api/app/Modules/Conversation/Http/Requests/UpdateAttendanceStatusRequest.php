<?php

namespace App\Modules\Conversation\Http\Requests;

use App\Modules\Conversation\Domain\Enums\ConversationAttendanceStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateAttendanceStatusRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'attendance_status' => [
                'required',
                'string',
                Rule::enum(ConversationAttendanceStatus::class),
            ],
        ];
    }
}
