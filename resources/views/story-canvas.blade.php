<div>
    @if ($this->kind() === 'column')
        {{ $this->table }}
    @else
        {{ $this->form }}
    @endif

    <x-filament-actions::modals />
</div>
