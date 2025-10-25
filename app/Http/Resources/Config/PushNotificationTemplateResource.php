<?php

namespace App\Http\Resources\Config;

use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;

class PushNotificationTemplateResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array
     */
    public function toArray($request)
    {
        $templates = Arr::get(Arr::getVar('templates'), 'push');

        $template = Arr::first($templates, function ($item) {
            return $item['code'] == $this->code;
        });

        $variables = Arr::get($template, 'variables', []);

        return [
            'uuid' => $this->uuid,
            'name' => $this->name,
            'code' => $this->code,
            'subject' => $this->subject,
            'content' => $this->content,
            'variables' => $variables,
            'variables_display' => collect($variables)->map(function ($item) {
                return Str::of($item)->wrap('##');
            })->implode(', '),
            'enabled_at' => \Cal::dateTime($this->enabled_at),
            'created_at' => \Cal::dateTime($this->created_at),
            'updated_at' => \Cal::dateTime($this->updated_at),
        ];
    }
}
