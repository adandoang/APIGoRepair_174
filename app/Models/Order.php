<?php

// app/Models/Order.php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Order extends Model
{
    use HasFactory;

    /**
     * Properti $fillable untuk Mass Assignment
     */
    protected $fillable = [
        'customer_id',
        'technician_id',
        'category_id',
        'description',
        'address',
        'latitude',
        'longitude',
        'status',
        'payment_proof_url',
        'is_payment_validated',
        'technician_notes',
    ];

    /**
     * Relasi ke User (sebagai Pelanggan)
     * Sebuah Order dimiliki oleh satu User.
     */
    public function customer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'customer_id');
    }

    /**
     * Relasi ke User (sebagai Teknisi)
     * Sebuah Order dimiliki oleh satu User.
     */
    public function technician(): BelongsTo
    {
        return $this->belongsTo(User::class, 'technician_id');
    }

    /**
     * Relasi ke Category
     * Sebuah Order termasuk dalam satu Category.
     */
    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    /**
     * Relasi ke OrderPhoto
     * Sebuah Order bisa memiliki banyak foto.
     */
    public function photos(): HasMany
    {
        return $this->hasMany(OrderPhoto::class);
    }

    public function rating()
    {
        return $this->hasOne(ServiceRating::class);
    }
}
