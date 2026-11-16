<footer class="border-t border-line bg-white">
    <div class="mx-auto flex max-w-6xl flex-col items-center justify-between gap-3 px-6 py-5 text-sm text-slate-500 sm:flex-row">
        <p>© {{ date('Y') }} SCIMSCORE · Loewenstein Rehabilitation Medical Center. All rights reserved.</p>
        <nav class="flex gap-6 font-semibold text-slate-700">
            <a href="{{ route('terms') }}" class="hover:text-ink">Terms of Use</a>
            <a href="mailto:{{ \App\Models\Setting::supportEmail() }}" class="hover:text-ink">Contact support</a>
        </nav>
    </div>
</footer>
