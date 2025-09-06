<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

/**
 * App\Models\CronJob
 *
 * @property int $id
 * @property int $user_id
 * @property int|null $domain_id
 * @property string $domain
 * @property string $command
 * @property string $schedule
 * @property string|null $email_output
 * @property bool $is_active
 * @property int $success_count
 * @property int $failure_count
 * @property \Illuminate\Support\Carbon|null $last_run_at
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * 
 * @property-read \App\Models\User $user
 * @property-read \App\Models\Domain|null $domainModel
 */
class CronJob extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'domain_id',
        'command',
        'schedule',
        'email_output',
        'is_active',
        'success_count',
        'failure_count',
        'last_run_at',
        'next_run_at',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'email_output' => 'boolean',
        'success_count' => 'integer',
        'failure_count' => 'integer',
        'last_run_at' => 'datetime',
        'next_run_at' => 'datetime',
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
