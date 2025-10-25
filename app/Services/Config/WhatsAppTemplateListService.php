<?php

namespace App\Services\Config;

use App\Contracts\ListGenerator;
use App\Http\Resources\Config\WhatsAppTemplateResource;
use App\Models\Config\Template;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class WhatsAppTemplateListService extends ListGenerator
{
    protected $allowedSorts = ['created_at', 'name'];

    protected $defaultSort = 'name';

    protected $defaultOrder = 'asc';

    public function getHeaders(): array
    {
        $headers = [
            [
                'key' => 'name',
                'label' => trans('config.whatsapp.template.props.name'),
                'sortable' => true,
                'visibility' => true,
            ],
            [
                'key' => 'templateId',
                'label' => trans('config.whatsapp.template.props.template_id'),
                'sortable' => false,
                'visibility' => true,
            ],
            [
                'key' => 'subject',
                'label' => trans('config.whatsapp.template.props.subject'),
                'sortable' => true,
                'visibility' => true,
            ],
        ];

        if (request()->ajax()) {
            $headers[] = $this->actionHeader;
        }

        return $headers;
    }

    public function filter(Request $request): Builder
    {
        return Template::query()
            ->whereType('whatsapp')
            ->filter([
                'App\QueryFilters\LikeMatch:name',
            ]);
    }

    public function paginate(Request $request): AnonymousResourceCollection
    {
        return WhatsAppTemplateResource::collection($this->filter($request)
            ->orderBy($this->getSort(), $this->getOrder())
            ->paginate((int) $this->getPageLength(), ['*'], 'current_page'))
            ->additional([
                'headers' => $this->getHeaders(),
                'meta' => [
                    'allowed_sorts' => $this->allowedSorts,
                    'default_sort' => $this->defaultSort,
                    'default_order' => $this->defaultOrder,
                ],
            ]);
    }

    public function list(Request $request): AnonymousResourceCollection
    {
        return $this->paginate($request);
    }
}
