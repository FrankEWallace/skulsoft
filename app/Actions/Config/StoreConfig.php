<?php

namespace App\Actions\Config;

use App\Helpers\SysHelper;
use App\Lists\ConfigType;
use App\Models\Config\Config;
use Illuminate\Support\Arr;
use Illuminate\Validation\ValidationException;

class StoreConfig
{
    public function execute($params = [])
    {
        $type = Arr::get($params, 'type');

        if (! in_array($type, ConfigType::TYPES) && ! in_array($type, ConfigType::MODULE_TYPES)) {
            throw ValidationException::withMessages(['message' => __('general.errors.invalid_action')]);
        }

        $moduleConfigPath = in_array($type, ConfigType::MODULE_TYPES) ? '\\Module' : '';

        $class = __NAMESPACE__.$moduleConfigPath.'\\Store'.title_case($type).'Config';
        $class = str_replace('_', '', $class);

        if (class_exists($class)) {
            $params = $class::handle($params);
        }

        $this->store($params, $type);

        cache()->forget('query_config_list_all');
    }

    /**
     * Store config
     */
    private function store(array $params = [], ?string $type = null): void
    {
        $teamId = in_array($type, ConfigType::MODULE_TYPES) ? auth()->user()?->current_team_id : null;

        $config = Config::firstOrCreate(['name' => $type, 'team_id' => $teamId]);

        array_walk_recursive($params, function (&$param, $key) use ($config) {
            if (SysHelper::isBoolean($key)) {
                $param = $param ? true : false;
            } elseif ($param === config('app.mask')) {
                $param = $config->getValue($key);
            }
        });

        $config->value = array_merge($config->value ?? [], Arr::except($params, ['type']));
        $config->save();
    }
}
