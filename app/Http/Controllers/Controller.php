<?php

namespace App\Http\Controllers;

abstract class Controller
{
    protected function isAdmin(): bool
    {
        return auth()->user()?->hasAnyRole(['Admin', 'Super Admin']) ?? false;
    }

    protected function adminOnly(): void
    {
        abort_unless($this->isAdmin(), 403);
    }
}
