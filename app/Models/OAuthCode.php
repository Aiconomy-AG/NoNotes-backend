<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class OAuthCode extends Model
{
    protected $table = 'oauth_codes';

    protected $primaryKey = 'code';

    public $incrementing = false;

    public $timestamps = false;

    protected $keyType = 'string';

    protected $guarded = [];
}
