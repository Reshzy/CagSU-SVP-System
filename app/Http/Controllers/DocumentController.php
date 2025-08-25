<?php

namespace App\Http\Controllers;

use App\Models\Document;
use Illuminate\Support\Facades\Storage;

class DocumentController extends Controller
{
    /**
     * Stream or download a stored document from the public disk.
     */
    public function show(Document $document)
    {
        if (!Storage::disk('public')->exists($document->file_path)) {
            abort(404);
        }

        $mimeType = $document->mime_type ?: Storage::disk('public')->mimeType($document->file_path);

        $fileName = $document->file_name ?: basename($document->file_path);

        $lowerName = strtolower($fileName);
        $forceDownload = str_ends_with($lowerName, '.docx') || str_ends_with($lowerName, '.xlsx') || str_ends_with($lowerName, '.pptx');

        $disposition = $forceDownload ? 'attachment' : 'inline';

        return Storage::disk('public')->download($document->file_path, $fileName, [
            'Content-Type' => $mimeType,
            'Content-Disposition' => $disposition . '; filename="' . $fileName . '"',
        ]);
    }
}


