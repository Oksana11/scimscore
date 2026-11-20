{{-- Expects: $versions, $version, $url (callable version => url) --}}
@if (count($versions) > 0)
    <nav class="inline-flex rounded-xl bg-slate-200/70 p-1 text-sm font-semibold" aria-label="SCIM version">
        @foreach (\App\Enums\ScimVersion::cases() as $v)
            @php $available = in_array($v, $versions, true); @endphp
            @if ($available)
                <a href="{{ $url($v) }}" @class(['rounded-lg px-4 py-1.5', 'bg-white text-ink shadow-sm' => $v === $version, 'text-slate-500 hover:text-ink' => $v !== $version])>SCIM {{ $v->value }}</a>
            @else
                <span class="cursor-not-allowed rounded-lg px-4 py-1.5 text-slate-400" title="Questionnaire not available yet">SCIM {{ $v->value }}</span>
            @endif
        @endforeach
    </nav>
@endif
