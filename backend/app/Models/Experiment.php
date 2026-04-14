<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Experiment extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'description',
        'hypothesis',
        'default_protocol',
        'default_config',
    ];

    protected function casts(): array
    {
        return [
            'default_config' => 'array',
        ];
    }

    public function runs(): HasMany
    {
        return $this->hasMany(Run::class);
    }
}
