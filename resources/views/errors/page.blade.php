{{-- What went wrong in plain words, a way out, and the error's code kept small (feedback-error-pages). --}}
<div class="fi-simple-page">
    <div class="fi-simple-page-content">
        <x-filament-panels::header.simple :heading="$heading" :subheading="$message" />

        <x-filament::button tag="a" :href="$url ?? url(filament()->getCurrentOrDefaultPanel()?->getPath() ?? '/')">
            {{ $action ?? 'Go to the dashboard' }}
        </x-filament::button>

        <p class="fi-simple-header-subheading">Error {{ $code }}</p>
    </div>
</div>
