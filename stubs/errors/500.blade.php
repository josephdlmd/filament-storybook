{{-- From filament-storybook (feedback-error-pages). Change the words to fit the app; the layout comes from the package. --}}
@include('filament-storybook::errors.layout', [
    'code' => 500,
    'heading' => 'Something went wrong',
    'message' => 'Try again in a few minutes. If it keeps happening, tell an administrator.',
])
