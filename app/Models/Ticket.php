<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Enums\TicketType;

class Ticket extends Model
{
    use HasFactory;

    protected $fillable = [
        'title',
        'description',
        'opened_at',
        'closed_at',
        'type',
        'assigned_user_id',
        'created_by',
    ];

    protected $casts = [
        'opened_at' => 'datetime',
        'closed_at' => 'datetime',
        'type' => TicketType::class,
    ];

    public function assignee()
    {
        return $this->belongsTo(User::class, 'assigned_user_id');
    }

    public function author()
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
