<?php
// app/Http/Controllers/Api/OrderController.php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use App\Models\Order;
use App\Models\OrderPhoto; 
use Illuminate\Support\Facades\Storage; 

class OrderController extends Controller
{
    /**
     * Menyimpan order baru ke database.
     */
    public function store(Request $request)
    {
        // Dapatkan user yang sedang login
        $user = $request->user();

        // Validasi input
        $validator = Validator::make($request->all(), [
            'category_id' => 'required|exists:categories,id', // Pastikan category_id ada di tabel categories
            'description' => 'required|string',
            'address' => 'required|string',
            'latitude' => 'required|numeric',
            'longitude' => 'required|numeric',
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors(), 422);
        }

        // Buat order baru
        $order = Order::create([
            'customer_id' => $user->id, // Ambil id dari user yang login
            'category_id' => $request->category_id,
            'description' => $request->description,
            'address' => $request->address,
            'latitude' => $request->latitude,
            'longitude' => $request->longitude,
            'status' => 'pending', // Status awal saat dibuat
        ]);

        // Kembalikan response sukses dengan data order yang baru dibuat
        return response()->json([
            'message' => 'Order berhasil dibuat',
            'data' => $order
        ], 201);
    }

    public function index(Request $request)
    {
        $user = $request->user();

        // Ambil semua order milik user tersebut menggunakan relasi 'ordersAsCustomer'
        // Gunakan `with('category')` untuk mengambil data kategori terkait (Eager Loading)
        // Gunakan `latest()` untuk mengurutkan dari yang terbaru
        $orders = $user->ordersAsCustomer()->with('category')->latest()->get();

        return response()->json([
            'message' => 'Daftar order berhasil diambil',
            'data' => $orders
        ]);
    }
    public function uploadDamagePhoto(Request $request, Order $order)
    {
        // Keamanan: Pastikan order ini milik user yang sedang login
        if ($request->user()->id !== $order->customer_id) {
            return response()->json(['message' => 'Akses ditolak.'], 403);
        }

        $validator = Validator::make($request->all(), [
            'photo' => 'required|image|mimes:jpeg,png,jpg|max:2048', // Wajib ada, harus gambar, max 2MB
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors(), 422);
        }

        // Simpan file
        $path = $request->file('photo')->store('order_photos', 'public');

        // Simpan path ke database
        $orderPhoto = OrderPhoto::create([
            'order_id' => $order->id,
            'photo_url' => $path
        ]);

        return response()->json([
            'message' => 'Foto berhasil diunggah',
            'data' => $orderPhoto
        ], 201);
    }
    public function uploadPaymentProof(Request $request, Order $order)
    {
        // Keamanan: Pastikan order ini milik user yang sedang login
        if ($request->user()->id !== $order->customer_id) {
            return response()->json(['message' => 'Akses ditolak.'], 403);
        }

        $validator = Validator::make($request->all(), [
            'payment_proof' => 'required|image|mimes:jpeg,png,jpg|max:2048',
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors(), 422);
        }

        // Simpan file di folder storage/app/public/payment_proofs
        $path = $request->file('payment_proof')->store('payment_proofs', 'public');

        // Update kolom 'payment_proof_url' pada order yang bersangkutan
        $order->payment_proof_url = $path;
        $order->save();

        return response()->json([
            'message' => 'Bukti pembayaran berhasil diunggah',
            'data' => $order
        ]);
    }
}