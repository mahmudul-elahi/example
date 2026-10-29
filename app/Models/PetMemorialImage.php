<?php

declare(strict_types=1);

namespace App\Models;

use Database\Factories\PetMemorialImageFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

/**
 * @property-read int $id
 * @property-read int $pet_memorial_id
 * @property-read string $path
 * @property-read string $url
 */
#[Fillable([
    'pet_memorial_id',
    'path',
])]
class PetMemorialImage extends Model
{
    /** @use HasFactory<PetMemorialImageFactory> */
    use HasFactory;

    /**
     * The absolute URL of the memorial image.
     *
     * @return Attribute<string, never>
     */
    protected function url(): Attribute
    {
        return Attribute::make(
            get: fn (): string => Storage::url($this->path),
        );
    }

    /**
     * @return BelongsTo<PetMemorial, $this>
     */
    public function petMemorial(): BelongsTo
    {
        return $this->belongsTo(PetMemorial::class);
    }
}
