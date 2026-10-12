@props(['user', 'size' => 40, 'ring' => false])

@php
    // Reads from the single source of truth: User::avatar_url (users.avatar + ?v=).
    $url      = $user->avatar_url;
    $ringCss  = $ring ? 'border:3px solid rgba(255, 255, 255, .65);' : '';
    $imgStyle = "width:{$size}px;height:{$size}px;object-fit:cover;border-radius:50%;{$ringCss}";
    $iconStyle = "width:{$size}px;height:{$size}px;border-radius:50%;font-size:" . round($size * 0.9) . "px;line-height:1;{$ringCss}";
@endphp

<span data-avatar-wrap
      data-avatar-size="{{ $size }}"
      data-avatar-ring="{{ $ring ? '1' : '0' }}"
      data-avatar-name="{{ $user->name }}"
      {{ $attributes->merge(['class' => 'd-inline-flex align-items-center justify-content-center']) }}>
    @if ($url)
        <img src="{{ $url }}"
             alt="{{ $user->name }}"
             class="rounded-circle object-fit-cover"
             style="{{ $imgStyle }}">
    @else
        <i class="bi bi-person-circle text-secondary"
           aria-label="{{ $user->name }}"
           style="{{ $iconStyle }};display:inline-flex;align-items:center;justify-content:center"></i>
    @endif
</span>
