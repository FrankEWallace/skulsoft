<?php

namespace App\Http\Resources\Library\Report;

use Illuminate\Http\Resources\Json\JsonResource;

class TopBorrowedBookListResource extends JsonResource
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
            'title' => $this->title,
            'author' => $this->author_name,
            'publisher' => $this->publisher_name,
            'count' => $this->count,
        ];
    }
}
