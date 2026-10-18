@php
    // An error can come from outside every panel's routes, such as a 404 for an address no route has: the page then takes
    // the default panel's theme and logo.
    if (! filament()->getCurrentPanel()) {
        filament()->setCurrentPanel(filament()->getDefaultPanel());
    }

    // Filament's layout takes the page's title from its Livewire page; an error page has none, so this stands in for it.
    $page = new class($heading)
    {
        public function __construct(public string $heading) {}

        public function getTitle(): string
        {
            return $this->heading;
        }

        public function getRenderHookScopes(): array
        {
            return [];
        }

        public function getExtraBodyAttributes(): array
        {
            return [];
        }
    };
@endphp

{{-- As data, not attributes: the layout reads the page and the top bar's switch as variables, as Filament's own pages pass them. --}}
@component('filament-panels::components.layout.simple', ['livewire' => $page, 'hasTopbar' => false])
    @include('filament-storybook::errors.page')
@endcomponent
