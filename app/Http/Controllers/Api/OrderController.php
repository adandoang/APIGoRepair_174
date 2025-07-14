<?php
// app/Http/Controllers/Api/OrderController.php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use App\Models\Order;
use App\Models\OrderPhoto; 
use Illuminate\Support\Facades\Storage; 
use Barryvdh\DomPDF\Facade\Pdf;
use App\Models\ServiceRating;

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
            'data' => $order->load('customer', 'category')
        ], 201);
    }

    public function index(Request $request)
    {
        $user = $request->user();

        // Ambil semua order milik user tersebut menggunakan relasi 'ordersAsCustomer'
        // Gunakan `with('category')` untuk mengambil data kategori terkait (Eager Loading)
        // Gunakan `latest()` untuk mengurutkan dari yang terbaru
        $orders = $user->ordersAsCustomer()->with('category', 'customer', 'rating')->latest()->get();

        return response()->json([
            'message' => 'Daftar order berhasil diambil',
            'data' => $orders
        ]);
    }
    // app/Http/Controllers/Api/OrderController.php
    public function show(Request $request, Order $order)
    {
        // Keamanan: Pastikan order ini milik user yang sedang login
        if ($request->user()->id !== $order->customer_id) {
            return response()->json(['message' => 'Akses ditolak.'], 403);
        }

        // Muat semua relasi yang dibutuhkan sebelum mengirim response
        $order->load('customer', 'category', 'technician', 'photos', 'rating');

        return response()->json(['data' => $order]);
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
            'payment_proof' => 'required|image|mimes:jpeg,png,jpg|max:5120',
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors(), 422);
        }

        $path = $request->file('payment_proof')->store('payment_proofs', 'public');

        $order->payment_proof_url = $path;
        $order->save();

        return response()->json([
            'message' => 'Bukti pembayaran berhasil diunggah',
            'data' => $order
        ]);
    }

     public function cancelOrder(Request $request, Order $order)
    {
        // Keamanan: Pastikan pengguna adalah pemilik order
        if ($request->user()->id !== $order->customer_id) {
            return response()->json(['message' => 'Akses ditolak.'], 403);
        }

        // Logika Bisnis: Pesanan hanya bisa dibatalkan jika statusnya 'pending' atau 'processed'
        if (!in_array($order->status, ['pending', 'processed'])) {
            return response()->json(['message' => 'Pesanan tidak dapat dibatalkan pada tahap ini.'], 422);
        }

        // Ubah status menjadi 'cancelled'
        $order->status = 'cancelled';
        $order->save();

        return response()->json(['message' => 'Pesanan berhasil dibatalkan.']);
    }

    public function downloadInvoice(Order $order)
   {
       // Pastikan order milik customer yang sedang login
       if ($order->customer_id !== auth()->id()) {
           abort(403, 'Unauthorized');
       }
       $pdf = PDF::loadView('invoices.order', ['order' => $order]);
       return $pdf->download('invoice-order-'.$order->id.'.pdf');
   }

   public function addTechnicianNotes(Request $request, Order $order)
    {
        $request->validate([
            'notes' => 'required|string|max:1000',
        ]);

        // Pastikan teknisi yang login adalah teknisi yang ditugaskan
        if ($order->technician_id !== auth()->id()) {
            return response()->json([
                'message' => 'Anda tidak memiliki akses ke order ini'
            ], 403);
        }

        $order->update([
            'technician_notes' => $request->notes
        ]);

        return response()->json([
            'message' => 'Catatan berhasil ditambahkan',
            'data' => $order->load(['category', 'customer', 'technician', 'photos', 'rating'])
        ]);
    }

    public function addRating(Request $request, Order $order)
{
    $request->validate([
        'rating' => 'required|integer|min:1|max:5',
        'comment' => 'nullable|string|max:500'
    ]);

    // Pastikan customer yang login adalah pemilik order
    if ($request->user()->id !== $order->customer_id) {
        return response()->json([
            'message' => 'Unauthorized'
        ], 403);
    }

    // Pastikan order sudah completed dan payment validated
    if ($order->status !== 'completed' || !$order->is_payment_validated) {
        return response()->json([
            'message' => 'Order harus sudah selesai dan pembayaran divalidasi'
        ], 400);
    }

    // Cek apakah sudah pernah rating
    if ($order->rating) {
        return response()->json([
            'message' => 'Order ini sudah diberi rating'
        ], 400);
    }

    $rating = ServiceRating::create([
        'order_id' => $order->id,
        'customer_id' => $request->user()->id,
        'technician_id' => $order->technician_id,
        'rating' => $request->rating,
        'comment' => $request->comment
    ]);

    return response()->json([
        'message' => 'Rating berhasil ditambahkan',
        'data' => $rating->load(['order', 'customer', 'technician'])
    ]);
}

public function getRating(Request $request, Order $order)
    {
        // Pastikan customer yang login adalah pemilik order
        if ($request->user()->id !== $order->customer_id) {
            return response()->json([
                'message' => 'Unauthorized'
            ], 403);
        }

        return response()->json([
            'data' => $order->rating
        ]);
    }
}