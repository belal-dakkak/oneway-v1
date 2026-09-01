<?php

namespace App\Observers;

use App\Models\User;

class UserObserver
{
    public function created(User $user)
    {
        $user->wallet()->create(['credit' => 0, 'debit' =>0]);
    }
}
