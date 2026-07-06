@props(['status'])

@php
    use App\Enums\ParasiteStatus;

    $enum = $status instanceof ParasiteStatus
        ? $status
        : (ParasiteStatus::tryFrom((string) $status) ?? ParasiteStatus::Intact);
@endphp

<span {{ $attributes->merge(['class' => 'inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium '.$enum->badgeClasses()]) }}>
    {{ $enum->label() }}
</span>
