<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <title>{{ $document['title'] }} {{ $document['number'] }}</title>
    <style>
        @page { size: A4; margin: 15mm; }
        body { margin: 0; }
    </style>
</head>
<body>
    @include('filament-storybook::documents.document')
</body>
</html>
