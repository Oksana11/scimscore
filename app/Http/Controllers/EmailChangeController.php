<?php

namespace App\Http\Controllers;

use App\Services\Auth\EmailChangeService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class EmailChangeController extends Controller
{
    public function __construct(private readonly EmailChangeService $service) {}

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate(['new_email' => ['required', 'string', 'email:rfc', 'max:254']]);
        $email = mb_strtolower(trim($data['new_email']));

        if ($this->service->isTaken($email)) {
            return back()->withErrors(['new_email' => __('This email address cannot be used.')]);
        }

        $this->service->start($request->user(), $email);

        return redirect()->route('profile.edit')->with('status', __('We sent a code to the new address. Enter it below to confirm.'));
    }

    public function confirm(Request $request): RedirectResponse
    {
        $data = $request->validate(['code' => ['required', 'string', 'max:20']]);

        return match ($this->service->confirm($request->user(), $data['code'])) {
            'ok' => redirect()->route('profile.edit')->with('status', __('Your email address has been changed.')),
            'taken' => back()->withErrors(['code' => __('This email address cannot be used.')]),
            default => back()->withErrors(['code' => __('The code is invalid or has expired.')]),
        };
    }
}
