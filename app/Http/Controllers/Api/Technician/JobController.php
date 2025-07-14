<?php

namespace App\Http\Controllers\Api\Technician;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Order; 
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class JobController extends Controller
{
    /**
     * Menampilkan daftar pekerjaan yang ditugaskan kepada teknisi yang sedang login.
     */
    public function index(Request $request)
    {
        $technician = $request->user();

        // Ambil semua order yang ditugaskan ke teknisi ini melalui relasi
        $jobs = $technician->ordersAsTechnician()
                            ->with('customer', 'category') 
                            ->latest()
                            ->get();

        return response()->json([
            'message' => 'Daftar pekerjaan berhasil diambil',
            'data' => $jobs
        ]);
    }
    public function updateStatus(Request $request, Order $order)
    {
        $technician = $request->user();

        // --- PENTING: Validasi Keamanan ---
        // Pastikan order yang akan diupdate benar-benar milik teknisi yang sedang login.
        if ($order->technician_id !== $technician->id) {
            return response()->json(['message' => 'Akses ditolak. Ini bukan pekerjaan Anda.'], 403);
        }

        // Validasi input status
        $validator = Validator::make($request->all(), [
            'status' => ['required', Rule::in(['in_progress', 'completed', 'cancelled'])],
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors(), 422);
        }

        // Update status order
        $order->status = $request->status;
        $order->save();

        // Ambil data terbaru untuk dikembalikan sebagai response
        $order->load('customer', 'category');

        return response()->json([
            'message' => 'Status pekerjaan berhasil diupdate',
            'data' => $order
        ]);
    }

    public function show(Request $request, Order $order)
    {
        // Keamanan: Pastikan teknisi hanya bisa melihat job miliknya sendiri
        if ($request->user()->id !== $order->technician_id) {
            return response()->json(['message' => 'Ini bukan pekerjaan Anda.'], 403);
        }

        // Muat relasi yang dibutuhkan
        $order->load('customer', 'category', 'photos');

        return response()->json(['data' => $order]);
    }
}