<?php

namespace App\Http\Controllers\Config;

use App\Actions\Config\FetchConfig;
use App\Actions\Config\GetConfig;
use App\Actions\Config\RemoveAsset;
use App\Actions\Config\StoreConfig;
use App\Actions\Config\StoreModule;
use App\Actions\Config\TestMailConnection;
use App\Actions\Config\TestPusherConnection;
use App\Actions\Config\TestSMS;
use App\Actions\Config\TestWhatsApp;
use App\Actions\Config\UploadAsset;
use App\Http\Controllers\Controller;
use App\Http\Requests\Config\AssetRequest;
use App\Models\Config\Config;
use App\Services\Config\ConfigService;
use Illuminate\Http\Request;

class ConfigController extends Controller
{
    /**
     * Instantiate a new controller instance
     */
    public function __construct()
    {
        $this->middleware('permission:config:store')->only(['uploadAsset', 'removeAsset']);
        $this->middleware('test.mode.restriction')->except(['preRequisite', 'index', 'fetch']);
    }

    /**
     * Get pre requisite
     */
    public function preRequisite(Request $request, ConfigService $service)
    {
        return response()->ok($service->getPreRequisite($request));
    }

    /**
     * Get pre requisite
     */
    public function modulePreRequisite(Request $request, ConfigService $service)
    {
        return response()->ok($service->getModulePreRequisite($request));
    }

    /**
     * Get config
     */
    public function index(GetConfig $action)
    {
        return response()->ok($action->execute());
    }

    /**
     * Fetch config
     */
    public function fetch(Request $request, FetchConfig $action)
    {
        $this->authorize('store', Config::class);

        return response()->ok($action->execute($request));
    }

    /**
     * Store config
     */
    public function store(Request $request, StoreConfig $action)
    {
        $this->authorize('store', Config::class);

        $action->execute($request->all());

        return response()->success(['message' => trans('global.stored', ['attribute' => trans('config.config')])]);
    }

    public function storeModule(Request $request, StoreModule $action)
    {
        $this->authorize('store', Config::class);

        $action->execute($request->all());

        return response()->success(['message' => trans('global.stored', ['attribute' => trans('config.module.module')])]);
    }

    /**
     * Upload asset
     */
    public function uploadAsset(AssetRequest $request, UploadAsset $action)
    {
        $this->authorize('store', Config::class);

        $action->execute($request);

        return response()->success(['message' => trans('global.uploaded', ['attribute' => trans('config.asset.asset')])]);
    }

    /**
     * Remove asset
     */
    public function removeAsset(AssetRequest $request, RemoveAsset $action)
    {
        $this->authorize('store', Config::class);

        $action->execute($request);

        return response()->success(['message' => trans('global.removed', ['attribute' => trans('config.asset.asset')])]);
    }

    /**
     * Test Mail Connection
     */
    public function testMailConnection(Request $request, TestMailConnection $action)
    {
        $action->execute($request);

        return response()->success(['message' => trans('config.mail.test_mail_sent')]);
    }

    /**
     * Test SMS Connection
     */
    public function testSMS(Request $request, TestSMS $action)
    {
        $action->execute($request);

        return response()->success(['message' => trans('config.sms.test_sms_sent')]);
    }

    /**
     * Test WhatsApp Connection
     */
    public function testWhatsApp(Request $request, TestWhatsApp $action)
    {
        $action->execute($request);

        return response()->success(['message' => trans('config.whatsapp.test_whatsapp_sent')]);
    }

    /**
     * Test Pusher Connection
     */
    public function testPusherConnection(Request $request, TestPusherConnection $action)
    {
        $action->execute($request);

        return response()->success(['message' => trans('config.notification.test_pusher_notification_sent')]);
    }
}
