{{-- From filament-storybook (feedback-error-pages). Change the words to fit the app; the layout comes from the package. --}}
@include('filament-storybook::errors.layout', [
    'code' => 503,
    'heading' => 'Down for maintenance',
    'message' => 'The app is being updated. Try again in a few minutes.',
    // The whole app is down, so the way out is to try again.
    'action' => 'Try again',
    'url' => url()->current(),
])
