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
        return 'Every upload says what it accepts before anyone picks a file: the types, the largest size, and both in the helper text. Once uploaded, a file is shown by the name it was uploaded with: Filament stores it under a random name, so keep the original in a column of its own with `storeFileNamesIn()` (a `supporting_document_file_name` column here). An image shown as a picture, such as an avatar, needs no name. Basis: USWDS file input (2026), Carbon file uploader (Sep 2026: the item shows "The name of the file that has been uploaded"), Filament docs (always set accepted types).';
    }

    public function default(): FileUpload
    {
        return FileUpload::make('supporting_document')
            ->label('Supporting document (optional)')
            ->acceptedFileTypes(['application/pdf', 'image/jpeg'])
            ->maxSize(10240)
            ->helperText('PDF or JPG, up to 10 MB')
            // Shown again by the name it was uploaded with, not the random one Filament stores it under (Carbon file uploader).
            ->storeFileNamesIn('supporting_document_file_name')
            // The server's errors, worded as GOV.UK's file upload asks. FilePond's own messages in the browser ("File of
            // invalid type") are in Filament's bundled script and can't be reworded from outside it.
            ->validationMessages([
                'mimetypes' => 'The selected file must be a PDF or JPG',
                'max' => 'The selected file must be smaller than 10 MB',
            ]);
    }
}
