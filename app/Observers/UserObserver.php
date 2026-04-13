<?php

namespace App\Observers;

use App\Events\NewUserEvent;
use App\Models\TicketAgentGroups;
use App\Models\User;
use App\Traits\StoreHeaders;

class UserObserver
{

    use StoreHeaders;

    public function saving(User $user)
    {
        if (!isRunningInConsoleOrSeeding()) {
            if ($user->isDirty('status') && $user->status == 'deactive') {
                // Remove as ticket agent
                TicketAgentGroups::whereAgentId($user->id)->delete();
            }
        }

        session()->forget('user');
    }

    public function created(User $user)
    {
        if (!isRunningInConsoleOrSeeding()) {
            // Email sending disabled - SMTP removed
            // Set to false to prevent email notifications when creating users
            $sendMail = false;

            if (request()->has('sendMail') && request()->sendMail == 'no') {
                $sendMail = false;
            }

            if ($sendMail && request()->password != '' && auth()->check() && request()->email != '') {
                event(new NewUserEvent($user, request()->password));
            }
        }
    }

    public function creating(User $model)
    {
        if (company()) {
            $model->company_id = company()->id;
        }

        $this->storeHeaders($model);

    }

}
