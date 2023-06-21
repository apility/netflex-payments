<div class="d-flex flex-column">
    <img
        class="img-fluid {{ $attributes->get('class') }}"
        src="{{ $qr }}"
        width="110"
        height="110"
        style="image-rendering: pixelated; width: 110; height: 110;"
    />
    @if($label || ($slot && $slot->isNotEmpty()))
        <span
            style="font-size: 0.90rem; font-family: monospace;"
            class="text-muted text-center"
        >
            @if($slot && $slot->isNotEmpty())
                {{ $slot }}
            @else
                {{ $string }}
            @endif
        </span>
    @endif
</div>
