<?php

namespace App\Http\Resources\Utility;

use App\Http\Resources\UserSummaryResource;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Str;
use UAParser\Parser;

class ActivityResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array|\Illuminate\Contracts\Support\Arrayable|\JsonSerializable
     */
    public function toArray($request)
    {
        $parser = Parser::create();
        $userAgent = $this->getExtraProperty('user_agent');
        if ($userAgent) {
            $result = $parser->parse($userAgent);
        }

        return [
            'uuid' => (string) Str::uuid(),
            'activity' => trans('global.'.$this->description, ['attribute' => trans('module.'.$this->log_name)]),
            'properties' => $this->properties,
            'ip' => $this->getExtraProperty('ip'),
            'browser' => $userAgent ? ($result->ua->family.' '.$result->ua->major) : null,
            'os' => $userAgent ? ($result->os->family.' '.$result->os->major) : null,
            'user' => $this->causer_id ? UserSummaryResource::make($this->causer) : null,
            'created_at' => \Cal::dateTime($this->created_at),
        ];
    }
}
