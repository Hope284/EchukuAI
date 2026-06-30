@php
    use App\Support\Security\SafeUrl;
@endphp

<div
    class="flex w-full gap-4 [--mask-from:2rem] [--mask-to:calc(100%-2rem)] md:[--mask-from:7rem] md:[--mask-to:calc(100%-7rem)]"
    style="mask-image: linear-gradient(to right, transparent, black var(--mask-from), black var(--mask-to), transparent);"
    x-data="marquee({ pauseOnHover: true })"
>
    <div class="lqd-marquee-viewport relative flex w-full overflow-hidden">
        <div class="lqd-marquee-slider flex w-full gap-4 overflow-x-auto py-2 lg:px-14">
            @foreach ($items ?? [] as $button)
                @php
                    $buttonTitle = trim((string) ($button?->name ?? ''));
                    $buttonUrl = SafeUrl::normalize($button?->prompt ?? null);
                    $isExternal = $buttonUrl && SafeUrl::isExternal($buttonUrl, request()->getHost());
                @endphp

                @continue($buttonTitle === '')

                @if ($buttonUrl)
                    <a
                        class="lqd-marquee-cell inline-flex shrink-0 items-center justify-center whitespace-nowrap rounded-xl bg-surface-background px-2.5 py-3 text-base font-semibold leading-[1.15em] transition-all hover:-translate-y-1 hover:shadow dark:bg-heading-foreground/5 dark:hover:bg-white lg:text-[1.2vw]"
                        href="{{ $buttonUrl }}"
                        @if ($isExternal)
                            target="_blank"
                            rel="noopener noreferrer"
                        @endif
                    >
                        <span class="bg-gradient-to-r from-gradient-from via-gradient-via to-gradient-to bg-clip-text text-transparent">
                            {{ __($buttonTitle) }}
                        </span>
                    </a>
                @else
                    <span
                        class="lqd-marquee-cell inline-flex shrink-0 cursor-not-allowed items-center justify-center whitespace-nowrap rounded-xl bg-surface-background px-2.5 py-3 text-base font-semibold leading-[1.15em] opacity-45 dark:bg-heading-foreground/5 lg:text-[1.2vw]"
                        aria-disabled="true"
                        title="{{ __('This legacy item needs a valid Button URL before it can be opened.') }}"
                    >
                        {{ __($buttonTitle) }}
                    </span>
                @endif
            @endforeach
        </div>
    </div>
</div>
