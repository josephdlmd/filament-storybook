{{-- From filament-storybook (feedback-error-pages). Change the words to fit the app; the layout comes from the package. --}}
@include('filament-storybook::errors.layout', [
    'code' => 404,
    'heading' => 'Page not found',
    'message' => 'Check the address for a typing mistake, or start again from the dashboard.',
])
