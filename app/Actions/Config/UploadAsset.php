<?php

namespace App\Actions\Config;

use App\Concerns\HasStorage;
use App\Models\Config\Config;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;

class UploadAsset
{
    use HasStorage;

    public function execute(Request $request)
    {
        $request->validate([
            'image' => 'required|image',
        ]);

        $assets = Config::where('name', 'assets')->first();
        $asset = Arr::get($assets?->value, $request->query('type'));

        $this->deleteImageFile(
            visibility: 'public',
            path: $asset
        );

        $image = $this->uploadImageFile(
            visibility: 'public',
            path: 'assets/'.$request->query('type'),
            input: 'image',
            url: false
        );

        $config = Config::firstOrCreate(['name' => 'assets']);
        $config->setValue([$request->query('type') => $image]);
        $config->save();

        cache()->forget('query_config_list_all');
    }
}
