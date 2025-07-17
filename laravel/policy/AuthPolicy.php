<?php

namespace App\Policies;

use App\Models\User;
use App\Models\Post;
use App\Models\CaseFile;
use Illuminate\Auth\Access\HandlesAuthorization;

class AuthPolicy
{
    use HandlesAuthorization;

    public function __construct()
    {
        //
    }

    public function post(User $user, Post $post)
    {
        return $user->id === $post->user_id;
    }

    public function case(User $user, CaseFile $caseFile)
    {
        return $user->id === $caseFile->user_id;
    }
}
