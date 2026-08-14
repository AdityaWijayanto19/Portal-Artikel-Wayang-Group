<?php

namespace App\Http\Controllers;

use App\Models\WPSite;
use App\Services\WpSiteService;
use Illuminate\Http\Request;
use Illuminate\View\View;

class WPSiteVisitorController extends Controller
{
    public function __construct(
        protected WpSiteService $wpSiteService
    ) {}

    public function index(Request $request): View
    {
        // 1. Authorization check via Policy
        $this->authorize('viewVisitorAnalytics', WPSite::class);

        // 2. Delegate business logic ke Service Layer
        $sites = $this->wpSiteService->getTrackedSitesForUser($request->user());

        // 3. Render View
        return view('wp-sites.visitors', compact('sites'));
    }
}
