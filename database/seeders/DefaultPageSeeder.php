<?php

namespace Database\Seeders;

use App\Models\Site\Page;
use Illuminate\Database\Seeder;

class DefaultPageSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $pages = [
            [
                'name' => 'Home',
                'title' => 'Welcome to '.config('app.name'),
                'content' => 'Welcome to '.config('app.name'),
            ],
            [
                'name' => 'Contact',
                'title' => 'Welcome to '.config('app.name'),
                'content' => 'Welcome to '.config('app.name'),
            ],
        ];

        foreach ($pages as $page) {
            Page::forceCreate($page);
        }
    }
}
