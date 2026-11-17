<?php

namespace App\Http\Controllers\Admin;

use App\Mail\SupportReplyMail;
use App\Models\SupportMessage;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Illuminate\View\View;

class SupportController extends AdminController
{
    public function index(Request $request): View
    {
        $status = in_array($request->query('status'), SupportMessage::STATUSES, true) ? $request->query('status') : null;

        return view('admin.support.index', [
            'messages' => SupportMessage::query()
                ->when($status, fn ($q) => $q->where('status', $status))
                ->withCount('replies')
                ->latest('id')
                ->paginate(25)
                ->withQueryString(),
            'status' => $status,
        ]);
    }

    public function show(SupportMessage $message): View
    {
        $message->load('replies.admin');
        $this->log('admin.support_viewed', $message);

        return view('admin.support.show', ['message' => $message]);
    }

    public function reply(Request $request, SupportMessage $message): RedirectResponse
    {
        $data = $request->validate(['body' => ['required', 'string', 'max:10000']]);

        $reply = $message->replies()->create(['admin_id' => $this->admin()->id, 'body' => trim($data['body'])]);
        Mail::to($message->email)->send(new SupportReplyMail($message, $reply));

        $message->forceFill(['status' => 'answered'])->save();
        $this->log('admin.support_replied', $message);

        return back()->with('status', 'Reply sent to '.$message->email.'.');
    }

    public function status(Request $request, SupportMessage $message): RedirectResponse
    {
        $data = $request->validate(['status' => ['required', 'in:'.implode(',', SupportMessage::STATUSES)]]);

        $message->forceFill(['status' => $data['status']])->save();
        $this->log('admin.support_status', $message, ['status' => $data['status']]);

        return back()->with('status', 'Status updated.');
    }
}
