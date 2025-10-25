<?php

namespace App\Services\Transport;

use App\Models\Transport\Circle;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class CircleService
{
    public function preRequisite(): array
    {
        return [];
    }

    public function findByUuidOrFail(string $uuid): Circle
    {
        return Circle::query()
            ->byPeriod()
            ->findByUuidOrFail($uuid, trans('transport.circle.circle'), 'message');
    }

    public function create(Request $request): Circle
    {
        \DB::beginTransaction();

        $circle = Circle::forceCreate($this->formatParams($request));

        \DB::commit();

        return $circle;
    }

    private function formatParams(Request $request, ?Circle $circle = null): array
    {
        $formatted = [
            'name' => $request->name,
            'description' => $request->description,
        ];

        if (! $circle) {
            $formatted['period_id'] = auth()->user()->current_period_id;
        }

        return $formatted;
    }

    public function update(Request $request, Circle $circle): void
    {
        \DB::beginTransaction();

        $circle->forceFill($this->formatParams($request, $circle))->save();

        \DB::commit();
    }

    public function deletable(Circle $circle, $validate = false): ?bool
    {
        $transportFeeExists = \DB::table('transport_fee_records')
            ->whereTransportCircleId($circle->id)
            ->exists();

        if ($transportFeeExists) {
            throw ValidationException::withMessages(['message' => trans('global.associated_with_dependency', ['attribute' => trans('transport.circle.circle'), 'dependency' => trans('transport.fee.fee')])]);
        }

        $studentFeeExists = \DB::table('student_fees')
            ->whereTransportCircleId($circle->id)
            ->exists();

        if ($studentFeeExists) {
            throw ValidationException::withMessages(['message' => trans('global.associated_with_dependency', ['attribute' => trans('transport.circle.circle'), 'dependency' => trans('student.fee.fee')])]);
        }

        return true;
    }
}
