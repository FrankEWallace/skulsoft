<?php

namespace App\Services\Config;

use App\Models\Config\Template;
use Illuminate\Http\Request;

class WhatsAppTemplateService
{
    public function update(Request $request, Template $whatsAppTemplate)
    {
        $whatsAppTemplate->subject = $request->subject;
        $whatsAppTemplate->content = $request->content;
        $whatsAppTemplate->setMeta([
            'template_id' => $request->template_id,
        ]);

        $whatsAppTemplate->save();
    }
}
