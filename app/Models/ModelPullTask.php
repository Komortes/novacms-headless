<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ModelPullTask extends Model
{
    /** @var list<string> */
    protected $fillable = ['model', 'status', 'progress', 'status_text', 'error'];

    public function isActive(): bool
    {
        return in_array($this->status, ['pending', 'pulling'], true);
    }

    public function isSuccess(): bool
    {
        return $this->status === 'success';
    }

    public function isFailed(): bool
    {
        return $this->status === 'failed';
    }
}
