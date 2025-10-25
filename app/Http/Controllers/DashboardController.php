<?php

namespace App\Http\Controllers;

use App\Services\Dashboard\CelebrationService;
use App\Services\Dashboard\FormListService;
use App\Services\Dashboard\GalleryService;
use App\Services\Dashboard\InstituteInfoService;
use App\Services\Dashboard\MessScheduleService;
use App\Services\Dashboard\ScheduleService;
use App\Services\Dashboard\StatService;
use App\Services\Dashboard\StudentService;
use App\Services\Dashboard\TimetableService;
use App\Services\Dashboard\TransportRouteService;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function __construct()
    {
        $this->middleware('role:student|guardian')->only(['listStudent']);
    }

    /**
     * Dashboard stats
     */
    public function stat(StatService $service)
    {
        return $service->fetch();
    }

    public function schedule(Request $request, ScheduleService $service)
    {
        return $service->fetch($request);
    }

    public function listStudent(Request $request, StudentService $service)
    {
        return $service->fetch($request);
    }

    public function listGallery(Request $request, GalleryService $service)
    {
        return $service->fetch($request);
    }

    public function getCelebration(Request $request, CelebrationService $service)
    {
        return $service->fetch($request);
    }

    public function getTimetable(Request $request, TimetableService $service)
    {
        return $service->fetch($request);
    }

    public function getTransportRoute(Request $request, TransportRouteService $service)
    {
        return $service->fetch($request);
    }

    public function getMessSchedule(Request $request, MessScheduleService $service)
    {
        return $service->fetch($request);
    }

    public function getInstituteInfo(Request $request, InstituteInfoService $service)
    {
        return $service->fetch($request);
    }

    public function getFormList(Request $request, FormListService $service)
    {
        return $service->fetch($request);
    }
}
