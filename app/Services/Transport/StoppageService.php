<?php

namespace App\Services\Transport;

use App\Models\Transport\Stoppage;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class StoppageService
{
    public function preRequisite(): array
    {
        return [];
    }

    public function findByUuidOrFail(string $uuid): Stoppage
    {
        return Stoppage::query()
            ->byPeriod()
            ->findByUuidOrFail($uuid, trans('transport.stoppage.stoppage'), 'message');
    }

    public function create(Request $request): Stoppage
    {
        \DB::beginTransaction();

        $stoppage = Stoppage::forceCreate($this->formatParams($request));

        \DB::commit();

        return $stoppage;
    }

    private function formatParams(Request $request, ?Stoppage $stoppage = null): array
    {
        $formatted = [
            'name' => $request->name,
            'description' => $request->description,
        ];

        if (! $stoppage) {
            $formatted['period_id'] = auth()->user()->current_period_id;
        }

        return $formatted;
    }

    public function update(Request $request, Stoppage $stoppage): void
    {
        \DB::beginTransaction();

        $stoppage->forceFill($this->formatParams($request, $stoppage))->save();

        \DB::commit();
    }

    public function deletable(Stoppage $stoppage, $validate = false): ?bool
    {
        $transportRouteExists = \DB::table('transport_route_stoppages')
            ->whereStoppageId($stoppage->id)
            ->exists();

        if ($transportRouteExists) {
            throw ValidationException::withMessages(['message' => trans('global.associated_with_dependency', ['attribute' => trans('transport.stoppage.stoppage'), 'dependency' => trans('transport.route.route')])]);
        }

        $passengerExists = \DB::table('transport_route_passengers')
            ->whereStoppageId($stoppage->id)
            ->exists();

        if ($passengerExists) {
            throw ValidationException::withMessages(['message' => trans('global.associated_with_dependency', ['attribute' => trans('transport.stoppage.stoppage'), 'dependency' => trans('transport.route.route')])]);
        }

        return true;
    }
}
