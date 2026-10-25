<?php

namespace App\Http\Requests\Api\V1\User;

use App\Models\Story;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\UploadedFile;

class StoreStoryRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()?->can('create', Story::class) === true;
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [
            'media' => [
                'required',
                'file',
                'mimetypes:image/jpeg,image/png,image/webp,video/mp4,video/quicktime,video/webm',
                'max:51200',
            ],
            'caption' => ['sometimes', 'nullable', 'string', 'max:2200'],
        ];
    }

    public function media(): UploadedFile
    {
        /** @var UploadedFile $media */
        $media = $this->file('media');

        return $media;
    }

    public function caption(): ?string
    {
        $caption = $this->validated('caption');

        return is_string($caption) ? $caption : null;
    }
}
