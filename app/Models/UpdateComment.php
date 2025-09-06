<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * UpdateComment Model
 * 
 * @property int $id
 * @property int $plugin_id
 * @property int $user_id
 * @property string $comment_type
 * @property string $title
 * @property string $message
 * @property string $priority
 * @property string $status
 * @property bool $action_required
 * @property bool $auto_resolve
 * @property \Carbon\Carbon|null $resolved_at
 * @property int|null $resolved_by
 * @property array|null $metadata
 * @property \Carbon\Carbon|null $created_at
 * @property \Carbon\Carbon|null $updated_at
 * 
 * @property-read \App\Models\Plugin $plugin
 * @property-read \App\Models\User $user
 * @property-read \App\Models\User|null $resolver
 */
class UpdateComment extends Model
{
    use HasFactory;

    protected $fillable = [
        'plugin_id',
        'user_id',
        'comment_type',
        'title',
        'message',
        'priority',
        'status',
        'action_required',
        'auto_resolve',
        'resolved_at',
        'resolved_by',
        'metadata'
    ];

    protected $casts = [
        'metadata' => 'array',
        'action_required' => 'boolean',
        'auto_resolve' => 'boolean',
        'resolved_at' => 'datetime'
    ];

    const TYPE_UPDATE_AVAILABLE = 'update_available';
    const TYPE_UPDATE_FAILED = 'update_failed';
    const TYPE_UPDATE_SUCCESS = 'update_success';
    const TYPE_DEPENDENCY_CONFLICT = 'dependency_conflict';
    const TYPE_COMPATIBILITY_WARNING = 'compatibility_warning';
    const TYPE_SECURITY_UPDATE = 'security_update';
    const TYPE_BREAKING_CHANGE = 'breaking_change';

    const STATUS_PENDING = 'pending';
    const STATUS_ACKNOWLEDGED = 'acknowledged';
    const STATUS_RESOLVED = 'resolved';
    const STATUS_DISMISSED = 'dismissed';

    const PRIORITY_LOW = 1;
    const PRIORITY_MEDIUM = 2;
    const PRIORITY_HIGH = 3;
    const PRIORITY_CRITICAL = 4;

    /**
     * Plugin relationship
     */
    public function plugin()
    {
        return $this->belongsTo(Plugin::class);
    }

    /**
     * User who created the comment
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * User who resolved the comment
     */
    public function resolver()
    {
        return $this->belongsTo(User::class, 'resolved_by');
    }

    /**
     * Get priority color
     */
    public function getPriorityColor(): string
    {
        switch ($this->priority) {
            case self::PRIORITY_CRITICAL:
                return 'danger';
            case self::PRIORITY_HIGH:
                return 'warning';
            case self::PRIORITY_MEDIUM:
                return 'info';
            case self::PRIORITY_LOW:
                return 'secondary';
            default:
                return 'secondary';
        }
    }

    /**
     * Get priority text
     */
    public function getPriorityText(): string
    {
        switch ($this->priority) {
            case self::PRIORITY_CRITICAL:
                return 'Critical';
            case self::PRIORITY_HIGH:
                return 'High';
            case self::PRIORITY_MEDIUM:
                return 'Medium';
            case self::PRIORITY_LOW:
                return 'Low';
            default:
                return 'Unknown';
        }
    }

    /**
     * Get status color
     */
    public function getStatusColor(): string
    {
        switch ($this->status) {
            case self::STATUS_PENDING:
                return 'warning';
            case self::STATUS_ACKNOWLEDGED:
                return 'info';
            case self::STATUS_RESOLVED:
                return 'success';
            case self::STATUS_DISMISSED:
                return 'secondary';
            default:
                return 'secondary';
        }
    }

    /**
     * Mark as resolved
     */
    public function markAsResolved($userId = null)
    {
        $this->update([
            'status' => self::STATUS_RESOLVED,
            'resolved_at' => now(),
            'resolved_by' => $userId ?: auth()->id()
        ]);
    }

    /**
     * Get unresolved comments
     */
    public static function getUnresolved()
    {
        return self::whereIn('status', [self::STATUS_PENDING, self::STATUS_ACKNOWLEDGED])
                   ->orderBy('priority', 'desc')
                   ->orderBy('created_at', 'desc')
                   ->get();
    }

    /**
     * Get critical comments
     */
    public static function getCritical()
    {
        return self::where('priority', self::PRIORITY_CRITICAL)
                   ->whereIn('status', [self::STATUS_PENDING, self::STATUS_ACKNOWLEDGED])
                   ->get();
    }
}
