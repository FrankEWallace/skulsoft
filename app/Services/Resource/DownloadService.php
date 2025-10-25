<?php

namespace App\Services\Resource;

use App\Enums\Employee\AudienceType as EmployeeAudienceType;
use App\Enums\Student\AudienceType as StudentAudienceType;
use App\Helpers\CalHelper;
use App\Models\Employee\Employee;
use App\Models\Resource\Download;
use App\Support\HasAudience;
use Illuminate\Http\Request;

class DownloadService
{
    use HasAudience;

    public function preRequisite(Request $request): array
    {
        $studentAudienceTypes = StudentAudienceType::getOptions();

        $employeeAudienceTypes = EmployeeAudienceType::getOptions();

        return compact('studentAudienceTypes', 'employeeAudienceTypes');
    }

    public function create(Request $request): Download
    {
        \DB::beginTransaction();

        $download = Download::forceCreate($this->formatParams($request));

        $this->storeAudience($download, $request->all());

        $download->addMedia($request);

        \DB::commit();

        return $download;
    }

    private function formatParams(Request $request, ?Download $download = null): array
    {
        $formatted = [
            'title' => $request->title,
            'description' => $request->description,
            'published_at' => now()->toDateTimeString(),
            'expires_at' => $request->expires_at ? CalHelper::storeDateTime($request->expires_at)->toDateTimeString() : null,
            'audience' => [
                'student_type' => $request->student_audience_type,
                'employee_type' => $request->employee_audience_type,
            ],
        ];

        if (! $download) {
            $formatted['team_id'] = auth()->user()->current_team_id;
            $formatted['employee_id'] = Employee::auth()->first()?->id;
        }

        return $formatted;
    }

    public function update(Request $request, Download $download): void
    {
        \DB::beginTransaction();

        $download->forceFill($this->formatParams($request, $download))->save();

        $this->updateAudience($download, $request->all());

        $download->updateMedia($request);

        \DB::commit();
    }

    public function deletable(Download $download): void
    {
        //
    }
}
