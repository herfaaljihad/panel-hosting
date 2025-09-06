<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

/**
 * DnsRecord Model
 * 
 * @property int $id
 * @property int $domain_id
 * @property int $user_id
 * @property string $name
 * @property string $type
 * @property string $value
 * @property int|null $priority
 * @property int $ttl
 * @property bool $is_active
 * @property \Carbon\Carbon|null $created_at
 * @property \Carbon\Carbon|null $updated_at
 * 
 * @property-read \App\Models\Domain $domain
 * @property-read \App\Models\User $user
 */
class DnsRecord extends Model
{
    use HasFactory;

    protected $fillable = [
        'domain_id',
        'user_id',
        'name',
        'type',
        'value',
        'priority',
        'ttl',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'priority' => 'integer',
        'ttl' => 'integer',
    ];

    /**
     * Relationships
     */
    public function domain()
    {
        return $this->belongsTo(Domain::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Scopes
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeByType($query, $type)
    {
        return $query->where('type', $type);
    }
}
