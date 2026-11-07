<?php

namespace Database\Seeders;

use App\Models\Movie;
use Illuminate\Database\Seeder;
use RuntimeException;

class MovieSeeder extends Seeder
{
    public function run(): void
    {
        $filePath = dirname(base_path()) . DIRECTORY_SEPARATOR
            . 'data' . DIRECTORY_SEPARATOR . 'movies.json';

        if (!file_exists($filePath)) {
            throw new RuntimeException("Movies JSON file not found: {$filePath}");
        }

        $movies = json_decode(
            file_get_contents($filePath),
            true,
            512,
            JSON_THROW_ON_ERROR
        );

        foreach ($movies as $movie) {
            Movie::updateOrCreate(
                ['title' => $movie['title']],
                [
                    'title' => $movie['title'],
                    'year' => $movie['year'] ?? null,
                    'director' => $movie['director'] ?? null,
                    'genres' => $movie['genres'] ?? [],
                    'genre' => isset($movie['genres'])
                        ? implode(', ', $movie['genres'])
                        : null,
                    'rating' => $movie['rating'] ?? null,
                    'duration' => $movie['duration'] ?? null,
                    'synopsis' => $movie['synopsis'] ?? null,
                    'description' => $movie['synopsis'] ?? null,
                    'poster_url' => $movie['poster_url'] ?? null,
                    'cast' => $movie['cast'] ?? [],
                ]
            );
        }

        $this->command->info(count($movies) . ' movies imported successfully.');
    }
}
