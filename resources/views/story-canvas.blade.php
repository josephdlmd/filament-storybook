<div>
    {{-- Error and forbidden are the shared 500 and 403 pages (feedback-error-pages), in the screen's place. --}}
    @if (in_array($state, ['error', 'forbidden'], true))
        @php($page = (new \Josephdlmd\FilamentStorybook\Stories\ErrorPageStories)->{$state === 'error' ? 'serverError' : 'forbidden'}())
        @include($page->getView(), $page->getViewData())
    @elseif (in_array($this->kind(), ['column', 'table'], true))
        {{ $this->table }}
    @else
        {{ $this->form }}
    @endif

    <x-filament-actions::modals />
</div>
