<?php

namespace App\Http\Controllers;

use App\Models\TechnicianWorkSchedule;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Illuminate\Database\Eloquent\ModelNotFoundException;

class TechnicianWorkScheduleController extends Controller
{
    /**
     * Get logged-in technician's work schedule
     * 
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function index(Request $request)
    {
        try {
            Log::info('Starting work schedule fetch', ['request' => $request->all()]);

            $technicianId = Auth::id();
            if (!$technicianId) {
                Log::warning('Unauthenticated access attempt to work schedules');
                return response()->json([
                    'status' => 403,
                    'message' => 'يجب أن تكون مسجلاً كفني لرؤية جدول العمل'
                ], 403);
            }

            Log::info('Technician authenticated', ['technician_id' => $technicianId]);

            $validator = Validator::make($request->all(), [
                'status' => 'sometimes|in:scheduled,in_progress,completed,cancelled',
                'date' => 'sometimes|date'
            ]);

            if ($validator->fails()) {
                Log::warning('Validation failed', ['errors' => $validator->errors()->toArray()]);
                return response()->json([
                    'status' => 422,
                    'errors' => $validator->errors()
                ], 422);
            }

            Log::info('Building query for work schedules');

            $query = TechnicianWorkSchedule::where('technician_id', $technicianId);

            // Add relationships with error handling
            try {
                $query->with([
                    'serviceRequest' => function ($query) {
                        $query->select(
                            'id',
                            'user_id',
                            'location',
                            'job_type',
                            'status',
                            'date',
                            'day',
                            'time_slot',
                            'longitude',
                            'latitude',
                            'description',
                            'created_at',
                            'updated_at'
                        );
                    },
                    'serviceRequest.user' => function ($query) {
                        $query->select('id', 'name', 'phone', 'email');
                    },
                    'orderService' => function ($query) {
                        $query->select(
                            'id',
                            'user_id',
                            'location',
                            'service_type',
                            'status',
                            'date',
                            'day',
                            'time_slot',
                            'longitude',
                            'latitude',
                            'description',
                            'created_at',
                            'updated_at'
                        );
                    },
                    'orderService.user' => function ($query) {
                        $query->select('id', 'name', 'phone', 'email');
                    }
                ]);
            } catch (\Exception $e) {
                Log::error('Error loading relationships', [
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString()
                ]);
                throw $e;
            }

            if ($request->has('status')) {
                $query->where('status', $request->status);
            }

            if ($request->has('date')) {
                $query->whereDate('start_time', $request->date);
            }

            Log::info('Executing query');

            $schedules = $query->orderBy('start_time', 'asc')->get();

            Log::info('Query executed successfully', ['count' => $schedules->count()]);

            $formattedSchedules = $schedules->map(function ($schedule) {
                try {
                    $request = $schedule->request;
                    if (!$request) {
                        Log::warning('Request not found for schedule', ['schedule_id' => $schedule->id]);
                        return null;
                    }

                    return [
                        'id' => $schedule->id,
                        'start_time' => $schedule->start_time,
                        'end_time' => $schedule->end_time,
                        'status' => $schedule->status,
                        'notes' => $schedule->notes,
                        'request_type' => $schedule->request_type,
                        'request_details' => [
                            'id' => $request->id,
                            'location' => $request->location,
                            'status' => $request->status,
                            'date' => $request->date,
                            'day' => $request->day,
                            'time_slot' => $request->time_slot,
                            'longitude' => $request->longitude,
                            'latitude' => $request->latitude,
                            'description' => $request->description,
                            'type' => $schedule->request_type === 'service_request' ? $request->job_type : $request->service_type,
                            'user' => $request->user ? [
                                'id' => $request->user->id,
                                'name' => $request->user->name,
                                'phone' => $request->user->phone,
                                'email' => $request->user->email
                            ] : null
                        ],
                        'created_at' => $schedule->created_at,
                        'updated_at' => $schedule->updated_at
                    ];
                } catch (\Exception $e) {
                    Log::error('Error formatting schedule', [
                        'schedule_id' => $schedule->id,
                        'error' => $e->getMessage(),
                        'trace' => $e->getTraceAsString()
                    ]);
                    return null;
                }
            })->filter(); // Remove any null entries from failed formatting

            Log::info('Schedules formatted successfully', ['count' => $formattedSchedules->count()]);

            return response()->json([
                'status' => 200,
                'work_schedules' => $formattedSchedules
            ]);
        } catch (ModelNotFoundException $e) {
            Log::error('Model not found', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return response()->json([
                'status' => 404,
                'message' => 'لم يتم العثور على جدول العمل'
            ], 404);
        } catch (\Exception $e) {
            Log::error('Error in work schedule fetch', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'request' => $request->all()
            ]);
            return response()->json([
                'status' => 500,
                'message' => 'حدث خطأ غير متوقع أثناء معالجة الطلب',
                'debug_message' => $e->getMessage() // Remove this in production
            ], 500);
        }
    }

    /**
     * Show specific work schedule
     * 
     * @param int $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function show($id)
    {
        try {
            $technicianId = Auth::id();
            if (!$technicianId) {
                return response()->json([
                    'status' => 403,
                    'message' => 'يجب أن تكون مسجلاً كفني لرؤية جدول العمل'
                ], 403);
            }

            $schedule = TechnicianWorkSchedule::with([
                'serviceRequest' => function ($query) {
                    $query->select(
                        'id',
                        'user_id',
                        'location',
                        'job_type',
                        'status',
                        'date',
                        'day',
                        'time_slot',
                        'longitude',
                        'latitude',
                        'description',
                        'created_at',
                        'updated_at'
                    );
                },
                'serviceRequest.user' => function ($query) {
                    $query->select('id', 'name', 'phone', 'email');
                },
                'orderService' => function ($query) {
                    $query->select(
                        'id',
                        'user_id',
                        'location',
                        'service_type',
                        'status',
                        'date',
                        'day',
                        'time_slot',
                        'longitude',
                        'latitude',
                        'description',
                        'created_at',
                        'updated_at'
                    );
                },
                'orderService.user' => function ($query) {
                    $query->select('id', 'name', 'phone', 'email');
                }
            ])
                ->where('technician_id', $technicianId)
                ->findOrFail($id);

            $request = $schedule->request;
            $formattedSchedule = [
                'id' => $schedule->id,
                'start_time' => $schedule->start_time,
                'end_time' => $schedule->end_time,
                'status' => $schedule->status,
                'notes' => $schedule->notes,
                'request_type' => $schedule->request_type,
                'request_details' => [
                    'id' => $request->id,
                    'location' => $request->location,
                    'status' => $request->status,
                    'date' => $request->date,
                    'day' => $request->day,
                    'time_slot' => $request->time_slot,
                    'longitude' => $request->longitude,
                    'latitude' => $request->latitude,
                    'description' => $request->description,
                    'type' => $schedule->request_type === 'service_request' ? $request->job_type : $request->service_type,
                    'user' => [
                        'id' => $request->user->id,
                        'name' => $request->user->name,
                        'phone' => $request->user->phone,
                        'email' => $request->user->email
                    ]
                ],
                'created_at' => $schedule->created_at,
                'updated_at' => $schedule->updated_at
            ];

            return response()->json([
                'status' => 200,
                'schedule' => $formattedSchedule
            ]);
        } catch (\Exception $e) {
            Log::error('Error fetching work schedule: ' . $e->getMessage());
            return response()->json([
                'status' => 404,
                'message' => 'لم يتم العثور على جدول العمل'
            ], 404);
        }
    }

    /**
     * Update work schedule status
     * 
     * @param Request $request
     * @param int $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function updateStatus(Request $request, $id)
    {
        try {
            $technicianId = Auth::id();
            if (!$technicianId) {
                return response()->json([
                    'status' => 403,
                    'message' => 'يجب أن تكون مسجلاً كفني لتحديث جدول العمل'
                ], 403);
            }

            $validator = Validator::make($request->all(), [
                'status' => 'required|in:scheduled,in_progress,completed,cancelled',
                'notes' => 'sometimes|string|max:500'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'status' => 422,
                    'errors' => $validator->errors()
                ], 422);
            }

            $schedule = TechnicianWorkSchedule::where('technician_id', $technicianId)
                ->findOrFail($id);

            $schedule->update($request->only(['status', 'notes']));

            Log::info('Work schedule status updated', [
                'schedule_id' => $id,
                'new_status' => $request->status
            ]);

            return response()->json([
                'status' => 200,
                'message' => 'تم تحديث حالة جدول العمل بنجاح',
                'schedule' => $schedule
            ]);
        } catch (\Exception $e) {
            Log::error('Error updating work schedule: ' . $e->getMessage());
            return response()->json([
                'status' => 500,
                'message' => 'حدث خطأ أثناء تحديث جدول العمل'
            ], 500);
        }
    }
}
