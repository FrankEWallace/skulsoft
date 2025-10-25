<?php

namespace App\Concerns;

use Illuminate\Support\Arr;

trait HasMeta
{
    public static function bootHasMeta() {}

    public function getMeta(string $option, mixed $default = null)
    {
        return Arr::get($this->meta, $option, $default);
    }

    public function setMeta(array $options = [], bool $save = false)
    {
        if (empty($options)) {
            return;
        }

        $meta = $this->meta ?? [];
        $meta = array_merge($meta, $options);
        $this->meta = $meta;

        if ($save) {
            $this->save();
        }
    }

    public function updateMeta(array $options = [])
    {
        $this->setMeta($options, true);
    }

    public function resetMeta(array $options = [])
    {
        if (empty($options)) {
            return;
        }

        $meta = $this->meta ?? [];

        foreach ($options as $option) {
            unset($meta[$option]);
        }

        $this->meta = $meta;
    }
}
