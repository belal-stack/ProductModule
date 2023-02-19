<?php

namespace App\Models;

use App\Observers\ProductObserver;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Notifications\Notifiable;

class Product extends Model
{
    use HasFactory, Notifiable;

    protected static function boot()
    {
        parent::boot();

        static::observe(ProductObserver::class);
    }

    protected $fillable = ['name', 'price', 'status', 'type', 'user_id'];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function scopeFilterByName($query, $name)
    {
        return $query->where('name', 'like', '%'.$name.'%');
    }

    public function scopeFilterByAddedBy($query, $added_by)
    {
        return $query->where('added_by', $added_by);
    }

    public function history(): HasMany
    {
        return $this->hasMany(ProductHistory::class);
    }
    public function addHistory($user_id,$product_id, $status)
    {
        $history = new ProductHistory();
        $history->user_id = $user_id;
        $history->product_id = $product_id;
        $history->status = $status;

        $this->history()->save($history);
    }
}
