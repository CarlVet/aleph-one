<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class FileController extends Controller
{
    /**
     * Stream a stored file to authenticated users only.
     *
     * Replaces the framework's unauthenticated `storage/{path}` route so that
     * uploaded files (genomic sequences, patient/animal photos, project and
     * ethics documents) are never downloadable without a session.
     */
    public function show(Request $request, string $path): StreamedResponse
    {
        abort_if(str_contains($path, '..'), 404);

        $disk = Storage::disk('local');

        abort_unless($disk->exists($path), 404);

        return $disk->response($path);
    }
}
