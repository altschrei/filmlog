<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Movie;

class WatchlistController extends Controller
{
    /**
     * Get the authenticated user's watchlist.
     */
    public function index(Request $request)
    {
        $watchlist = $request->user()
            ->watchlists()
            ->with('movie')
            ->get();

        return response()->json($watchlist);
    }

    /**
     * Add a movie to the authenticated user's watchlist.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'movieId' => 'required|integer|exists:movies,id',
        ]);

        $watchlist = $request->user()->watchlists()->firstOrCreate([
            'movie_id' => $validated['movieId'],
        ]);

        return response()->json([
            'message' => 'Movie added to watchlist.',
            'data' => $watchlist->load('movie'),
        ], 201);
    }

    /**
     * Remove a movie from the authenticated user's watchlist.
     */
    public function destroy(Request $request, $movieId)
    {
        $watchlist = $request->user()
            ->watchlists()
            ->where('movie_id', $movieId)
            ->first();

        if (!$watchlist) {
            return response()->json([
                'message' => 'Movie not found in your watchlist.',
            ], 404);
        }

        $watchlist->delete();

        return response()->json([
            'message' => 'Movie removed from watchlist.',
        ]);
    }
}
