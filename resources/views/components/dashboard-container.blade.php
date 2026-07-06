@props(['length' => '','title' => ''])

<div class="{{ $length }}">
    <div class="card border-secondary h-100">
        <div class="card-header text-center bg-light">
            <h5 class="card-title mb-0">{{ $title }}</h5>
        </div>
        <div class="card-body">
            {{ $slot }}
        </div>
    </div>
</div>