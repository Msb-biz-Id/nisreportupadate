<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class ChatMemory extends Model
{
    use HasUuids;

    protected $table = 'chat_memories';

    protected $fillable = [
        'telegram_chat_id',
        'role',
        'content',
    ];
}
