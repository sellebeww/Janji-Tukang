<?php

namespace App\Policies;

use App\Models\Agreement;
use App\Models\User;
use Illuminate\Auth\Access\Response;

class AgreementPolicy
{
    /** Bukan pemilik: 404, supaya keberadaan data orang lain tidak bocor. */
    public function view(User $user, Agreement $agreement): Response
    {
        return $user->id === $agreement->user_id ? Response::allow() : Response::denyAsNotFound();
    }

    /** Ubah/ajukan hanya saat draf. */
    public function update(User $user, Agreement $agreement): Response
    {
        $view = $this->view($user, $agreement);

        return $view->denied() ? $view : ($agreement->isDraft() ? Response::allow() : Response::deny('Kesepakatan sudah diajukan.'));
    }

    public function link(User $user, Agreement $agreement): Response
    {
        $view = $this->view($user, $agreement);

        return $view->denied() ? $view : ($agreement->status === 'menunggu_konfirmasi' ? Response::allow() : Response::deny());
    }
}
