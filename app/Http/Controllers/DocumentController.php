<?php

namespace App\Http\Controllers;

use App\Models\Document;
use Illuminate\Support\Facades\Storage;

class DocumentController extends Controller
{
    /**
     * Stream or download a stored document from the public or local disk.
     */
    public function show(Document $document)
    {
        // Authorize document access
        $this->authorize('view', $document);

        // Determine which disk the file is stored on
        $disk = null;
        $filePath = $document->file_path;

        // Try public disk first (for uploaded files)
        if (Storage::disk('public')->exists($filePath)) {
            $disk = 'public';
        }
        // Try local disk (for generated BAC documents)
        elseif (Storage::disk('local')->exists($filePath)) {
            $disk = 'local';
        }

        // If file not found on either disk, abort
        if (! $disk) {
            abort(404, 'Document file not found in storage.');
        }

        // Get mime type
        $mimeType = $document->mime_type ?: Storage::disk($disk)->mimeType($filePath);

        // Get filename
        $fileName = $document->file_name ?: basename($filePath);

        // Force download for Office documents
        $lowerName = strtolower($fileName);
        $forceDownload = str_ends_with($lowerName, '.docx') || str_ends_with($lowerName, '.xlsx') || str_ends_with($lowerName, '.pptx');

        $disposition = $forceDownload ? 'attachment' : 'inline';

        return Storage::disk($disk)->download($filePath, $fileName, [
            'Content-Type' => $mimeType,
            'Content-Disposition' => $disposition.'; filename="'.$fileName.'"',
        ]);
    }
}
