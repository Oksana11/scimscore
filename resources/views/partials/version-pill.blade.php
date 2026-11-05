@if ($version)<span class="{{ $version === \App\Enums\ScimVersion::IV ? 'pill-iv' : 'pill' }}">SCIM {{ $version->value }}</span>@else<span class="text-slate-300">—</span>@endif
