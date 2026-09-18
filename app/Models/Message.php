<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\MessageType;
use Database\Factories\MessageFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Storage;

/**
 * @property int $id
 * @property int $conversation_id
 * @property int $sender_id
 * @property MessageType $type
 * @property string|null $body
 * @property string|null $attachment_path
 * @property string|null $attachment_mime
 * @property int|null $attachment_size
 * @property int|null $audio_duration
 * @property Carbon|null $deleted_for_everyone_at
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read Conversation $conversation
 * @property-read User $sender
 * @property-read Collection<int, MessageDeletion> $deletions
 * @property-read string|null $attachment_url
 */
#[Fillable([
    'conversation_id',
    'sender_id',
    'type',
    'body',
    'attachment_path',
    'attachment_mime',
    'attachment_size',
    'audio_duration',
    'deleted_for_everyone_at',
])]
class Message extends Model
{
    /** @use HasFactory<MessageFactory> */
    use HasFactory;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'type' => MessageType::class,
            'attachment_size' => 'integer',
            'audio_duration' => 'integer',
            'deleted_for_everyone_at' => 'datetime',
        ];
    }

    /**
     * Absolute URL to the uploaded attachment, using default disk.
     *
     * @return Attribute<string|null, never>
     */
    protected function attachmentUrl(): Attribute
    {
        return Attribute::make(
            get: fn (): ?string => $this->attachment_path === null ? null : Storage::url($this->attachment_path),
        );
    }

    /**
     * @return BelongsTo<Conversation, $this>
     */
    public function conversation(): BelongsTo
    {
        return $this->belongsTo(Conversation::class);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function sender(): BelongsTo
    {
        return $this->belongsTo(User::class, 'sender_id');
    }

    /**
     * @return HasMany<MessageDeletion, $this>
     */
    public function deletions(): HasMany
    {
        return $this->hasMany(MessageDeletion::class);
    }

    public function isDeletedForEveryone(): bool
    {
        return $this->deleted_for_everyone_at !== null;
    }
}
