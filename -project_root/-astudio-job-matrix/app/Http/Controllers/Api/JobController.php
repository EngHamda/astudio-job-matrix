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
            $perPage = $request->query('per_page', 2);
            $sortField = $request->query('sort');
            $sortDirection = $request->query('direction', 'asc'); // Default to ascending if not specified


            $query = Job::query();

            if ($filter) {
                $query = $this->filterService->applyFilters($query, $filter);
            }

            // Apply sorting if provided
            if ($sortField) {
                // Check if the sort field exists in the model to prevent SQL injection
                $allowedSortFields = config('job_filters')['job_columns'];
                if (in_array($sortField, $allowedSortFields)) {
                    // Validate direction
                    $direction = strtolower($sortDirection) === 'asc' ? 'asc' : 'desc';
                    $query->orderBy($sortField, $direction);
                }
            }

            // Load relationships eagerly to avoid N+1 query problems
            $query->with(['languages', 'locations', 'categories', 'jobAttributeValues.attribute']);
            $jobs = $query->paginate($perPage);

            // Create a custom response that preserves pagination but uses the resource for data
            return response()->json([
                'current_page' => $jobs->currentPage(),
                'data' => JobResource::collection($jobs->items()),
                'first_page_url' => $jobs->url(1),
                'from' => $jobs->firstItem(),
                'last_page' => $jobs->lastPage(),
                'last_page_url' => $jobs->url($jobs->lastPage()),
                'links' => $jobs->linkCollection()->toArray(),
                'next_page_url' => $jobs->nextPageUrl(),
                'path' => $jobs->path(),
                'per_page' => $jobs->perPage(),
                'prev_page_url' => $jobs->previousPageUrl(),
                'to' => $jobs->lastItem(),
                'total' => $jobs->total(),
            ]);
            // NOTE: below line has same result got above line but above has the best performance time
            // This line replaces the return statement in your existing code
            // return response()->json($jobs->through(fn($job) => new JobResource($job)));

            // Transform using resource to include relationships and EAV data
            //return response()->json(JobResource::collection($jobs));//without pagination data, but data jobs are formated
            //return response()->json($jobs);//with pagination data, but data jobs aren't formated
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
