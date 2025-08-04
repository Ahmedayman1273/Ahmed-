<?php

namespace App\Libraries;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class ImageValidator
{
    public static function validate(Request $request, $fieldName)
    {
        return Validator::make($request->all(), [
            $fieldName => 'nullable|image|mimes:jpeg,jpg,png,webp,gif|max:2048',
        ]);
    }
}
