<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\Category;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class CategoryController extends Controller
{
    // Menampilkan semua kategori
    public function index()
    {
        $categories = Category::latest()->get();
        return response()->json(['data' => $categories]);
    }

    // Menyimpan kategori baru
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255|unique:categories',
            'description' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors(), 422);
        }

        $category = Category::create($request->all());
        return response()->json(['message' => 'Kategori berhasil dibuat', 'data' => $category], 201);
    }

    // Menampilkan satu kategori spesifik
    public function show(Category $category)
    {
        return response()->json(['data' => $category]);
    }

    // Mengupdate kategori
    public function update(Request $request, Category $category)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255|unique:categories,name,' . $category->id,
            'description' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors(), 422);
        }

        $category->update($request->all());
        return response()->json(['message' => 'Kategori berhasil diupdate', 'data' => $category]);
    }

    // Menghapus kategori
    public function destroy(Category $category)
    {
        // Tambahan: Cek apakah kategori sedang digunakan oleh order
        if ($category->orders()->exists()) {
            return response()->json(['message' => 'Kategori tidak bisa dihapus karena sedang digunakan.'], 409); // 409 Conflict
        }

        $category->delete();
        return response()->json(['message' => 'Kategori berhasil dihapus']);
    }
}