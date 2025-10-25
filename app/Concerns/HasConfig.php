<?php

namespace App\Concerns;

use Illuminate\Support\Arr;

trait HasConfig
{
    public static function bootHasConfig() {}

    public function getConfig(string $option, mixed $default = null)
    {
        return Arr::get($this->config, $option, $default);
    }

    public function setConfig(array $options = [], bool $save = false)
    {
        if (empty($options)) {
            return;
        }

        $config = $this->config ?? [];
        $config = array_merge($config, $options);
        $this->config = $config;

        if ($save) {
            $this->save();
        }
    }

    public function updateConfig(array $options = [])
    {
        $this->setConfig($options, true);
    }

    public function resetConfig(array $options = [])
    {
        if (empty($options)) {
            return;
        }

        $config = $this->config ?? [];

        foreach ($options as $option) {
            unset($config[$option]);
        }

        $this->config = $config;
    }
}
