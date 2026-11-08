<?php

namespace Tests\Feature;

use App\Models\Movie;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class RecommendationTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_returns_valid_movie_recommendations(): void
    {
        $user = User::factory()->create();

        $movie = Movie::forceCreate([
            'title' => 'Inception',
            'year' => 2010,
            'genre' => 'Sci-Fi',
            'director' => 'Christopher Nolan',
        ]);

        Http::fake([
            'generativelanguage.googleapis.com/*' => Http::response([
                'candidates' => [
                    [
                        'content' => [
                            'parts' => [
                                [
                                    'text' => json_encode([
                                        'recommendations' => [
                                            [
                                                'movie_id' => $movie->id,
                                                'reason' => 'A mind-bending science fiction movie.',
                                            ],
                                        ],
                                    ]),
                                ],
                            ],
                        ],
                    ],
                ],
            ], 200),
        ]);

        $response = $this->actingAs($user, 'api')
            ->postJson('/api/movies/recommend', [
                'preferences' => 'Mind-bending science fiction movies',
            ]);

        $response->assertOk()
            ->assertJsonCount(1, 'recommendations')
            ->assertJsonPath('recommendations.0.movie.id', $movie->id)
            ->assertJsonPath(
                'recommendations.0.movie.title',
                'Inception'
            )
            ->assertJsonPath(
                'recommendations.0.reason',
                'A mind-bending science fiction movie.'
            );

        Http::assertSentCount(1);
    }

    public function test_it_rejects_invalid_movie_ids(): void
    {
        $user = User::factory()->create();

        $movie = Movie::forceCreate([
            'title' => 'Inception',
            'year' => 2010,
        ]);

        Http::fake([
            'generativelanguage.googleapis.com/*' => Http::response([
                'candidates' => [
                    [
                        'content' => [
                            'parts' => [
                                [
                                    'text' => json_encode([
                                        'recommendations' => [
                                            [
                                                'movie_id' => $movie->id,
                                                'reason' => 'Valid recommendation.',
                                            ],
                                            [
                                                'movie_id' => 999999,
                                                'reason' => 'Invalid movie.',
                                            ],
                                            [
                                                'movie_id' => $movie->id,
                                                'reason' => 'Duplicate movie.',
                                            ],
                                        ],
                                    ]),
                                ],
                            ],
                        ],
                    ],
                ],
            ], 200),
        ]);

        $response = $this->actingAs($user, 'api')
            ->postJson('/api/movies/recommend', [
                'preferences' => 'Science fiction',
            ]);

        $response->assertOk()
            ->assertJsonCount(1, 'recommendations')
            ->assertJsonPath('recommendations.0.movie.id', $movie->id);
    }

    public function test_it_handles_gemini_quota_errors(): void
    {
        $user = User::factory()->create();

        Movie::forceCreate([
            'title' => 'Inception',
        ]);

        Http::fake([
            'generativelanguage.googleapis.com/*' =>
                Http::response(['error' => ['message' => 'Quota exceeded']], 429),
        ]);

        $response = $this->actingAs($user, 'api')
            ->postJson('/api/movies/recommend', [
                'preferences' => 'Science fiction',
            ]);

        $response->assertOk()
            ->assertJsonPath('fallback', true)
            ->assertJsonPath(
                'message',
                'AI is temporarily unavailable. Showing random picks instead.'
            )
            ->assertJsonStructure([
                'recommendations' => [
                    '*' => ['movie', 'reason'],
                ],
            ]);
    }

    public function test_it_falls_back_when_gemini_connection_fails(): void
    {
        $user = User::factory()->create();

        Movie::forceCreate([
            'title' => 'Inception',
        ]);

        Http::fake(function () {
            throw new ConnectionException('Connection failed.');
        });

        $response = $this->actingAs($user, 'api')
            ->postJson('/api/movies/recommend', [
                'preferences' => 'Science fiction',
            ]);

        $response->assertOk()
            ->assertJsonPath('fallback', true)
            ->assertJsonCount(1, 'recommendations');
    }

    public function test_it_falls_back_when_gemini_returns_service_unavailable(): void
    {
        $user = User::factory()->create();

        Movie::forceCreate([
            'title' => 'Inception',
        ]);

        Http::fake([
            'generativelanguage.googleapis.com/*' =>
                Http::response(['error' => ['message' => 'Unavailable']], 503),
        ]);

        $response = $this->actingAs($user, 'api')
            ->postJson('/api/movies/recommend', [
                'preferences' => 'Science fiction',
            ]);

        $response->assertOk()
            ->assertJsonPath('fallback', true)
            ->assertJsonCount(1, 'recommendations');
    }

    public function test_it_falls_back_when_gemini_returns_unparseable_recommendations(): void
    {
        $user = User::factory()->create();

        Movie::forceCreate([
            'title' => 'Inception',
        ]);

        Http::fake([
            'generativelanguage.googleapis.com/*' => Http::response([
                'candidates' => [
                    [
                        'content' => [
                            'parts' => [
                                ['text' => 'not valid JSON'],
                            ],
                        ],
                    ],
                ],
            ], 200),
        ]);

        $response = $this->actingAs($user, 'api')
            ->postJson('/api/movies/recommend', [
                'preferences' => 'Science fiction',
            ]);

        $response->assertOk()
            ->assertJsonPath('fallback', true)
            ->assertJsonCount(1, 'recommendations');
    }

    public function test_it_falls_back_when_gemini_returns_no_recommendations(): void
    {
        $user = User::factory()->create();

        Movie::forceCreate([
            'title' => 'Inception',
        ]);

        Http::fake([
            'generativelanguage.googleapis.com/*' => Http::response([
                'candidates' => [
                    [
                        'content' => [
                            'parts' => [
                                [
                                    'text' => json_encode([
                                        'recommendations' => [],
                                    ]),
                                ],
                            ],
                        ],
                    ],
                ],
            ], 200),
        ]);

        $response = $this->actingAs($user, 'api')
            ->postJson('/api/movies/recommend', [
                'preferences' => 'Science fiction',
            ]);

        $response->assertOk()
            ->assertJsonPath('fallback', true)
            ->assertJsonCount(1, 'recommendations');
    }

    public function test_it_falls_back_when_the_gemini_api_key_is_missing(): void
    {
        $user = User::factory()->create();

        Movie::forceCreate([
            'title' => 'Inception',
        ]);

        config()->set('services.gemini.api_key', null);
        Http::fake();

        $response = $this->actingAs($user, 'api')
            ->postJson('/api/movies/recommend', [
                'preferences' => 'Science fiction',
            ]);

        $response->assertOk()
            ->assertJsonPath('fallback', true)
            ->assertJsonCount(1, 'recommendations');

        Http::assertNothingSent();
    }

    public function test_it_returns_not_found_when_no_movies_are_available_for_fallback(): void
    {
        $user = User::factory()->create();

        config()->set('services.gemini.api_key', null);
        Http::fake();

        $response = $this->actingAs($user, 'api')
            ->postJson('/api/movies/recommend', [
                'preferences' => 'Science fiction',
            ]);

        $response->assertNotFound()
            ->assertJsonPath('message', 'No movies are available.');

        Http::assertNothingSent();
    }

    public function test_it_validates_empty_preferences_without_calling_gemini(): void
    {
        $user = User::factory()->create();

        Http::fake();

        $response = $this->actingAs($user, 'api')
            ->postJson('/api/movies/recommend', [
                'preferences' => '',
            ]);

        $response->assertStatus(422);

        Http::assertNothingSent();
    }
}
