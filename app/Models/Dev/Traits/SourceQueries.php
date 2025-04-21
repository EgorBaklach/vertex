<?php namespace App\Models\Dev\Traits;

use Illuminate\Support\Facades\DB;

trait SourceQueries
{
    public function inset(string $field): int
    {
        return $this->upsert([['id' => $this->id, 'last_request' => date('Y-m-d H:i:s')]], [], ['process' => DB::raw('process-1'), $field => DB::raw($field.'+1'), 'last_request']);
    }
}
