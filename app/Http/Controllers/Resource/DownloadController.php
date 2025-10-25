<?php

namespace App\Http\Controllers\Resource;

use App\Http\Controllers\Controller;
use App\Http\Requests\Resource\DownloadRequest;
use App\Http\Resources\Resource\DownloadResource;
use App\Models\Resource\Download;
use App\Services\Resource\DownloadListService;
use App\Services\Resource\DownloadService;
use Illuminate\Http\Request;

class DownloadController extends Controller
{
    public function __construct()
    {
        $this->middleware('test.mode.restriction')->only(['destroy']);
    }

    public function preRequisite(Request $request, DownloadService $service)
    {
        return $service->preRequisite($request);
    }

    public function index(Request $request, DownloadListService $service)
    {
        $this->authorize('viewAny', Download::class);

        return $service->paginate($request);
    }

    public function store(DownloadRequest $request, DownloadService $service)
    {
        $this->authorize('create', Download::class);

        $download = $service->create($request);

        return response()->success([
            'message' => trans('global.created', ['attribute' => trans('resource.download.download')]),
            'download' => DownloadResource::make($download),
        ]);
    }

    public function show(Request $request, string $download, DownloadService $service)
    {
        $download = Download::findByUuidOrFail($download);

        $this->authorize('view', $download);

        $request->merge(['with_subjects' => true]);

        $download->load(['audiences.audienceable', 'employee' => fn ($q) => $q->summary(), 'media']);

        return DownloadResource::make($download);
    }

    public function update(DownloadRequest $request, string $download, DownloadService $service)
    {
        $download = Download::findByUuidOrFail($download);

        $this->authorize('update', $download);

        $service->update($request, $download);

        return response()->success([
            'message' => trans('global.updated', ['attribute' => trans('resource.download.download')]),
        ]);
    }

    public function destroy(string $download, DownloadService $service)
    {
        $download = Download::findByUuidOrFail($download);

        $this->authorize('delete', $download);

        $service->deletable($download);

        $download->delete();

        return response()->success([
            'message' => trans('global.deleted', ['attribute' => trans('resource.download.download')]),
        ]);
    }

    public function downloadMedia(string $download, string $uuid, DownloadService $service)
    {
        $download = Download::findByUuidOrFail($download);

        $this->authorize('view', $download);

        return $download->downloadMedia($uuid);
    }
}
