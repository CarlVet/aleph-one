@props([
    'id' => '',
    'length' => '',
    'theme' => '',
    'message' => '',
    'title' => '',
    'data' => '',
])

<div {{ $attributes->merge(['class' => 'col-md-6 ' . $length]) }}>
    <div {{ $attributes->merge(['class' => 'card text-center border-' . $theme]) }} data-tooltip="{{ $message }}">
        <div class="card-body" id={{ $id }}>
            <h4 class="card-title">{{ $title }}</h5>
            <p {{ $attributes->merge(['class' => 'card-text fs-3 text-' . $theme]) }}>{{ $data }}</p>
        </div>
    </div>
</div>