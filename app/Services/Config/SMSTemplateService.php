<?php

namespace App\Services\Config;

use App\Models\Config\Template;
use Illuminate\Http\Request;

class SMSTemplateService
{
    public function update(Request $request, Template $smsTemplate)
    {
        $smsTemplate->subject = $request->subject;
        $smsTemplate->content = $request->content;
        $smsTemplate->setMeta([
            'template_id' => $request->template_id,
        ]);

        $smsTemplate->save();
    }
}
