<?php

namespace App\Http\Controllers;

use App\Models\Category;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Validation\Rule;

class CategoryController extends Controller
{
    public function index()
    {
        $categories = Category::query()
            ->alphabetical()
            ->withCount('notes')
            ->get();

        return response()->json(['categories' => $categories], Response::HTTP_OK);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'min:2', 'max:64', 'unique:categories,name'],
            'color' => ['required', 'string', 'regex:/^#[0-9A-Fa-f]{6}$/'],
        ]);

        $category = Category::create($validated);

        return response()->json([
            'message' => 'Kategória bola úspešne vytvorená.',
            'category' => $category->loadCount('notes'),
        ], Response::HTTP_CREATED);
    }

    public function show(Category $category)
    {
        return response()->json([
            'category' => $category->loadCount('notes'),
        ], Response::HTTP_OK);
    }

    public function update(Request $request, Category $category)
    {
        $validated = $request->validate([
            'name' => [
                'sometimes',
                'required',
                'string',
                'min:2',
                'max:64',
                Rule::unique('categories', 'name')->ignore($category->id),
            ],
            'color' => ['sometimes', 'required', 'string', 'regex:/^#[0-9A-Fa-f]{6}$/'],
        ]);

        $category->update($validated);

        return response()->json([
            'message' => 'Kategória bola úspešne aktualizovaná.',
            'category' => $category->fresh()->loadCount('notes'),
        ], Response::HTTP_OK);
    }

    public function destroy(Category $category)
    {
        $category->delete();

        return response()->json([
            'message' => 'Kategória bola odstránená.',
        ], Response::HTTP_OK);
    }
}
