<div>
    @if (in_array($this->kind(), ['column', 'table'], true))
        {{ $this->table }}
    @else
        {{ $this->form }}
    @endif

    <x-filament-actions::modals />
</div>
