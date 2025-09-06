<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

/**
 * App\Models\Backup
 *
 * @property int $id
 * @property int $user_id
 * @property int|null $domain_id
 * @property string $backup_type
 * @property string $filename
 * @property string $file_path
 * @property float $size_mb
 * @property string $status
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * 
 * @property-read \App\Models\User $user
 * @property-read \App\Models\Domain|null $domain
 */
class Backup extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'domain_id',
        'backup_type',
        'filename',
        'file_path',
        'size_mb',
        'status',
    ];

    protected $casts = [
        'created_at' => 'datetime',
        'completed_at' => 'datetime',
        'size_mb' => 'float',
    ];

    // Relationships
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function domain()
    {
        return $this->belongsTo(Domain::class);
    }
}
