<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AccountStatusController extends Controller
{
    public function pending(Request $request): View
    {
        return view('auth.pending', ['user' => $request->user()]);
    }

    public function blocked(Request $request): View
    {
        return view('auth.blocked', ['user' => $request->user()]);
    }
}
