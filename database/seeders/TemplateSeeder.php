<?php

namespace Database\Seeders;

use App\Models\Config\Template;
use Illuminate\Database\Seeder;
use Illuminate\Support\Arr;

class TemplateSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $allTemplates = Arr::getVar('templates');

        foreach ($allTemplates as $key => $templates) {
            foreach ($templates as $template) {
                $newTemplate = Template::firstOrCreate([
                    'type' => $key,
                    'code' => Arr::get($template, 'code'),
                ]);

                $newTemplate->update([
                    'name' => Arr::get($template, 'name'),
                    'subject' => Arr::get($template, 'subject'),
                    'content' => Arr::get($template, 'content'),
                    'enabled_at' => now()->toDateTimeString(),
                ]);
            }
        }
    }
}
