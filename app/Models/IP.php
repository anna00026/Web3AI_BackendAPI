<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * @method static where(...$args)
 * @method static create(array $array)
 */
class IP extends Model
{
    use HasFactory;

    protected $table = 'ips';

    protected $fillable = [
        'ip_address',
        'user_id',
    ];
}
