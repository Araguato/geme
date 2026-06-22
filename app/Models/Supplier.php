<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\Party;
use App\Models\SupplierInvoice;

class Supplier extends Model
{
    use HasFactory;

    protected $fillable = [
        'party_id',
        'contact_name',
        'payment_terms',
        'default_currency',
    ];

    public function party()
    {
        return $this->belongsTo(Party::class);
    }

    public function invoices()
    {
        return $this->hasMany(SupplierInvoice::class);
    }
}
