<?php

namespace Josephdlmd\FilamentStorybook\Stories;

use Filament\Forms\Components\FileUpload;
use Josephdlmd\FilamentStorybook\Stories;

class FileUploadStories extends Stories
{
    public function title(): string
    {
        return 'Forms/File upload';
    }

    public function description(): string
    {
        return 'Every upload says what it accepts before anyone picks a file: the types, the largest size, and both in the helper text. Basis: USWDS file input (2026), Carbon file uploader, Filament docs (always set accepted types).';
    }

    public function default(): FileUpload
    {
        return FileUpload::make('supporting_document')
            ->acceptedFileTypes(['application/pdf', 'image/jpeg'])
            ->maxSize(10240)
            ->helperText('PDF or JPG, up to 10 MB');
    }
}
