<?php

namespace App\Policies;

use App\Models\Task;
use App\Models\User;

class TaskPolicy
{
    public function viewAny(User $user, Task $task): bool
    {
        $note = $task->note;

        if ($note->status === 'draft') {
            return $user->id === $note->user_id;
        }

        return true;
    }

    public function view(User $user, Task $task): bool
    {
        $note = $task->note;

        if ($note->status === 'draft') {
            return $user->id === $note->user_id;
        }

        return true;
    }

    public function create(User $user, Task $task): bool
    {
        return $user->id === $task->note->user_id;
    }

    public function update(User $user, Task $task): bool
    {
        return $user->id === $task->note->user_id;
    }

    public function delete(User $user, Task $task): bool
    {
        return $user->id === $task->note->user_id;
    }
}
