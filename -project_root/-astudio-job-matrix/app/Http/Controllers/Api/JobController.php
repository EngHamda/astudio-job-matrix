<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\CoreJob as Job;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use App\Services\JobFilterService;
use App\Http\Resources\JobResource;

class JobController extends Controller
{
    protected JobFilterService $filterService;

    public function __construct(JobFilterService $filterService)
    {
        $this->filterService = $filterService;
    }

    /**
     * Get filtered jobs
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function index(Request $request): JsonResponse
    {
        $filter = $request->query('filter');
        $query = Job::query();
        $query = $this->filterService->applyFilters($query, $filter);

        if ($filter) {

            $query = $this->filterService->applyFilters($query, $filter);
        }

        // Load relationships eagerly to avoid N+1 query problems
        $query->with(['languages', 'locations', 'categories', 'jobAttributeValues.attribute']);
        $jobs = $query->paginate(15);

        // Transform using resource to include relationships and EAV data
        return response()->json(JobResource::collection($jobs));

    }
}
