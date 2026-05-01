<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BankAccount extends Model
{
    use HasFactory;

    protected $fillable = [
        'employee_id',
        'bank_name',
        'account_number',
        'account_holder',
    ];

    public function employee()
    {
        return $this->belongsTo(Employee::class);
    }
}
