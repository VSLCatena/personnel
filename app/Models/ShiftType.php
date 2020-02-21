<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ShiftType extends Model
{
    protected $fillable = [
        'common', 'committee_id', 'title','description','updated_by'
    ];
    public $table = "shift_type";
	public function shift()
    {
        return $this->hasMany(Shift::class);
    }
	public function committee()
    {
        return $this->belongsTo(Committee::class);
    }

	public function user()
    {
        return $this->belongsTo(User::class,"updated_by");
    }
}
