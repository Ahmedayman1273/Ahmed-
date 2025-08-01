<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Graduate;
use Illuminate\Support\Facades\Storage;

class GraduateController extends Controller
{
    // Get all graduates (for list)
    public function index()
    {
        $graduates = Graduate::select('id', 'name', 'specialized', 'profile', 'photo')->get();

        $graduates->transform(function ($graduate) {
            $graduate->photo = $graduate->photo ? url('storage/' . $graduate->photo) : null;
            return $graduate;
        });

        return response()->json($graduates);
    }

    // Show details for one graduate
    public function show($id)
    {
        $graduate = Graduate::find($id);

        if (!$graduate) {
            return response()->json(['message' => 'Graduate not found'], 404);
        }

        $graduate->photo = $graduate->photo ? url('storage/' . $graduate->photo) : null;

        return response()->json($graduate);
    }

 
}
