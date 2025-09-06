<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

/**
 * App\Models\EmailAccount
 *
 * @property int $id
 * @property string $email
 * @property string $username
 * @property string $password
 * @property int $user_id
 * @property int|null $domain_id
 * @property int $quota_mb
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * 
 * @property-read \App\Models\User $user
 * @property-read \App\Models\Domain|null $domain
 */
class EmailAccount extends Model
{
    use HasFactory;

    protected $fillable = [
        'email',
        'password',
        'user_id',
        'domain_id',
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

    /**
     * Relationships
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function domain()
    {
        return $this->belongsTo(Domain::class);
    }
}
