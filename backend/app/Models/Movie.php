<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Movie extends Model
{
    use HasFactory;

    protected $fillable = [
        'title',
        'description',
        'year',
        'genre',
        'director',
        'poster_url',
        'genres',
        'rating',
        'duration',
        'synopsis',
        'cast',
    ];

    protected function casts(): array
    {
        return [
            'genres' => 'array',
            'cast' => 'array',
            'rating' => 'decimal:1',
            'year' => 'integer',
        ];
    }

    public function watchlists(): HasMany
    {
        return $this->hasMany(Watchlist::class);
    }

    public function watcheds(): HasMany
    {
        return $this->hasMany(Watched::class);
    }
}
