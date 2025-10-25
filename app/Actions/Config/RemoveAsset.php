<?php

namespace App\Actions\Config;

use App\Concerns\HasStorage;
use App\Models\Config\Config;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;

class RemoveAsset
{
    use HasStorage;

    public function execute(Request $request)
    {
        $assets = Config::where('name', 'assets')->first();
        $asset = Arr::get($assets?->value, $request->query('type'));

        $this->deleteImageFile(
            visibility: 'public',
            path: $asset,
        );

        $config = Config::firstOrCreate(['name' => 'assets']);
        $config->resetValue([$request->query('type')]);
        $config->save();

        cache()->forget('query_config_list_all');
    }
}
