<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Movie;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Http\Client\ConnectionException;

class RecommendationController extends Controller
{
    public function recommend(Request $request)
    {
        $validated = $request->validate([
            'preferences' => ['required', 'string', 'min:3', 'max:1000'],
        ]);

        $apiKey = config('services.gemini.api_key');
        $model = config('services.gemini.model');

        if (!$apiKey) {
            Log::warning('Gemini API key is not configured. Using fallback recommendations.');

            return $this->fallbackRecommendations();
        }

        // Retrieve the available FilmLog catalogue.
        $movies = Movie::query()
            ->get([
                'id',
                'title',
                'year',
                'genre',
                'genres',
                'director',
                'rating',
                'duration',
                'synopsis',
                'description',
                'cast',
                'poster_url',
            ]);

        if ($movies->isEmpty()) {
            return response()->json([
                'message' => 'No movies are available.',
            ], 404);
        }

        $catalogue = $movies->map(function ($movie) {
            return [
                'id' => $movie->id,
                'title' => $movie->title,
                'year' => $movie->year,
                'genres' => $movie->genres ?? [$movie->genre],
                'director' => $movie->director,
                'rating' => $movie->rating,
                'duration' => $movie->duration,
                'synopsis' => $movie->synopsis ?? $movie->description,
                'cast' => $movie->cast,
            ];
        })->values()->all();

        $prompt = "
            You are a movie recommendation assistant for FilmLog.

            Recommend up to 5 movies from the catalogue below.

            User preferences:
            {$validated['preferences']}

            Available movie catalogue:
            " . json_encode($catalogue, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . "

            Rules:
            - Only recommend movies whose IDs exist in the supplied catalogue.
            - Never invent movies or movie IDs.
            - Do not recommend the same movie more than once.
            - Give a short, relevant explanation for each recommendation.
            - Return only valid JSON in this exact structure:
            {
                \"recommendations\": [
                    {
                        \"movie_id\": 1,
                        \"reason\": \"A short explanation.\"
                    }
                ]
            }
            ";

        try {
            $response = Http::connectTimeout(10)
                ->timeout(25)
                ->acceptJson()
                ->withHeaders([
                    'x-goog-api-key' => $apiKey,
                ])
                ->post(
                    "https://generativelanguage.googleapis.com/v1beta/models/{$model}:generateContent",
                    [
                        'contents' => [
                            [
                                'parts' => [
                                    ['text' => $prompt],
                                ],
                            ],
                        ],
                        'generationConfig' => [
                            'responseMimeType' => 'application/json',
                        ],
                    ]
                );
        } catch (ConnectionException $e) {
            Log::error('Gemini connection failed.', [
                'error' => $e->getMessage(),
                'exception' => get_class($e),
            ]);

            return $this->fallbackRecommendations();
        }

        if ($response->failed()) {
            Log::warning('Gemini recommendation request failed.', [
                'status' => $response->status(),
                'body' => $response->json(),
            ]);

            return $this->fallbackRecommendations();
        }

        $generatedText = data_get(
            $response->json(),
            'candidates.0.content.parts.0.text'
        );

        if (!is_string($generatedText)) {
            Log::warning('Gemini returned an invalid recommendation response.');

            return $this->fallbackRecommendations();
        }

        $result = json_decode($generatedText, true);

        if (!is_array($result) || !isset($result['recommendations'])
            || !is_array($result['recommendations'])) {
            Log::warning('Gemini returned unparseable recommendations.');

            return $this->fallbackRecommendations();
        }

        // Only accept IDs from our actual database catalogue.
        $validMovies = $movies->keyBy('id');
        $seenIds = [];
        $recommendations = [];

        foreach ($result['recommendations'] as $item) {
            if (!is_array($item)) {
                continue;
            }

            $movieId = filter_var(
                $item['movie_id'] ?? null,
                FILTER_VALIDATE_INT
            );

            if (
                !$movieId ||
                !$validMovies->has($movieId) ||
                in_array($movieId, $seenIds, true)
            ) {
                continue;
            }

            $seenIds[] = $movieId;

            $recommendations[] = [
                'movie' => $validMovies->get($movieId),
                'reason' => mb_substr(
                    (string) ($item['reason'] ?? 'Recommended based on your preferences.'),
                    0,
                    500
                ),
            ];

            if (count($recommendations) >= 5) {
                break;
            }
        }

        if (empty($recommendations)) {
            Log::warning('Gemini returned no valid movie recommendations.');

            return $this->fallbackRecommendations();
        }

        return response()->json([
            'recommendations' => $recommendations,
        ]);
    }
    private function fallbackRecommendations()
    {
        $recommendations = Movie::inRandomOrder()
            ->limit(5)
            ->get()
            ->map(function ($movie) {
                return [
                    'movie' => $movie,
                    'reason' => 'A random pick from our movie collection.',
                ];
            });

        if ($recommendations->isEmpty()) {
            return response()->json([
                'message' => 'No movies are available.',
            ], 404);
        }

        return response()->json([
            'recommendations' => $recommendations,
            'fallback' => true,
            'message' => 'AI is temporarily unavailable. Showing random picks instead.',
        ]);
    }
}
