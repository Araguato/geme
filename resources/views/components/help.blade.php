@props(['title' => 'Ayuda', 'text'])
<button type="button" class="btn btn-link p-0 ms-1 align-baseline text-secondary geme-help"
        data-bs-toggle="popover" data-bs-trigger="focus" data-bs-placement="top"
        data-bs-title="{{ $title }}" data-bs-content="{{ $text }}"
        aria-label="Ayuda: {{ $title }}" title="Ayuda">
    <i class="bi bi-question-circle"></i>
</button>
