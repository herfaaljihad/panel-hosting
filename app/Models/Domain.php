<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

/**
 * App\Models\Domain
 *
 * @property int $id
 * @property string $name
 * @property int $user_id
 * @property string|null $document_root
 * @property string $status
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * 
 * @property-read \App\Models\User $user
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Database> $databases
 * @property-read int|null $databases_count
 */
class Domain extends Model
{
    use HasFactory;

    protected $fillable = [
        "name",
        "user_id",
        "document_root",
        "status",
        "ip_address_id"
    ];

    protected $casts = [
        // status is string, not boolean
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function dnsRecords()
    {
        return $this->hasMany(DnsRecord::class);
    }

    public function sslCertificates()
    {
        return $this->hasMany(SslCertificate::class);
    }

    public function ipAddress()
    {
        return $this->belongsTo(IpAddress::class);
    }

    public function ftpAccounts()
    {
        return $this->hasMany(FtpAccount::class);
    }

    public function activeSslCertificate()
    {
        return $this->sslCertificates()->where("status", "active")->first();
    }

    public function hasSsl()
    {
        return $this->activeSslCertificate() !== null;
    }
}
