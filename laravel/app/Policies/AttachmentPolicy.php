<?php

namespace App\Policies;

use App\Models\Attachment;
use App\Models\User;

class AttachmentPolicy
{
    public function view(User $user, Attachment $attachment): bool
    {
        $attachable = $attachment->attachable;

        if ($attachable instanceof \App\Models\Note) {
            if ($attachable->status === 'draft') {
                return $user->id === $attachable->user_id;
            }
            return true;
        }

        return $user->id === $attachable->id;
    }

    public function create(User $user): bool
    {
        return true;
    }

    public function delete(User $user, Attachment $attachment): bool
    {
        $attachable = $attachment->attachable;

        if ($attachable instanceof \App\Models\Note) {
            return $user->id === $attachable->user_id || $user->isAdmin();
        }

        return $user->id === $attachable->id;
    }
}
