<div>
    {{-- Error and forbidden are the shared 500 and 403 pages (feedback-error-pages), in the screen's place. --}}
    @if (in_array($state, ['error', 'forbidden'], true))
        @php($page = (new \Josephdlmd\FilamentStorybook\Stories\ErrorPageStories)->{$state === 'error' ? 'serverError' : 'forbidden'}())
        @include($page->getView(), $page->getViewData())
    @elseif ($this->kind() === 'page')
        {{-- A whole Filament page, such as log in, as its own Livewire component. --}}
        @livewire($this->build()::class, key($stories.'.'.$story))
    @elseif ($state === 'loading' && in_array($this->kind(), ['column', 'table'], true))
        {{-- Mid-reload, held: Filament's skeleton class (loadingSkeleton(), a house default), which it sets only while a request runs. --}}
        <div aria-busy="true" x-data x-init="$el.querySelector('.fi-ta-content-ctn')?.classList.add('fi-ta-content-loading')">
            {{ $this->table }}
        </div>
    @elseif (in_array($this->kind(), ['column', 'table'], true))
        {{ $this->table }}
    @elseif ($state === 'loading' && ($widgets = $this->lazyWidgets($this->form)) !== [])
        @foreach ($widgets as $widget)
            {{ $widget }}
        @endforeach
    @else
        {{ $this->form }}
    @endif

    <x-filament-actions::modals />
</div>
