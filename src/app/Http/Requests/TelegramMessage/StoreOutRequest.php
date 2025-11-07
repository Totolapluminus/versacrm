<?php

namespace App\Http\Requests\TelegramMessage;

use Illuminate\Foundation\Http\FormRequest;

class StoreOutRequest extends FormRequest
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
            'text' => 'required|string',
            'telegram_chat_db_id' => 'required|numeric|exists:telegram_chats,id',
            'telegram_chat_tg_id' => 'required|numeric|exists:telegram_chats,chat_id',
            'direction' => 'required|string'
        ];
    }
}
