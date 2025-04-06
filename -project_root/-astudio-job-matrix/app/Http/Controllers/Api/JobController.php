<?php

namespace App\Http\Controllers\Api;

use Exception;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\Log;
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
        try {
            // Validate the request parameters
//            $validated = $request->validate([
//                'filter' => 'sometimes|array',
//                'per_page' => 'sometimes|integer|min:1|max:100',
//            ]);

            $filter = $request->query('filter');
            $perPage = $request->query('per_page', 15);

            $query = Job::query();

            if ($filter) {
                $query = $this->filterService->applyFilters($query, $filter);
            }

            // Load relationships eagerly to avoid N+1 query problems
            $query->with(['languages', 'locations', 'categories', 'jobAttributeValues.attribute']);
            $jobs = $query->paginate($perPage);

            // Transform using resource to include relationships and EAV data
            return response()->json(JobResource::collection($jobs));
        } catch (ModelNotFoundException $e) {
            // Handle not found errors
            Log::warning("Job not found", ['error' => $e->getMessage()]);
            return response()->json([
                'error' => true,
                'message' => 'Job not found'
            ], 404);
        } catch (Exception $e) {
            // Handle unexpected errors
            Log::error("Unexpected error in job filter", [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return response()->json([
                'error' => true,
                'message' => config('app.debug') ? $e->getMessage() : 'An unexpected error occurred'
            ], 500);
        }
    }
}
