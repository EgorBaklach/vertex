<?php namespace App\Models;

use Laravel\Sanctum\PersonalAccessToken;

class ApiAccessToken extends PersonalAccessToken
{
    protected $table = 'personal_access_tokens';
}