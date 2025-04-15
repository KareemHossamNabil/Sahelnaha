<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\ProblemType;
use App\Models\Service;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;

class ProblemTypeController extends Controller
{
    /**
     * Display a listing of problem types.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $serviceId = $request->query('service_id');

            if ($serviceId) {
                // If service_id is provided, get problem types for that service
                $service = Service::findOrFail($serviceId);
                $problemTypes = ProblemType::where('service_id', $serviceId)
                    ->where('is_active', true)
                    ->get();

                return response()->json([
                    'success' => true,
                    'data' => [
                        'service' => [
                            'id' => $service->id,
                            'service_name' => $service->service_name,
                            'category' => $service->category
                        ],
                        'problem_types' => $problemTypes
                    ]
                ]);
            } else {
                // Otherwise, get all problem types
                $problemTypes = ProblemType::with('service')
                    ->where('is_active', true)
                    ->get();

                // Group problem types by service
                $groupedProblemTypes = [];
                foreach ($problemTypes as $problemType) {
                    $serviceId = $problemType->service_id;
                    if (!isset($groupedProblemTypes[$serviceId])) {
                        $service = $problemType->service;
                        $groupedProblemTypes[$serviceId] = [
                            'service' => [
                                'id' => $service->id,
                                'service_name' => $service->service_name,
                                'category' => $service->category
                            ],
                            'problem_types' => []
                        ];
                    }
                    $groupedProblemTypes[$serviceId]['problem_types'][] = $problemType;
                }

                return response()->json([
                    'success' => true,
                    'data' => array_values($groupedProblemTypes)
                ]);
            }
        } catch (\Exception $e) {
            Log::error('Error in ProblemTypeController@index: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'An error occurred while fetching problem types',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Display the specified problem type.
     *
     * @param  int  $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function show($id): JsonResponse
    {
        try {
            $problemType = ProblemType::with('service')->findOrFail($id);

            return response()->json([
                'success' => true,
                'data' => $problemType
            ]);
        } catch (\Exception $e) {
            Log::error('Error in ProblemTypeController@show: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'An error occurred while fetching the problem type',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get problem types for a specific service.
     *
     * @param  int  $serviceId
     * @return \Illuminate\Http\JsonResponse
     */
    public function getByService($serviceId): JsonResponse
    {
        try {
            $service = Service::findOrFail($serviceId);
            $problemTypes = ProblemType::where('service_id', $serviceId)
                ->where('is_active', true)
                ->get();

            return response()->json([
                'success' => true,
                'data' => [
                    'service' => [
                        'id' => $service->id,
                        'service_name' => $service->service_name,
                        'category' => $service->category
                    ],
                    'problem_types' => $problemTypes
                ]
            ]);
        } catch (\Exception $e) {
            Log::error('Error in ProblemTypeController@getByService: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'An error occurred while fetching problem types for the service',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
