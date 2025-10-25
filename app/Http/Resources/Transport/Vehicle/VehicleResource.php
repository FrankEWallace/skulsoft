<?php

namespace App\Http\Resources\Transport\Vehicle;

use App\Enums\Transport\Vehicle\FuelType;
use App\Enums\Transport\Vehicle\Ownership;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Arr;

class VehicleResource extends JsonResource
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
            'name' => $this->name,
            'name_with_registration_number' => $this->name.' ('.Arr::get($this->registration, 'number').')',
            'registration_number' => Arr::get($this->registration, 'number'),
            'registration_date' => \Cal::date(Arr::get($this->registration, 'date')),
            'registration_place' => Arr::get($this->registration, 'place'),
            'model_number' => $this->model_number,
            'make' => $this->make,
            'class' => $this->class,
            'fuel_type' => FuelType::getDetail($this->fuel_type),
            'chassis_number' => Arr::get($this->registration, 'chassis_number'),
            'engine_number' => Arr::get($this->registration, 'engine_number'),
            'cubic_capacity' => Arr::get($this->registration, 'cubic_capacity'),
            'color' => Arr::get($this->registration, 'color'),
            'fuel_capacity' => $this->fuel_capacity,
            'seating_capacity' => $this->seating_capacity,
            'max_seating_allowed' => $this->max_seating_allowed,
            'ownership' => Ownership::getDetail(Arr::get($this->owner, 'ownership')),
            'owner_name' => Arr::get($this->owner, 'name'),
            'owner_address' => Arr::get($this->owner, 'address'),
            'owner_phone' => Arr::get($this->owner, 'phone'),
            'owner_email' => Arr::get($this->owner, 'email'),
            // 'driver_name' => Arr::get($this->driver, 'name'),
            // 'driver_address' => Arr::get($this->driver, 'address'),
            // 'driver_phone' => Arr::get($this->driver, 'phone'),
            // 'helper_name' => Arr::get($this->driver, 'helper_name'),
            // 'helper_address' => Arr::get($this->driver, 'helper_address'),
            // 'helper_phone' => Arr::get($this->driver, 'helper_phone'),
            // 'is_sold' => (bool) Arr::get($this->buyer, 'is_sold'),
            // $this->mergeWhen(Arr::get($this->buyer, 'is_sold'), [
            //     'buyer_name' => Arr::get($this->buyer, 'name'),
            //     'buyer_address' => Arr::get($this->buyer, 'address'),
            //     'buyer_phone' => Arr::get($this->buyer, 'phone'),
            //     'buyer_email' => Arr::get($this->buyer, 'email'),
            //     'selling_date' => \Cal::date(Arr::get($this->buyer, 'selling_date')),
            //     'selling_price' => \Price::from(Arr::get($this->buyer, 'selling_price')),
            // ]),
            'created_at' => \Cal::dateTime($this->created_at),
            'updated_at' => \Cal::dateTime($this->updated_at),
        ];
    }
}
