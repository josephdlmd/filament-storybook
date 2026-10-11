{{-- Warns before an idle session ends, with time to stay logged in (WCAG 2.2.1). house.js times it and opens it. --}}
<div hidden data-fs-session-lifetime="{{ $lifetime }}" data-fs-session-warning="{{ $warning }}"></div>

<x-filament::modal
    id="fs-session-ending"
    :close-by-clicking-away="false"
    heading="You'll be logged out in {{ intdiv($warning, 60) }} minutes"
    description="You haven't done anything for a while. Anything you haven't saved will be lost."
    width="md"
>
    <x-slot name="footerActions">
        <form method="post" action="{{ filament()->getLogoutUrl() }}">
            @csrf
            <x-filament::button type="submit" color="gray">Log out</x-filament::button>
        </form>

        <x-filament::button x-on:click="$dispatch('close-modal', { id: 'fs-session-ending' })" autofocus>Stay logged in</x-filament::button>
    </x-slot>
</x-filament::modal>
