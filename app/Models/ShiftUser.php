<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\Pivot;

class ShiftUser extends Pivot
{
    public $table = "shift_user";   
}
