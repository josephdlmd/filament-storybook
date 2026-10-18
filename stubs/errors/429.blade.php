{{-- From filament-storybook (feedback-error-pages). Change the words to fit the app; the layout comes from the package. --}}
@include('filament-storybook::errors.layout', [
    'code' => 429,
    'heading' => 'Too many attempts',
    'message' => 'Wait a minute, then try again.',
])
