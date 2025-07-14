<?php
// File: app/Http/Controllers/Api/Admin/OrderController.php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Order;
use App\Models\User;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Log;

class OrderController extends Controller
{
    public function index(Request $request)
    {
        // Mulai query builder
        $query = Order::with('customer', 'category', 'technician', 'rating');

        // Filter berdasarkan kategori jika ada
        if ($request->has('category_id')) {
            $query->where('category_id', $request->category_id);
        }

        // Filter pencarian jika ada
        if ($request->has('search')) {
            $searchTerm = $request->search;
            // Cari berdasarkan nama pelanggan atau ID order
            $query->where(function($q) use ($searchTerm) {
                $q->where('id', 'like', "%{$searchTerm}%")
                ->orWhereHas('customer', function($subQ) use ($searchTerm) {
                    $subQ->where('name', 'like', "%{$searchTerm}%");
                });
            });
        }

        // Ambil hasil query yang sudah difilter dan urutkan
        $orders = $query->latest()->get();

        return response()->json([
            'message' => 'Semua data order berhasil diambil',
            'data' => $orders
        ]);
    }

    /**
     * Update status order dan menugaskan teknisi.
     */
    public function update(Request $request, Order $order)
    {
        Log::info('Update Order Request Data:', $request->all());
        // Gunakan Validator manual agar lebih fleksibel
        $validator = Validator::make($request->all(), [
            'status' => 'required|in:pending,processed,assigned,in_progress,completed,cancelled',
            'technician_id' => 'nullable|exists:users,id', // Cek apakah ID user ada
        ]);

        // Tambahkan aturan validasi custom
        $validator->after(function ($validator) use ($request) {
            // Aturan 1: Jika status 'assigned', maka teknisi tidak boleh kosong.
            if ($request->status == 'assigned' && is_null($request->technician_id)) {
                $validator->errors()->add(
                    'technician_id', 'Teknisi wajib dipilih jika status adalah assigned.'
                );
            }
            // Aturan 2: Jika teknisi dipilih, pastikan rolenya adalah 'technician'.
            if (!is_null($request->technician_id)) {
                $technician = User::find($request->technician_id);
                if ($technician && $technician->role !== 'technician') {
                    $validator->errors()->add(
                        'technician_id', 'User yang dipilih bukanlah seorang teknisi.'
                    );
                }
            }
        });

        // Jika validasi gagal, kembalikan error
        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        // --- Logika Update (tetap sama) ---
        $order->status = $request->status;

        if ($request->has('technician_id')) {
            $order->technician_id = $request->technician_id;
        }

        $order->save();
        $order->load('customer', 'category', 'technician', 'rating');

        return response()->json([
            'message' => 'Order berhasil diperbarui',
            'data' => $order
        ]);
    }

    public function validatePayment(Order $order)
    {
        // Pastikan ada bukti bayar yang diunggah sebelum divalidasi
        if (is_null($order->payment_proof_url)) {
            return response()->json(['message' => 'Pelanggan belum mengunggah bukti pembayaran.'], 422);
        }

        // Ubah status validasi menjadi true
        $order->is_payment_validated = true;
        $order->save();

        return response()->json([
            'message' => 'Pembayaran berhasil divalidasi.',
            'data' => $order->fresh()->load('customer', 'category', 'technician', 'rating')
        ]);
    }

    public function downloadInvoice(Order $order)
    {
        // Load view 'invoices.order' dan teruskan data order
        $pdf = PDF::loadView('invoices.order', ['order' => $order]);

        // Unduh file PDF
        return $pdf->download('invoice-order-'.$order->id.'.pdf');
    }

    public function show(Order $order)
    {
        if ($order->status == 'pending') {
            $order->status = 'processed';
            $order->save();
        }
        // Admin bisa melihat order manapun, jadi tidak perlu cek kepemilikan
        // Muat semua relasi yang dibutuhkan
        $order->load('customer', 'category', 'technician', 'photos', 'rating');
        return response()->json(['data' => $order]);
    }

    public function setInvoiceAmount(Request $request, Order $order)
    {
        $request->validate([
            'invoice_amount' => 'required|numeric|min:0',
        ]);
        $order->invoice_amount = $request->invoice_amount;
        $order->save();

        return response()->json([
            'message' => 'Harga invoice berhasil disimpan',
            'data' => $order->fresh()->load('rating')
        ]);
    }
}