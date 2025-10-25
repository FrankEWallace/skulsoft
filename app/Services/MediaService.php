<?php

namespace App\Services;

use App\Concerns\HasStorage;
use App\Models\Media;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class MediaService
{
    use HasStorage;

    private function getMediaInfo(string $module = ''): array
    {
        $mediaInfo = collect(Arr::getVar('media'))->where('module', $module)->first();

        if (! $mediaInfo) {
            $mediaInfo = collect(Arr::getVar('media'))->where('module', 'general')->first();
        }

        return [
            'max_file_size' => Arr::get($mediaInfo, 'max_file_size', 5),
            'max_no_of_files' => Arr::get($mediaInfo, 'max_no_of_files', 5),
            'allowed_extensions' => Arr::get($mediaInfo, 'allowed_extensions', ['jpg', 'jpeg', 'webp', 'png', 'pdf', 'txt', 'doc', 'docx', 'xls', 'xlsx', 'ppt', 'pptx']),
        ];
    }

    public function store(Request $request)
    {
        $this->validateToken($request);
        $this->validateFileSize($request);
        $this->validateFileExtension($request);
        $this->validateFileLimit($request);
        $this->validateFileDuplication($request);

        $file = $this->uploadFile(
            path: 'media/'.$request->module,
            input: 'file'
        );

        return Media::forceCreate([
            'token' => $request->token,
            'name' => $file,
            'collection' => 'default',
            'file_name' => $request->file('file')->getClientOriginalName(),
            'user_id' => auth()?->id(),
            'model_type' => Str::studly($request->module),
            'meta' => [
                'section' => strtolower($request->section),
                'mime' => $request->file('file')->getClientMimeType(),
                'size' => $request->file('file')->getSize(),
                'is_temp_deleted' => false,
                'hash' => $request->hash,
            ],
        ]);
    }

    public function delete(Request $request, Media $media)
    {
        if (auth()->check()) {
            if (! auth()->user()->hasRole('admin') && $media->user_id != auth()->id()) {
                throw ValidationException::withMessages(['message' => trans('user.errors.permission_denied')]);
            }
        } else {
            // delete only of current hash
        }

        if (! $media->status) {
            $this->deleteFile(path: $media->name);
            $media->delete();
        }

        if ($media->status) {
            $media->setMeta([
                'is_temp_deleted' => true,
                'delete_hash' => $request->query('hash'),
            ]);
            $media->save();
        }
    }

    private function validateToken(Request $request)
    {
        $existingToken = Media::query()
            ->whereToken($request->token)
            ->where('model_type', '!=', Str::studly($request->module))
            ->count();

        if ($existingToken) {
            throw ValidationException::withMessages(['message' => trans('general.errors.invalid_action')]);
        }
    }

    private function validateFileSize(Request $request)
    {
        $maxFileSize = Arr::get($this->getMediaInfo($request->module), 'max_file_size', 5);
        $size = $request->file('file')->getSize();

        if ($size > $maxFileSize * 1024 * 1024) {
            throw ValidationException::withMessages(['message' => trans('general.errors.file_too_large')]);
        }
    }

    private function validateFileExtension(Request $request)
    {
        $allowedFileExtensions = Arr::get($this->getMediaInfo($request->module), 'allowed_extensions', 5);
        $extension = $request->file('file')->getClientOriginalExtension();

        if (! in_array($extension, $allowedFileExtensions)) {
            throw ValidationException::withMessages(['message' => trans('general.errors.file_not_supported')]);
        }
    }

    private function validateFileLimit(Request $request)
    {
        $maxNoOfFiles = Arr::get($this->getMediaInfo($request->module), 'max_no_of_files', 5);

        $existingMedia = Media::query()
            ->whereToken($request->token)
            ->whereModelType(Str::studly($request->module))
            // ->where('meta->hash', $request->hash) // Commented to fix file limit check whatever be the hash
            ->where('meta->is_temp_deleted', false)
            ->count();

        if ($existingMedia >= $maxNoOfFiles) {
            throw ValidationException::withMessages(['message' => trans('general.errors.file_upload_limit_exceeded')]);
        }
    }

    private function validateFileDuplication(Request $request)
    {
        $duplicateMedia = Media::query()
            ->whereToken($request->token)
            ->whereModelType(Str::studly($request->module))
            ->where(function ($q) use ($request) {
                $q->whereStatus(1)
                    ->orWhere(function ($q) use ($request) {
                        $q->whereStatus(0)->where('meta->hash', $request->hash);
                    });
            })
            ->whereFileName($request->file('file')->getClientOriginalName())
            ->count();

        if ($duplicateMedia) {
            throw ValidationException::withMessages(['message' => trans('general.errors.file_with_same_name_uploaded')]);
        }
    }
}
