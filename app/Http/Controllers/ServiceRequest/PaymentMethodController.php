<?php

namespace App\Http\Controllers\ServiceRequest;

use App\Http\Controllers\Controller;

use App\Models\PaymentMethod;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Validator;
use Exception;

class PaymentMethodController extends Controller
{
    /**
     * عرض قائمة بجميع أنواع طرق الدفع
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function index(): JsonResponse
    {
        try {
            $paymentMethods = PaymentMethod::all();

            return response()->json([
                'status' => 200,
                'message' => 'Payment methods retrieved successfully',
                'data' => $paymentMethods
            ]);
        } catch (Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to retrieve payment methods',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * عرض تفاصيل نوع طريقة دفع محددة
     *
     * @param int $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function show($id): JsonResponse
    {
        try {
            $paymentMethod = PaymentMethod::findOrFail($id);

            return response()->json([
                'status' => 'success',
                'message' => 'Payment method retrieved successfully',
                'data' => $paymentMethod
            ]);
        } catch (Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Payment method not found',
                'error' => $e->getMessage()
            ], 404);
        }
    }

    /**
     * إنشاء نوع طريقة دفع جديدة
     *
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function store(Request $request): JsonResponse
    {
        try {
            $validator = Validator::make($request->all(), [
                'name' => 'required|string|max:255|unique:payment_methods,name',
                'code' => 'required|string|max:50|unique:payment_methods,code',
                'description' => 'nullable|string',
                'icon' => 'nullable|string',
                'is_active' => 'boolean',
                'requires_card_details' => 'boolean',
                'display_order' => 'integer'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Validation error',
                    'errors' => $validator->errors()
                ], 422);
            }

            $paymentMethod = PaymentMethod::create($request->all());

            return response()->json([
                'status' => 'success',
                'message' => 'Payment method created successfully',
                'data' => $paymentMethod
            ], 201);
        } catch (Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to create payment method',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * تحديث نوع طريقة دفع موجودة
     *
     * @param \Illuminate\Http\Request $request
     * @param int $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function update(Request $request, $id): JsonResponse
    {
        try {
            $paymentMethod = PaymentMethod::findOrFail($id);

            $validator = Validator::make($request->all(), [
                'name' => 'string|max:255|unique:payment_methods,name,' . $id,
                'code' => 'string|max:50|unique:payment_methods,code,' . $id,
                'description' => 'nullable|string',
                'icon' => 'nullable|string',
                'is_active' => 'boolean',
                'requires_card_details' => 'boolean',
                'display_order' => 'integer'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Validation error',
                    'errors' => $validator->errors()
                ], 422);
            }

            $paymentMethod->update($request->all());

            return response()->json([
                'status' => 'success',
                'message' => 'Payment method updated successfully',
                'data' => $paymentMethod
            ]);
        } catch (Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to update payment method',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * حذف نوع طريقة دفع
     *
     * @param int $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function destroy($id): JsonResponse
    {
        try {
            $paymentMethod = PaymentMethod::findOrFail($id);
            $paymentMethod->delete();

            return response()->json([
                'status' => 'success',
                'message' => 'Payment method deleted successfully'
            ]);
        } catch (Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to delete payment method',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * تفعيل أو تعطيل نوع طريقة دفع
     *
     * @param int $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function toggleActive($id): JsonResponse
    {
        try {
            $paymentMethod = PaymentMethod::findOrFail($id);
            $paymentMethod->is_active = !$paymentMethod->is_active;
            $paymentMethod->save();

            $status = $paymentMethod->is_active ? 'activated' : 'deactivated';

            return response()->json([
                'status' => 'success',
                'message' => "Payment method {$status} successfully",
                'data' => $paymentMethod
            ]);
        } catch (Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to toggle payment method status',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
