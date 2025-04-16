<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Address;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class AddressController extends Controller
{
    /**
     * Get the user's only address (default one).
     */
    public function show(Request $request)
    {
        $address = Address::where('user_id', $request->user()->id)->first();

        if (!$address) {
            return response()->json([
                'success' => false,
                'message' => 'No address found'
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => $address
        ]);
    }

    /**
     * Create or overwrite the user's address.
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'street' => 'required|string|max:255',
            'city' => 'required|string|max:255',
            'country' => 'required|string|max:255',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        // Check if address already exists
        $address = Address::where('user_id', $request->user()->id)->first();

        if ($address) {
            // Update existing address
            $address->update([
                'name' => $request->name,
                'street' => $request->street,
                'city' => $request->city,
                'country' => $request->country,
                'is_default' => true,
            ]);

            $message = 'Address updated successfully';
        } else {
            // Create new address
            $address = Address::create([
                'user_id' => $request->user()->id,
                'name' => $request->name,
                'street' => $request->street,
                'city' => $request->city,
                'country' => $request->country,
                'is_default' => true,
            ]);

            $message = 'Address created successfully';
        }

        return response()->json([
            'success' => true,
            'message' => $message,
            'data' => $address
        ]);
    }

    /**
     * Update the user's address.
     */
    public function update(Request $request)
    {
        $address = Address::where('user_id', $request->user()->id)->firstOrFail();

        $validator = Validator::make($request->all(), [
            'name' => 'sometimes|required|string|max:255',
            'street' => 'sometimes|required|string|max:255',
            'city' => 'sometimes|required|string|max:255',
            'country' => 'sometimes|required|string|max:255',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $address->update($request->all());

        return response()->json([
            'success' => true,
            'message' => 'Address updated successfully',
            'data' => $address
        ]);
    }

    /**
     * Delete the user's address.
     */
    public function destroy(Request $request)
    {
        $address = Address::where('user_id', $request->user()->id)->firstOrFail();

        $address->delete();

        return response()->json([
            'success' => true,
            'message' => 'Address deleted successfully'
        ]);
    }
}
