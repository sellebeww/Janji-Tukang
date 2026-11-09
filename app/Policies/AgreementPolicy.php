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

        return $view->denied() ? $view : ($agreement->pendingVersion() ? Response::allow() : Response::deny());
    }

    /** Addendum: hanya pembuat, hanya saat kesepakatan aktif atau pihak lawan sudah meminta perubahan. */
    public function amend(User $user, Agreement $agreement): Response
    {
        $view = $this->view($user, $agreement);

        return $view->denied() ? $view : (in_array($agreement->status, ['aktif', 'perlu_diubah'], true) ? Response::allow() : Response::deny('Kesepakatan belum bisa diubah.'));
    }
}
