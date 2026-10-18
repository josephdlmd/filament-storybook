{{-- From filament-storybook (feedback-error-pages). Change the words to fit the app; the layout comes from the package. --}}
@include('filament-storybook::errors.layout', [
    'code' => 403,
    'heading' => 'You don\'t have access to this page',
    'message' => 'Ask an administrator to give you access.',
])
