<?php

namespace Mint\Service\Http\Controllers;

use App\Http\Controllers\Controller;
use Mint\Service\Actions\ForceMigrate;
use Mint\Service\Actions\Install;
use Mint\Service\Http\Requests\InstallRequest;
use Mint\Service\Services\InstallService;

class InstallController extends Controller
{
    /**
     * Force migrate
     */
    public function forceMigrate(ForceMigrate $forceMigrate)
    {
        $forceMigrate->execute();

        return trans('setup.force_migration_completed');
    }

    /**
     * Get pre requisites of server and folder
     */
    public function preRequisite(InstallService $install)
    {
        return response()->ok($install->preRequisite());
    }

    /**
     * Install the application
     */
    public function store(InstallRequest $request, Install $install)
    {
        $request->validateDatabase();

        if (in_array(request()->query('option'), ['db', 'user', 'license'])) {
            return response()->success([]);
        }

        $request->testSymlink();

        $install->execute();

        return response()->success(['message' => trans('setup.install.completed')]);
    }
}
