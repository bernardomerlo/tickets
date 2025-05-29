<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Enums\TicketType;

/**
 * @OA\Schema(
 *     schema="Ticket",
 *     type="object",
 *     title="Ticket",
 *     required={"id", "title", "description", "type", "created_by"},
 *     @OA\Property(property="id", type="integer", example=1),
 *     @OA\Property(property="title", type="string", example="Erro no sistema"),
 *     @OA\Property(property="description", type="string", example="O sistema está travando quando clico em salvar."),
 *     @OA\Property(property="type", type="string", example="ti"),
 *     @OA\Property(property="opened_at", type="string", format="date-time", example="2024-05-28T12:34:56"),
 *     @OA\Property(property="closed_at", type="string", format="date-time", nullable=true, example=null),
 *     @OA\Property(property="assigned_user_id", type="integer", nullable=true, example=2),
 *     @OA\Property(property="created_by", type="integer", example=5),
 * )
 */
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

    public function messages()
    {
        return $this->hasMany(TicketMessage::class);
    }
}
