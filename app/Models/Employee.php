<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\Party;
use App\Models\User;
use App\Models\EmploymentContract;
use App\Models\EmployeeIncident;
use App\Models\PayrollEntry;

class Employee extends Model
{
    use HasFactory;

    protected $fillable = [
        'party_id',
        'user_id',
        'role',
        'hire_date',
        'salary_type',
        'monthly_salary',
        'hourly_rate',
        'is_current',
    ];

    protected $casts = [
        'hire_date' => 'date',
        'is_current' => 'boolean',
    ];

    public function party()
    {
        return $this->belongsTo(Party::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function contracts()
    {
        return $this->hasMany(EmploymentContract::class);
    }

    public function incidents()
    {
        return $this->hasMany(EmployeeIncident::class);
    }

    public function payrollEntries()
    {
        return $this->hasMany(PayrollEntry::class);
    }
}
