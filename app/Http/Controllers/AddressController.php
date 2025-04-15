<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Address;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class AddressController extends Controller
{
    /**
     * Display a listing of the user's addresses.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function index(Request $request)
    {
        $addresses = Address::where('user_id', $request->user()->id)
            ->orderBy('is_default', 'desc')
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json([
            'success' => true,
            'data' => $addresses
        ]);
    }

    /**
     * Store a newly created address in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'street' => 'required|string|max:255',
            'city' => 'required|string|max:255',
            'country' => 'required|string|max:255',
            'is_default' => 'nullable|boolean',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        // If this is the first address or is_default is true,
        // make all other addresses non-default
        if ($request->is_default || Address::where('user_id', $request->user()->id)->count() === 0) {
            Address::where('user_id', $request->user()->id)
                ->update(['is_default' => false]);
        }

        $address = Address::create([
            'name' => $request->name,
            'street' => $request->street,
            'city' => $request->city,
            'country' => $request->country,
            'is_default' => $request->is_default ?? false,
            'user_id' => $request->user()->id,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Address created successfully',
            'data' => $address
        ], 201);
    }

    /**
     * Update the specified address in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function update(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'sometimes|required|string|max:255',
            'street' => 'sometimes|required|string|max:255',
            'city' => 'sometimes|required|string|max:255',
            'country' => 'sometimes|required|string|max:255',
            'is_default' => 'sometimes|boolean',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $address = Address::where('id', $id)
            ->where('user_id', $request->user()->id)
            ->firstOrFail();

        // If setting this address as default, make all others non-default
        if (isset($request->is_default) && $request->is_default) {
            Address::where('user_id', $request->user()->id)
                ->where('id', '!=', $id)
                ->update(['is_default' => false]);
        }

        $address->update($request->all());

        return response()->json([
            'success' => true,
            'message' => 'Address updated successfully',
            'data' => $address
        ]);
    }

    /**
     * Remove the specified address from storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function destroy(Request $request, $id)
    {
        $address = Address::where('id', $id)
            ->where('user_id', $request->user()->id)
            ->firstOrFail();

        $address->delete();

        // If this was the default address, make another one default
        if ($address->is_default) {
            $newDefault = Address::where('user_id', $request->user()->id)->first();
            if ($newDefault) {
                $newDefault->is_default = true;
                $newDefault->save();
            }
        }

        return response()->json([
            'success' => true,
            'message' => 'Address deleted successfully'
        ]);
    }
}
