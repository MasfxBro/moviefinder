@props(['plans'])

<div class="grid grid-cols-1 md:grid-cols-3 gap-8">
    @foreach($plans as $name => $data)
        <div class="relative flex flex-col p-8 bg-surface rounded-2xl border {{ $name === 'plus' ? 'border-accent shadow-[0_0_40px_rgba(10,132,255,0.1)]' : 'border-border' }}">
            @if($name === 'plus')
                <div class="absolute -top-4 left-1/2 -translate-x-1/2 px-4 py-1 bg-accent text-white text-xs font-bold rounded-full">
                    MOST POPULAR
                </div>
            @endif

            <h3 class="text-2xl font-bold capitalize text-text">{{ $name }}</h3>
            <div class="mt-4 flex items-baseline">
                @if($data['price'] === 0)
                    <span class="text-4xl font-bold">Free</span>
                @else
                    <span class="text-4xl font-bold text-text">Rp {{ number_format($data['price'], 0, ',', '.') }}</span>
                    <span class="text-muted ml-2 text-sm">/ month</span>
                @endif
            </div>

            <ul class="mt-10 space-y-5 flex-1">
                <li class="flex items-start text-sm text-muted">
                    <svg class="w-5 h-5 text-accent mr-3 mt-0.5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"></path></svg>
                    <span><strong>{{ $data['daily_units'] }}</strong> AI searches daily</span>
                </li>
                @if($data['smart_searches'] > 0)
                    <li class="flex items-start text-sm text-muted">
                        <svg class="w-5 h-5 text-accent mr-3 mt-0.5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"></path></svg>
                        <span><strong>{{ $data['smart_searches'] }}</strong> Smart searches daily</span>
                    </li>
                @endif
                <li class="flex items-start text-sm text-muted">
                    <svg class="w-5 h-5 text-accent mr-3 mt-0.5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"></path></svg>
                    <span>Up to <strong>{{ $data['results_per_answer'] }}</strong> results per query</span>
                </li>
                <li class="flex items-start text-sm text-muted">
                    <svg class="w-5 h-5 text-accent mr-3 mt-0.5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"></path></svg>
                    <span>Official clip previews</span>
                </li>
            </ul>

            <div class="mt-10">
                <div class="text-[10px] font-bold text-faint uppercase tracking-widest mb-3">ACCEPTED METHODS</div>
                <x-payment-methods :methods="$data['payment_methods']" />
            </div>

            <x-button
                variant="{{ $name === 'plus' ? 'primary' : 'secondary' }}"
                class="mt-10 w-full"
                onclick="window.location.href='{{ route('register') }}'"
            >
                @if($name === 'free')
                    Start for free
                @else
                    Choose {{ ucfirst($name) }}
                @endif
            </x-button>
        </div>
    @endforeach
</div>
