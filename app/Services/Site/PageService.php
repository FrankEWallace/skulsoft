<?php

namespace App\Services\Site;

use App\Models\Site\Page;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class PageService
{
    public function preRequisite(Request $request): array
    {
        return [];
    }

    public function create(Request $request): Page
    {
        \DB::beginTransaction();

        $page = Page::forceCreate($this->formatParams($request));

        $page->addMedia($request);

        \DB::commit();

        return $page;
    }

    private function formatParams(Request $request, ?Page $page = null): array
    {
        $formatted = [
            'name' => $request->name,
            'title' => $request->title,
            'sub_title' => $request->sub_title,
            'content' => $request->content,
        ];

        if (! $page) {
            $formatted['seo']['robots'] = true;
            $formatted['seo']['slug'] = Str::slug($request->title);
        }

        return $formatted;
    }

    public function update(Request $request, Page $page): void
    {
        \DB::beginTransaction();

        $page->forceFill($this->formatParams($request, $page))->save();

        $page->updateMedia($request);

        \DB::commit();
    }

    public function deletable(Page $page): void {}
}
