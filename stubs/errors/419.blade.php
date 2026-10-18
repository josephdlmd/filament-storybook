{{-- From filament-storybook (feedback-error-pages). Change the words to fit the app; the layout comes from the package. --}}
@include('filament-storybook::errors.layout', [
    'code' => 419,
    'heading' => 'This page has expired',
    'message' => 'Pages expire after a while for your security. Go back, reload the page and try again.',
])
