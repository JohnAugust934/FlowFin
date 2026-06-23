<?php

namespace App\Models;

use App\Observers\TransactionObserver;
use Database\Factories\TransactionFactory;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

#[ObservedBy([TransactionObserver::class])]
class Transaction extends Model
{
    /** @use HasFactory<TransactionFactory> */
    use HasFactory, SoftDeletes;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'user_id',
        'category_id',
        'recurrence_id',
        'type',
        'amount',
        'date',
        'description',
        'classification',
        'is_recurring',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'date' => 'date',
            // Valor sempre em centavos (inteiro).
            'amount' => 'integer',
            'is_recurring' => 'boolean',
        ];
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * @return BelongsTo<Category, $this>
     */
    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    /**
     * @return BelongsTo<Recurrence, $this>
     */
    public function recurrence(): BelongsTo
    {
        return $this->belongsTo(Recurrence::class);
    }
}
