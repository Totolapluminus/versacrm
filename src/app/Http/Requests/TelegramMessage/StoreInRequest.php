<?php

namespace App\Http\Requests\TelegramMessage;

use Illuminate\Foundation\Http\FormRequest;

class StoreInRequest extends FormRequest
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
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'user_id' => 'required|numeric',
            'user_username' => 'nullable|string',
            'user_first_name' => 'nullable|string',
            'user_last_name' => 'nullable|string',
            'chat_id' => 'required|numeric',
            'chat_type' => 'required|string',
            'direction' => 'required|string',
            'text' => 'required|string',
            'bot_tg_id' => 'required|numeric',
            'bot_db_id' => 'required|numeric',
            'bot_username' => 'nullable|string',
            'ticket_type' => 'required|string',
            'ticket_id' => 'required|string',
        ];
    }
}
