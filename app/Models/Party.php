<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\Supplier;
use App\Models\Employee;

class Party extends Model
{
    use HasFactory;

    protected $fillable = [
        'type',
        'name',
        'document_type',
        'document_number',
        'phone',
        'email',
        'address',
        'notes',
        'is_active',
        'marketing_opt_in',
        'marketing_opt_in_at',
        'marketing_opt_in_source',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'marketing_opt_in' => 'boolean',
        'marketing_opt_in_at' => 'datetime',
    ];

    public function orders()
    {
        return $this->hasMany(Order::class, 'customer_party_id');
    }

    public function scopeCustomers($query)
    {
        return $query->where('type', 'customer');
    }

    /** Registra o retira el consentimiento de marketing, con fecha y origen. */
    public function setMarketingConsent(bool $optIn, string $source): void
    {
        $this->forceFill([
            'marketing_opt_in' => $optIn,
            'marketing_opt_in_at' => $optIn ? now() : null,
            'marketing_opt_in_source' => $optIn ? $source : null,
        ])->save();
    }

    public function supplier()
    {
        return $this->hasOne(Supplier::class);
    }

    public function employee()
    {
        return $this->hasOne(Employee::class);
    }
}
