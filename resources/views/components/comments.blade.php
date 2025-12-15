@props([
    'model',
    'approvedOnly' => true,
])

{{-- hi
@php
    $comments = $model->comments;
    if ($approvedOnly) {
        $comments = $comments->where('is_approved', true);
    }
@endphp

<div {{ $attributes->class(['codenzia-comments-list space-y-6']) }}>
    @foreach ($comments as $comment)
       
    @endforeach
</div> --}}
