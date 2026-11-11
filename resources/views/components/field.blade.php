@props([
    'name',
    'label',
    'type' => 'text',
    'placeholder' => '',
    'autocomplete' => 'off',
    'icon' => null,
    'toggle' => false,
    'autofocus' => false,
    'as' => 'input',     // 'input' | 'select' | 'combo'
    'options' => [],     // untuk select dan combo
    'digits' => null,    // isi angka (mis. 10) jika hanya boleh angka dengan panjang tetap
])

<div class="field @error($name) field--invalid @enderror">
    <label class="field__label" for="{{ $name }}">{{ $label }}</label>

    <div class="field__box">
        @if ($icon === 'user')
            <span class="field__icon" aria-hidden="true">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="8" r="4"/><path d="M4 21c0-4 3.6-7 8-7s8 3 8 7"/></svg>
            </span>
        @elseif ($icon === 'lock')
            <span class="field__icon" aria-hidden="true">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><rect x="4" y="11" width="16" height="10" rx="2"/><path d="M8 11V8a4 4 0 0 1 8 0v3"/></svg>
            </span>
        @endif

        @if ($as === 'select')
            <select class="field__input field__select" id="{{ $name }}" name="{{ $name }}" required>
                <option value="" disabled @selected(old($name) === null || old($name) === '')>{{ $placeholder }}</option>
                @foreach ($options as $opt)
                    <option value="{{ $opt }}" @selected((string) old($name) === (string) $opt)>{{ $opt }}</option>
                @endforeach
            </select>
        @elseif ($as === 'combo')
            <input class="field__input field__select field__combo"
                   id="{{ $name }}" name="{{ $name }}" type="text"
                   list="{{ $name }}-list" inputmode="numeric" maxlength="4"
                   pattern="[0-9]{4}" autocomplete="off" data-digits
                   value="{{ old($name) }}" placeholder="{{ $placeholder }}" required>
            <datalist id="{{ $name }}-list">
                @foreach ($options as $opt)
                    <option value="{{ $opt }}"></option>
                @endforeach
            </datalist>
        @else
            <input class="field__input"
                   id="{{ $name }}" name="{{ $name }}" type="{{ $type }}"
                   value="{{ $type === 'password' ? '' : old($name) }}"
                   placeholder="{{ $placeholder }}" autocomplete="{{ $autocomplete }}"
                   @if ($digits) inputmode="numeric" pattern="[0-9]{{ '{' . $digits . '}' }}" maxlength="{{ $digits }}" data-digits @endif
                   @if ($autofocus) autofocus @endif required>

            @if ($toggle)
                <button class="field__toggle" type="button" data-toggle="{{ $name }}" aria-label="Tampilkan password">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M2 12s3.6-7 10-7 10 7 10 7-3.6 7-10 7S2 12 2 12z"/><circle cx="12" cy="12" r="3"/></svg>
                </button>
            @endif
        @endif

        @if ($as !== 'input')
            <span class="field__chevron" aria-hidden="true">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m6 9 6 6 6-6"/></svg>
            </span>
        @endif
    </div>

    @error($name)
        <p class="error" role="alert">{{ $message }}</p>
    @enderror
</div>