@props([
    'paginator',
])

<div class="flex items-center justify-end border-t border-gray-200 bg-gray-50 px-2 py-2 text-xs">
    {{ $paginator->links(data: ['scrollTo' => false]) }}
</div>
