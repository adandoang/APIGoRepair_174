<?php

// app/Models/Category.php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Category extends Model
{
    use HasFactory;

    protected $fillable = ['name', 'description'];

    /**
     * Relasi: Satu Kategori bisa dimiliki oleh banyak Order.
     */
    public function orders(): HasMany
    {
        return $this->hasMany(Order::class);
    }
}