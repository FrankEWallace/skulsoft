<?php

namespace App\Http\Resources\Auth;

use Illuminate\Http\Resources\Json\JsonResource;
use UAParser\Parser;

class FailedLoginAttemptResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array
     */
    public function toArray($request)
    {
        $parser = Parser::create();
        $userAgent = $this->getMeta('user_agent');
        if ($userAgent) {
            $result = $parser->parse($userAgent);
        }

        return [
            'uuid' => $this->uuid,
            'email' => $this->email,
            'ip' => $this->getMeta('ip'),
            'user_agent' => $userAgent,
            'browser' => $userAgent ? ($result->ua->family.' '.$result->ua->major) : null,
            'os' => $userAgent ? ($result->os->family.' '.$result->os->major) : null,
            'created_at' => \Cal::dateTime($this->created_at),
        ];
    }
}
