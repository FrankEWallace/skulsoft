<?php

namespace App\Services\Config;

use App\Models\Config\Template;
use Illuminate\Http\Request;

class PushNotificationTemplateService
{
    public function update(Request $request, Template $pushNotificationTemplate)
    {
        $pushNotificationTemplate->subject = $request->subject;
        $pushNotificationTemplate->content = $request->content;

        $pushNotificationTemplate->save();
    }
}
