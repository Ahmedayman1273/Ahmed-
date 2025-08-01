<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request as HttpRequest;
use Illuminate\Support\Facades\DB;
use App\Models\Request as RequestModel;

class RequestController extends Controller
{
    // Get requests (filtered by user type)
    public function index(HttpRequest $request)
    {
        $user = $request->user();

        if (!$user) {
            return response()->json([
                'status' => 'error',
                'message' => 'Unauthorized. Token is missing or invalid.'
            ], 401);
        }

        $query = RequestModel::query();

        if ($user->type === 'student') {
            $query->where(DB::raw('LOWER(name)'), 'not like', '%graduation certificate%');
        } elseif ($user->type === 'graduate') {
            $query->where(DB::raw('LOWER(name)'), 'like', '%graduation certificate%');
        }

        $requests = $query->latest()->get();

        return response()->json([
            'status' => 'success',
            'requests' => $requests
        ]);
    }

    // Create new request (admin only)
    public function store(HttpRequest $request)
    {
        $user = $request->user();

        if (!$user) {
            return response()->json([
                'status' => 'error',
                'message' => 'Unauthorized. Token is missing or invalid.'
            ], 401);
        }

        if ($user->type !== 'admin') {
            return response()->json([
                'status' => 'error',
                'message' => 'Forbidden. Admins only.'
            ], 403);
        }

        $request->validate([
            'name' => 'required|string|max:255',
            'price' => 'required|numeric|min:0',
            'description' => 'nullable|string',
        ]);

        $newRequest = RequestModel::create([
            'name'        => $request->name,
            'price'       => $request->price,
            'description' => $request->description,
        ]);

        return response()->json([
            'message' => 'Request created successfully',
            'request' => $newRequest
        ], 201);
    }

    // Update existing request (admin only)
    public function update(HttpRequest $request, $id)
    {
        $user = $request->user();

        if (!$user) {
            return response()->json([
                'status' => 'error',
                'message' => 'Unauthorized. Token is missing or invalid.'
            ], 401);
        }

        if ($user->type !== 'admin') {
            return response()->json([
                'status' => 'error',
                'message' => 'Forbidden. Admins only.'
            ], 403);
        }

        $requestModel = RequestModel::find($id);

        if (!$requestModel) {
            return response()->json([
                'status' => 'error',
                'message' => 'Record not found.'
            ], 404);
        }

        $request->validate([
            'name' => 'required|string|max:255',
            'price' => 'required|numeric|min:0',
            'description' => 'nullable|string',
        ]);

        $requestModel->update([
            'name'        => $request->name,
            'price'       => $request->price,
            'description' => $request->description,
        ]);

        return response()->json([
            'message' => 'Request updated successfully',
            'request' => $requestModel
        ]);
    }

    // Delete request (admin only)
    public function destroy(HttpRequest $request, $id)
    {
        $user = $request->user();

        if (!$user) {
            return response()->json([
                'status' => 'error',
                'message' => 'Unauthorized. Token is missing or invalid.'
            ], 401);
        }

        if ($user->type !== 'admin') {
            return response()->json([
                'status' => 'error',
                'message' => 'Forbidden. Admins only.'
            ], 403);
        }

        $requestModel = RequestModel::find($id);

        if (!$requestModel) {
            return response()->json([
                'status' => 'error',
                'message' => 'Record not found.'
            ], 404);
        }

        $requestModel->delete();

        return response()->json(['message' => 'Request deleted successfully']);
    }

    // Get a single request type by ID (admin only)
public function getRequestTypeById(Request $request, $id)
{
    // Check if user is admin
    if ($request->user()->type !== 'admin') {
        return response()->json(['message' => 'Only admins can access this.'], 403);
    }

    // Find the request type
    $requestType = \App\Models\Request::find($id);

    if (!$requestType) {
        return response()->json(['message' => 'Request type not found.'], 404);
    }

    return response()->json([
        'status' => 'success',
        'request_type' => $requestType
    ]);
}

}
