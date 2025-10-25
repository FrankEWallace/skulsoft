<?php

namespace App\Services\Academic;

use App\Models\Academic\Session;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class SessionService
{
    public function preRequisite(): array
    {
        return [];
    }

    public function findByUuidOrFail(string $uuid): Session
    {
        return Session::query()
            ->byTeam()
            ->findByUuidOrFail($uuid, trans('academic.session.session'), 'message');
    }

    public function create(Request $request): Session
    {
        \DB::beginTransaction();

        $session = Session::forceCreate($this->formatParams($request));

        \DB::commit();

        return $session;
    }

    private function formatParams(Request $request, ?Session $session = null): array
    {
        $formatted = [
            'name' => $request->name,
            'code' => $request->code,
            'shortcode' => $request->shortcode,
            'alias' => $request->alias,
            'start_date' => $request->start_date,
            'end_date' => $request->end_date,
            'description' => $request->description,
        ];

        $config = $session?->config ?? [];
        $formatted['config'] = $config;

        if (! $session) {
            $formatted['is_default'] = $request->boolean('is_default');
            $formatted['team_id'] = auth()->user()?->current_team_id;
        }

        return $formatted;
    }

    public function update(Request $request, Session $session): void
    {
        \DB::beginTransaction();

        $session->forceFill($this->formatParams($request, $session))->save();

        \DB::commit();
    }

    public function deletable(Session $session, $validate = false): ?bool
    {
        $periodExists = \DB::table('periods')
            ->whereSessionId($session->id)
            ->exists();

        if ($periodExists) {
            throw ValidationException::withMessages(['message' => trans('global.associated_with_dependency', ['attribute' => trans('academic.session.session'), 'dependency' => trans('academic.period.period')])]);
        }

        return true;
    }
}
