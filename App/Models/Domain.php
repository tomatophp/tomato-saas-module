<?php

namespace Modules\TomatoSaaS\App\Models;

use Illuminate\Database\Eloquent\Model;

class Domain extends Model
{
    protected $fillable = [
        'domain',
        'tenant_id',
    ];

    protected $dates = [
        'created_at',
        'updated_at',

    ];
}
