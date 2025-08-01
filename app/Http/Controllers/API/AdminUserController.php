<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Maatwebsite\Excel\Facades\Excel;
use App\Imports\UsersImport;
use App\Models\User;
use App\Models\StudentRequest;
use App\Models\Request as RequestModel;
use App\Notifications\RequestStatusUpdated;



class AdminUserController extends Controller
{

    // Create new user (student or graduate)
public function createUser(Request $request)
{
    // Only admins can create users
    if ($request->user()->type !== 'admin') {
        return response()->json([
            'status' => 'error',
            'message' => 'Only admins can create users.'
        ], 403);
    }

    $messages = [];

    if ($request->id && User::find($request->id)) {
        $messages[] = 'ID already exists';
    }

    if (User::where('email', $request->email)->exists()) {
        $messages[] = 'Email already exists';
    }

    if ($request->phone_number && User::where('phone_number', $request->phone_number)->exists()) {
        $messages[] = 'Phone number already exists';
    }

    if ($request->personal_email && User::where('personal_email', $request->personal_email)->exists()) {
        $messages[] = 'Personal email already exists';
    }

    if (!empty($messages)) {
        return response()->json([
            'status' => 'error',
            'message' => implode('. ', $messages) . '.'
        ], 409);
    }

    $user = User::create([
        'id'             => $request->id,
        'name'           => $request->name,
        'email'          => $request->email,
        'personal_email' => $request->personal_email,
        'phone_number'   => $request->phone_number,
        'type'           => $request->type,
        'major'          => $request->major ?? 'Computer Science',
        'password'       => Hash::make($request->password ?? '123456'),
    ]);

    return response()->json([
        'message' => 'User created successfully.',
        'user'    => $user
    ], 201);
}


//creat new admin
public function createAdmin(Request $request)
{
    // Only admins can create new admins
    if ($request->user()->type !== 'admin') {
        return response()->json([
            'status' => 'error',
            'message' => 'Only admins can create new admins.'
        ], 403);
    }

    // Validate input
    $validator = Validator::make($request->all(), [
        'id'              => 'nullable|integer|min:1',
        'name'            => 'required|string|max:255',
        'email'           => 'required|email',
        'password'        => 'required|string|min:6',
        'phone_number'    => 'nullable|string|max:20',
        'personal_email'  => 'nullable|email',
    ]);

    if ($validator->fails()) {
        $flatMessages = implode(' ', array_map(function ($messages) {
            return implode(' ', $messages);
        }, $validator->errors()->toArray()));

        return response()->json([
            'status'  => 'error',
            'message' => $flatMessages
        ], 422);
    }

    // Check for duplicate values
    $messages = [];

    if ($request->id && User::find($request->id)) {
        $messages[] = 'ID already exists';
    }

    if (User::where('email', $request->email)->exists()) {
        $messages[] = 'Email already exists';
    }

    if ($request->phone_number && User::where('phone_number', $request->phone_number)->exists()) {
        $messages[] = 'Phone number already exists';
    }

    if ($request->personal_email && User::where('personal_email', $request->personal_email)->exists()) {
        $messages[] = 'Personal email already exists';
    }

    if (!empty($messages)) {
        return response()->json([
            'status'  => 'error',
            'message' => implode('. ', $messages) . '.'
        ], 409);
    }

    // Create the admin
    $admin = User::create([
        'id'             => $request->id,
        'name'           => $request->name,
        'email'          => $request->email,
        'phone_number'   => $request->phone_number,
        'personal_email' => $request->personal_email,
        'type'           => 'admin',
        'password'       => Hash::make($request->password),
    ]);

    return response()->json([
        'message' => 'Admin created successfully.',
        'admin'   => $admin
    ], 201);
}


   // Import users from Excel file
    public function importUsersFromExcel(Request $request)
{
    if ($request->user()->type !== 'admin') {
        return response()->json(['message' => 'Only admins can import users.'], 403);
    }

    $request->validate([
        'file' => 'required|file|mimes:xlsx,xls'
    ]);

    try {
        $rows = Excel::toCollection(new UsersImport, $request->file('file'))[0];

        $skippedIds = [];
        $importedCount = 0;
        $importedUsers = [];

        foreach ($rows as $row) {
            if (
                ($row['id'] && User::find($row['id'])) ||
                User::where('email', $row['email'])->exists() ||
                User::where('phone_number', $row['phone_number'])->exists() ||
                User::where('personal_email', $row['personal_email'])->exists()
            ) {
                $skippedIds[] = $row['id'];
                continue;
            }

            $user = User::create([
                'id'             => $row['id'],
                'name'           => $row['name'],
                'email'          => $row['email'],
                'personal_email' => $row['personal_email'],
                'phone_number'   => $row['phone_number'],
                'type'           => $row['type'],
                'major'          => $row['major'] ?? 'Computer Science',
                'password'       => Hash::make($row['password'] ?? '123456'),
            ]);

            $importedUsers[] = $user;
            $importedCount++;
        }

        return response()->json([
            'message'         => 'Import completed.',
            'imported_count'  => $importedCount,
            'skipped_count'   => count($skippedIds),
            'skipped_ids'     => $skippedIds,
            'imported_users'  => $importedUsers,
        ]);

    } catch (\Exception $e) {
        return response()->json(['message' => 'Import failed', 'error' => $e->getMessage()], 500);
    }
}










    // Helper to format student request output
 private function formatRequests($query)
{
    return $query->with('requestType')
        ->latest()
        ->get()
        ->map(function ($req) {
            return [
                'request_id'      => $req->id,
                'student_id'      => $req->student_id,
                'type_id'         => $req->request_id,
                'type_name'       => $req->requestType->name ?? null,
                'count'           => $req->count,
                'total_price'     => $req->total_price,
                'status'          => $req->status,
                'notes'           => $req->notes,
                'student_name_en' => $req->student_name_en,
                'student_name_ar' => $req->student_name_ar,
                'department'      => $req->department,
                'receipt_image'   => $req->receipt_image
                    ? url('storage/' . $req->receipt_image)
                    : null,
            ];
        });
}

  // Get all pending requests
public function getPendingRequests(Request $request)
{
    if ($request->user()->type !== 'admin') {
        return response()->json(['message' => 'Only admins can access student requests.'], 403);
    }

    $requests = $this->formatRequests(
        StudentRequest::where('status', 'pending')
    );

    return response()->json([
        'status' => 'success',
        'requests' => $requests
    ]);
}

// Get all approved requests
public function getAcceptedRequests(Request $request)
{
    if ($request->user()->type !== 'admin') {
        return response()->json(['message' => 'Only admins can access student requests.'], 403);
    }

    $requests = $this->formatRequests(
        StudentRequest::where('status', 'approved')
    );

    return response()->json([
        'status' => 'success',
        'requests' => $requests
    ]);
}

// Get all rejected requests
public function getRejectedRequests(Request $request)
{
    if ($request->user()->type !== 'admin') {
        return response()->json(['message' => 'Only admins can access student requests.'], 403);
    }

    $requests = $this->formatRequests(
        StudentRequest::where('status', 'rejected')
    );

    return response()->json([
        'status' => 'success',
        'requests' => $requests
    ]);
}


// Accept a student request
public function acceptStudentRequest(Request $request, $id)
{
    if ($request->user()->type !== 'admin') {
        return response()->json(['message' => 'Only admins can update request status.'], 403);
    }

    $studentRequest = StudentRequest::with('user', 'requestType')->find($id);

    if (!$studentRequest) {
        return response()->json(['status' => 'error', 'message' => 'Request not found.'], 404);
    }

    $request->validate([
        'delivery_date' => 'required|date'
    ]);

    $studentRequest->update([
        'status' => 'approved',
        'notes'  => 'Delivery date: ' . $request->delivery_date
    ]);

    // ✅ Send notification to the student
    $studentRequest->user->notify(new RequestStatusUpdated([
        'title'   => 'Request Approved',
        'message' => "Your request for '{$studentRequest->requestType->name}' has been approved. Delivery date: {$request->delivery_date}."
    ]));

    return response()->json(['status' => 'success', 'message' => 'Request approved.']);
}


// Reject a student request
public function rejectStudentRequest(Request $request, $id)
{
    if ($request->user()->type !== 'admin') {
        return response()->json(['message' => 'Only admins can update request status.'], 403);
    }

    $studentRequest = StudentRequest::with('user', 'requestType')->find($id);

    if (!$studentRequest) {
        return response()->json(['status' => 'error', 'message' => 'Request not found.'], 404);
    }

    $request->validate([
        'reason' => 'required|string|max:255'
    ]);

    $studentRequest->update([
        'status' => 'rejected',
        'notes'  => $request->reason
    ]);

    // ✅ Send notification to the student
    $studentRequest->user->notify(new RequestStatusUpdated([
        'title'   => 'Request Rejected',
        'message' => "Your request for '{$studentRequest->requestType->name}' has been rejected. Reason: {$request->reason}"
    ]));

    return response()->json(['status' => 'success', 'message' => 'Request rejected.']);
}



// Show specific pending request by ID
public function showPendingRequestById(Request $request, $id)
{
    if ($request->user()->type !== 'admin') {
        return response()->json(['message' => 'Only admins can view student requests.'], 403);
    }

    $requestRecord = StudentRequest::where('status', 'pending')
        ->with(['user', 'requestType'])
        ->find($id);

    if (!$requestRecord) {
        return response()->json([
            'status' => 'error',
            'message' => 'Request not found.'
        ], 404);
    }

    $formatted = [
        'request_id'      => $requestRecord->id,
        'type_id'         => $requestRecord->request_id,
        'type_name'       => $requestRecord->requestType->name ?? null,
        'count'           => $requestRecord->count,
        'total_price'     => $requestRecord->total_price,
        'status'          => $requestRecord->status,
        'notes'           => $requestRecord->notes,
        'student_name_en' => $requestRecord->student_name_en,
        'student_name_ar' => $requestRecord->student_name_ar,
        'department'      => $requestRecord->department,
        'receipt_image'   => $requestRecord->receipt_image
            ? url('storage/' . $requestRecord->receipt_image)
            : null,
    ];

    return response()->json([
        'status' => 'success',
        'request' => $formatted
    ]);
}








    // Get all request types
    public function getAllRequestTypes(Request $request)
{
    if ($request->user()->type !== 'admin') {
        return response()->json(['message' => 'Only admins can access request types.'], 403);
    }

    $types = RequestModel::latest()->get();

    return response()->json([
        'status' => 'success',
        'types' => $types
    ]);
}

    // Create new request type
  public function createRequestType(Request $request)
{
    if ($request->user()->type !== 'admin') {
        return response()->json(['message' => 'Only admins can create request types.'], 403);
    }

    $request->validate([
        'name'        => 'required|string|max:255',
        'price'       => 'required|numeric|min:0',
        'description' => 'nullable|string',
    ]);

    $requestType = RequestModel::create([
        'name'        => $request->name,
        'price'       => $request->price,
        'description' => $request->description,
    ]);

    return response()->json([
        'message' => 'Request type created successfully.',
        'request_type' => $requestType
    ], 201);
}

    // Update request type
   public function updateRequestType(Request $request, $id)
{
    if ($request->user()->type !== 'admin') {
        return response()->json(['message' => 'Only admins can update request types.'], 403);
    }

    $requestType = RequestModel::find($id);

    if (!$requestType) {
        return response()->json(['message' => 'Request type not found.'], 404);
    }

    $request->validate([
        'name'        => 'required|string|max:255',
        'price'       => 'required|numeric|min:0',
        'description' => 'nullable|string',
    ]);

    $requestType->update([
        'name'        => $request->name,
        'price'       => $request->price,
        'description' => $request->description,
    ]);

    return response()->json([
        'message' => 'Request type updated successfully.',
        'request_type' => $requestType
    ]);
}

    // Delete request type
  public function deleteRequestType(Request $request, $id)
{
    if ($request->user()->type !== 'admin') {
        return response()->json(['message' => 'Only admins can delete request types.'], 403);
    }

    $requestType = RequestModel::find($id);

    if (!$requestType) {
        return response()->json(['message' => 'Request type not found.'], 404);
    }

    $requestType->delete();

    return response()->json(['message' => 'Request type deleted successfully.']);
}
   // get request type {id}
public function getRequestTypeById(Request $request, $id)
{
    if ($request->user()->type !== 'admin') {
        return response()->json(['message' => 'Only admins can access this.'], 403);
    }

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
