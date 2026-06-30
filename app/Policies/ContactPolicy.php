<?php

namespace App\Policies;

use App\Models\Contact;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class ContactPolicy
{
    use HandlesAuthorization;

    public function viewAny(User $user) { return true; }
    public function view(User $user, Contact $contact) { return true; }
    public function create(User $user) { return true; }
    public function update(User $user, Contact $contact) { return true; }
    public function delete(User $user, Contact $contact) { return true; }
    public function importMessages(User $user, Contact $contact) { return true; }
    public function runAnalysis(User $user, Contact $contact) { return true; }
    public function applyAnalysis(User $user, Contact $contact) { return true; }
    public function runMaintenance(User $user, Contact $contact) { return true; }
    public function export(User $user, Contact $contact) { return true; }
    public function erase(User $user, Contact $contact) { return true; }
}
