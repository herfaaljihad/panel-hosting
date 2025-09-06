<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

/**
 * FtpAccount Model
 * 
 * @property int $id
 * @property int $user_id
 * @property int $domain_id
 * @property string $username
 * @property string $password
 * @property string $directory
 * @property int|null $quota_mb
 * @property bool $is_active
 * @property \Carbon\Carbon|null $created_at
 * @property \Carbon\Carbon|null $updated_at
 * 
 * @property-read \App\Models\User $user
 * @property-read \App\Models\Domain $domain
 */
class FtpAccount extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'domain_id',
        'username',
        'password',
        'directory',
        'quota_mb',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'quota_mb' => 'integer',
    ];

    protected $hidden = [
        'password',
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

    // Accessor for domain property
    public function getDomainAttribute()
    {
        return $this->domain();
    }
}
