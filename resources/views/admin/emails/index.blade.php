@extends('admin.layout')
@section('title', 'Emails')
@section('content')
    <h1 class="text-2xl font-semibold">Emails</h1>
    <p class="mt-1 text-sm text-slate-500">Texts of the emails the site sends. Plain text only — links and email addresses become clickable automatically.</p>

    <div class="mt-6 grid gap-6 lg:grid-cols-2">
        <section class="card">
            <h2 class="font-semibold">Templates</h2>
            <table class="mt-3 w-full text-sm">
                @foreach ($definitions as $key => $def)
                    <tr class="border-b border-slate-100">
                        <td class="py-2.5">
                            <a href="{{ route('admin.emails.edit', $key) }}" class="font-semibold text-brand hover:underline">{{ $def['label'] }}</a>
                            <p class="text-xs text-slate-500">{{ $def['description'] }}</p>
                        </td>
                        <td class="py-2.5 text-right text-xs whitespace-nowrap {{ isset($customized[$key]) ? 'text-amber-700' : 'text-slate-400' }}">
                            {{ isset($customized[$key]) ? 'Edited '.$customized[$key]->format('j M Y') : 'Default' }}
                        </td>
                    </tr>
                @endforeach
            </table>
        </section>

        <section class="card">
            <h2 class="font-semibold">Common settings</h2>
            <form method="POST" action="{{ route('admin.emails.settings') }}" class="mt-3 space-y-4">@csrf
                <div>
                    <label for="support_email" class="label">Support email</label>
                    <input id="support_email" name="support_email" type="email" class="input" maxlength="254" required value="{{ old('support_email', $supportEmail) }}">
                    <p class="hint">Used as {support_email} in emails and in the site footer.</p>
                    @error('support_email') <p class="field-error">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label for="email_footer" class="label">Footer of every email</label>
                    <textarea id="email_footer" name="email_footer" rows="4" class="input" maxlength="1000">{{ old('email_footer', $footer) }}</textarea>
                    <p class="hint">Placeholders: {support_email}, {app_name}, {site_url}</p>
                    @error('email_footer') <p class="field-error">{{ $message }}</p> @enderror
                </div>
                <button type="submit" class="btn-primary">Save</button>
            </form>
        </section>
    </div>
@endsection
