<?php

declare(strict_types=1);

namespace App\Http\Requests\Api\V1\User;

use App\Enums\MessageType;
use Dedoc\Scramble\Attributes\BodyParameter;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

#[BodyParameter('audio_duration', required: false, type: 'integer')]
class SendMessageRequest extends FormRequest
{
    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        $type = $this->input('type');

        $fileRules = match ($type) {
            MessageType::Text->value => ['prohibited'],
            MessageType::Image->value => ['required', 'file', 'max:25600', 'mimes:jpeg,png,webp,gif,heic'],
            MessageType::Audio->value => ['required', 'file', 'max:25600', 'mimes:mp3,wav,m4a,ogg,webm'],
            MessageType::File->value => ['required', 'file', 'max:25600', 'mimes:pdf,doc,docx,xls,xlsx,ppt,pptx,txt,csv,zip,rar,7z'],
            default => ['nullable', 'file', 'max:25600'],
        };

        return [
            'type' => ['required', 'string', Rule::in(MessageType::values())],
            'body' => [
                $type === MessageType::Text->value ? 'required' : 'nullable',
                'string',
                'max:5000',
            ],
            'file' => $fileRules,
            'audio_duration' => $type === MessageType::Audio->value
                ? ['required', 'integer', 'min:1', 'max:7200']
                : ['prohibited'],
        ];
    }
}
