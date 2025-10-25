<?php

namespace App\Actions\Config;

use App\Concerns\HasStorage;
use Closure;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;

class SetAssetConfig
{
    use HasStorage;

    public function handle($config, Closure $next)
    {
        foreach (Arr::get($config, 'assets', []) as $key => $asset) {
            $config['assets'][$key] = $this->getAssetUrl(Arr::get($config, 'assets.'.$key));
        }

        return $next($config);
    }

    private function getAssetUrl($asset)
    {
        if (Str::startsWith($asset, '/')) {
            return url($asset);
        }

        return $this->getImageFile(visibility: 'public', path: $asset);
    }
}
