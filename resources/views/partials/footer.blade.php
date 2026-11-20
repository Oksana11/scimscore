<footer class="border-t border-line bg-white">
    <div class="mx-auto flex max-w-6xl flex-col items-center justify-between gap-3 px-6 py-5 text-sm text-slate-500 sm:flex-row">
        <p>© {{ date('Y') }} SCIMSCORE · Loewenstein Rehabilitation Medical Center. All rights reserved.</p>
        <nav class="flex flex-wrap justify-center gap-x-6 gap-y-2 font-semibold text-slate-700">
            @foreach (\App\Models\Page::menu('footer') as $item)
                <a href="{{ $item['url'] }}" class="hover:text-ink">{{ $item['title'] }}</a>
            @endforeach
            <a href="{{ route('support') }}" class="hover:text-ink">Contact support</a>
        </nav>
    </div>
</footer>
