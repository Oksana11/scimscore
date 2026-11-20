<?php

namespace App\Http\Controllers;

use App\Models\SupportMessage;
use App\Support\Security\AuditLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Public contact form. Messages are stored (encrypted) for administrators — not emailed.
 */
class SupportController extends Controller
{
    public function create(Request $request): View
    {
        return view('support.create', ['email' => $request->user()?->email]);
    }

    public function store(Request $request, AuditLogger $audit): RedirectResponse
    {
        // Honeypot: real users never see or fill this field.
        if (filled($request->input('website'))) {
            return redirect()->route('support')->with('status', 'Thank you! Your message has been sent. We will reply by email.');
        }

        $data = $request->validate([
            'email' => ['required', 'email:rfc', 'max:254'],
            'subject' => ['required', 'string', 'max:150'],
            'message' => ['required', 'string', 'min:10', 'max:5000'],
        ]);

        $msg = SupportMessage::query()->create([
            'user_id' => $request->user()?->id,
            'email' => mb_strtolower(trim($data['email'])),
            'subject' => trim($data['subject']),
            'message' => trim($data['message']),
        ]);
        $audit->log('support.message_received', $msg);

        return redirect()->route('support')->with('status', 'Thank you! Your message has been sent. We will reply by email.');
    }
}
