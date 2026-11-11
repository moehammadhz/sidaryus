@props(['name', 'size' => 18])

<svg width="{{ $size }}" height="{{ $size }}" viewBox="0 0 24 24" fill="none" stroke="currentColor"
     stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" {{ $attributes }}>
    @switch($name)
        @case('home')       <path d="M3 11l9-8 9 8v9a1 1 0 0 1-1 1h-5v-6H9v6H4a1 1 0 0 1-1-1z"/> @break
        @case('checklist')  <path d="M9 6h11M9 12h11M9 18h11M4 6l1 1 2-2M4 12l1 1 2-2M4 18l1 1 2-2"/> @break
        @case('file')       <path d="M14 3H7a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h10a2 2 0 0 0 2-2V8z"/><path d="M14 3v5h5"/> @break
        @case('calendar')   <rect x="3" y="5" width="18" height="16" rx="2"/><path d="M3 10h18M8 3v4M16 3v4"/> @break
        @case('users')      <circle cx="9" cy="8" r="4"/><path d="M2 21c0-4 3-6 7-6s7 2 7 6"/><path d="M17 4a4 4 0 0 1 0 8M22 21c0-3-2-5-5-5.5"/> @break
        @case('cap')        <path d="M2 9l10-5 10 5-10 5z"/><path d="M6 11v5c0 1.5 3 3 6 3s6-1.5 6-3v-5"/> @break
        @case('userplus')   <circle cx="9" cy="8" r="4"/><path d="M2 21c0-4 3-6 7-6s7 2 7 6M19 8v6M16 11h6"/> @break
        @case('logout')     <path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4M16 17l5-5-5-5M21 12H9"/> @break
        @case('edit')       <path d="M4 20h4l10-10-4-4L4 16z"/><path d="m13 7 4 4"/> @break
        @case('info')       <circle cx="12" cy="12" r="9"/><path d="M12 11v5M12 8h.01"/> @break
        @case('clipboard')  <rect x="5" y="4" width="14" height="17" rx="2"/><path d="M9 4h6v3H9zM9 14l2 2 4-4"/> @break
        @case('check')      <circle cx="12" cy="12" r="9"/><path d="m8 12 3 3 5-6"/> @break
        @case('menu')       <path d="M4 6h16M4 12h16M4 18h16"/> @break
        @case('plus')       <path d="M12 5v14M5 12h14"/> @break
        @case('trash')      <path d="M4 7h16M10 11v6M14 11v6M6 7l1 13h10l1-13M9 7V4h6v3"/> @break
        @case('arrow-up')   <path d="m6 15 6-6 6 6"/> @break
        @case('arrow-down') <path d="m6 9 6 6 6-6"/> @break
        @case('eye')        <path d="M2 12s3.6-7 10-7 10 7 10 7-3.6 7-10 7S2 12 2 12z"/><circle cx="12" cy="12" r="3"/> @break
        @case('tick')       <path d="m5 12.5 4.5 4.5L19 7.5"/> @break
        @case('close')      <path d="M6 6l12 12M18 6 6 18"/> @break
        @case('clock')      <circle cx="12" cy="12" r="9"/><path d="M12 7v5l3 2"/> @break
        @case('upload')     <path d="M12 16V4M7 9l5-5 5 5M5 20h14"/> @break
        @case('send')       <path d="M22 2L11 13M22 2l-7 20-4-9-9-4z"/> @break
        @case('grip')
            <g fill="currentColor" stroke="none">
                <circle cx="9" cy="6" r="1.5"/><circle cx="15" cy="6" r="1.5"/>
                <circle cx="9" cy="12" r="1.5"/><circle cx="15" cy="12" r="1.5"/>
                <circle cx="9" cy="18" r="1.5"/><circle cx="15" cy="18" r="1.5"/>
            </g>
        @break
    @endswitch
</svg>