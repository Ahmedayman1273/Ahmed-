<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Graduate;

class GraduateController extends Controller
{
    private function getImageUrl($path)
    {
        return $path ? config('app.url') . '/storage/' . ltrim($path, '/') : null;
    }

    // Get all graduates (only basic info)
    public function index()
    {
        $graduates = Graduate::select(
            'id',
            'name',
            'specialized',
            'profile',
            'photo'
        )->get();

        $formatted = $graduates->map(function ($graduate) {
            return [
                'id'          => $graduate->id,
                'name'        => $graduate->name,
                'specialized' => $graduate->specialized,
                'profile'     => $graduate->profile,
                'photo'       => $this->getImageUrl($graduate->photo),
            ];
        });

        return response()->json([
            'status' => true,
            'data'   => $formatted
        ]);
    }

    // Show full info for one graduate
    public function show($id)
    {
        $graduate = Graduate::select(
            'id',
            'name',
            'phone',
            'age',
            'specialized',
            'company',
            'profile',
            'photo',
            'experience'
        )->find($id);

        if (!$graduate) {
            return response()->json([
                'status' => false,
                'message' => 'Graduate not found'
            ], 404);
        }

        $graduate->photo = $this->getImageUrl($graduate->photo);

        return response()->json([
            'status' => true,
            'data' => $graduate
        ]);
    }
}
