<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class WatchedController extends Controller
{
    /**
     * Get all watched movies belonging to the authenticated user.
     */
    public function index(Request $request)
    {
        $watched = $request->user()
            ->watcheds()
            ->with('movie')
            ->get();

        return response()->json($watched);
    }

    /**
     * Add a watched movie or update its existing review.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'movieId' => 'required|integer|exists:movies,id',
            'rating' => 'required|integer|between:1,5',
            'review' => 'nullable|string',
        ]);

        $watched = $request->user()->watcheds()->updateOrCreate(
            [
                'movie_id' => $validated['movieId'],
            ],
            [
                'rating' => $validated['rating'],
                'review' => $validated['review'] ?? null,
            ]
        );

        $status = $watched->wasRecentlyCreated ? 201 : 200;

        return response()->json([
            'message' => $status === 201
                ? 'Movie marked as watched.'
                : 'Watched movie review updated.',
            'data' => $watched->load('movie'),
        ], $status);
    }
}
