<?php

namespace App\Services\Discipline;

use App\Enums\Discipline\IncidentNature;
use App\Enums\Discipline\IncidentSeverity;
use App\Enums\OptionType;
use App\Http\Resources\OptionResource;
use App\Models\Discipline\Incident;
use App\Models\Option;
use Illuminate\Http\Request;

class IncidentService
{
    public function preRequisite(Request $request): array
    {
        $categories = OptionResource::collection(Option::query()
            ->byTeam()
            ->whereType(OptionType::INCIDENT_CATEGORY->value)
            ->get());

        $natures = IncidentNature::getOptions();

        $severities = IncidentSeverity::getOptions();

        return compact('categories', 'natures', 'severities');
    }

    public function create(Request $request): Incident
    {
        \DB::beginTransaction();

        $incident = Incident::forceCreate($this->formatParams($request));

        $incident->addMedia($request);

        \DB::commit();

        return $incident;
    }

    private function formatParams(Request $request, ?Incident $incident = null): array
    {
        $formatted = [
            'category_id' => $request->category_id,
            'title' => $request->title,
            'reported_by' => $request->reported_by,
            'date' => $request->date,
            'model_id' => $request->model_id,
            'model_type' => $request->model_type,
            'nature' => $request->nature,
            'severity' => $request->severity,
            'description' => clean($request->description),
            'action' => clean($request->action),
        ];

        if (! $incident) {
            $formatted['user_id'] = auth()->id();
            $formatted['period_id'] = auth()->user()->current_period_id;
        }

        $meta = $incident?->meta ?? [];

        $formatted['meta'] = $meta;

        return $formatted;
    }

    public function update(Request $request, Incident $incident): void
    {
        \DB::beginTransaction();

        $incident->forceFill($this->formatParams($request, $incident))->save();

        $incident->updateMedia($request);

        \DB::commit();
    }

    public function deletable(Incident $incident): void
    {
        //
    }
}
