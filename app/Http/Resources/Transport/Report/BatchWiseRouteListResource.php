<?php

namespace App\Http\Resources\Transport\Report;

use App\Enums\Gender;
use App\Enums\Transport\Direction;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Arr;

class BatchWiseRouteListResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array
     */
    public function toArray($request)
    {
        $passengers = $request->passengers->where('model_id', $this->id);

        return [
            'uuid' => $this->uuid,
            'name' => $this->name,
            'father_name' => $this->father_name,
            'code_number' => $this->code_number,
            'joining_date' => \Cal::date($this->joining_date),
            'batch_name' => $this->batch_name,
            'course_name' => $this->course_name,
            'gender' => Gender::getDetail($this->gender),
            'birth_date' => \Cal::date($this->birth_date),
            'contact_number' => $this->contact_number,
            'passengers' => $passengers->map(function ($passenger) {
                $routeName = $passenger->route_name;
                $direction = Arr::get(Direction::getDetail($passenger->direction), 'label');

                if ($direction) {
                    $routeName = $routeName.' ('.$direction.')';
                }

                return [
                    'route_name' => $routeName,
                    'stoppage_name' => $passenger->stoppage_name,
                ];
            }),
        ];
    }
}
