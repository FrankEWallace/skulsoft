<?php

namespace App\Http\Resources\Exam;

use App\Enums\Exam\OnlineExamQuestionType;
use Illuminate\Http\Resources\Json\JsonResource;

class OnlineExamQuestionResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array
     */
    public function toArray($request)
    {
        return [
            'uuid' => $this->uuid,
            'type' => OnlineExamQuestionType::getDetail($this->type),
            'mark' => $this->mark,
            'title' => $this->title,
            'header' => $this->header,
            'options' => $this->options,
            'position' => $this->position,
            'created_at' => \Cal::dateTime($this->created_at),
            'updated_at' => \Cal::dateTime($this->updated_at),
        ];
    }
}
