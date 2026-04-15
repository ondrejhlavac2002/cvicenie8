<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class Attachment extends Model
{
    protected $fillable = [
        'public_id',
        'collection',
        'visibility',
        'disk',
        'path',
        'original_name',
        'stored_name',
        'mime_type',
        'size',
    ];

    protected static function booted(): void
    {
        static::creating(function (Attachment $attachment) {
            if (empty($attachment->public_id)) {
                $attachment->public_id = (string) Str::ulid();
            }
        });
    }

    public function attachable(): MorphTo
    {
        return $this->morphTo();
    }

    public function getPublicUrl(): ?string
    {
        if ($this->disk === 'public') {
            return Storage::disk('public')->url($this->path);
        }

        return null;
    }

    public function getTemporaryUrl(int $seconds = 30): string
    {
        return url("/api/attachments/{$this->public_id}/link");
    }

    public function getRouteKeyName(): string
    {
        return 'public_id';
    }
}
