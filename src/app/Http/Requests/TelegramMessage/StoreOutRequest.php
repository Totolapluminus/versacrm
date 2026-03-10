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
            'text' => 'nullable|string|required_without:attachments',
            'telegram_chat_db_id' => 'required|numeric|exists:telegram_chats,id',
            'telegram_chat_tg_id' => 'required|numeric|exists:telegram_chats,chat_id',
            'direction' => 'required|string',
            'attachments' => 'nullable|array|required_without:text',
            'attachments.*' => 'file|max:15360|mimetypes:image/jpeg,image/png,image/gif,image/webp',
        ];
    }
}
