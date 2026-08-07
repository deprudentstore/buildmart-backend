<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\Testimonial;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class TestimonialController extends Controller
{
    public function index()
    {
        return response()->json(Testimonial::orderBy('sort_order')->get());
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'client_name' => 'required|string|max:255',
            'client_position' => 'nullable|string|max:255',
            'content' => 'required|string',
            'image_url' => 'nullable|string',
            'is_active' => 'boolean',
            'sort_order' => 'integer',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $testimonial = Testimonial::create($validator->validated());
        return response()->json($testimonial, 201);
    }

    public function update(Request $request, int $id)
    {
        $testimonial = Testimonial::findOrFail($id);

        $validator = Validator::make($request->all(), [
            'client_name' => 'sometimes|string|max:255',
            'client_position' => 'nullable|string|max:255',
            'content' => 'sometimes|string',
            'image_url' => 'nullable|string',
            'is_active' => 'boolean',
            'sort_order' => 'integer',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $testimonial->update($validator->validated());
        return response()->json($testimonial);
    }

    public function destroy(int $id)
    {
        Testimonial::findOrFail($id)->delete();
        return response()->json(['message' => 'Testimonial deleted']);
    }
}
