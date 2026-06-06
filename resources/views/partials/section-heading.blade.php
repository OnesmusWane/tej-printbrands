<div class="mb-16 text-center">
    <h2 class="relative mb-4 inline-block text-3xl font-extrabold text-slate-900 md:text-4xl">
        {{ $kicker }} <span class="text-primary">{{ $title }}</span>
        <span class="absolute -bottom-3 left-1/4 right-1/4 h-1 rounded-full {{ ($lightLine ?? false) ? 'bg-white' : 'bg-primary' }}"></span>
    </h2>
    @if (!empty($description))
        <p class="mx-auto mt-6 max-w-2xl text-lg leading-relaxed text-slate-600">{{ $description }}</p>
    @endif
</div>
