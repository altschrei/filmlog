<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Movie;
use Illuminate\Http\Request;

class MovieController extends Controller
{
    /**
     * Display all movies.
     * Supports search, genre filtering, and random ordering.
     */
    public function index(Request $request)
    {
        $query = Movie::query();

        // Filter by genre
        if ($request->filled('genre')) {
            $genre = $request->input('genre');

            $query->where(function ($q) use ($genre) {
                $q->whereJsonContains('genres', $genre)
                    ->orWhere('genre', 'LIKE', "%{$genre}%");
            });
        }

        // Search by title, director, or synopsis
        if ($request->filled('search')) {
            $search = $request->input('search');

            $query->where(function ($q) use ($search) {
                $q->where('title', 'LIKE', "%{$search}%")
                    ->orWhere('director', 'LIKE', "%{$search}%")
                    ->orWhere('synopsis', 'LIKE', "%{$search}%");
            });
        }

        // Optional random ordering: ?shuffle=true
        if ($request->boolean('shuffle')) {
            $query->inRandomOrder();
        }

        return response()->json($query->get());
    }

    /**
     * Display details of a specific movie.
     */
    public function show($id)
    {
        $movie = Movie::find($id);

        if (!$movie) {
            return response()->json([
                'message' => 'Movie not found.',
            ], 404);
        }

        return response()->json($movie);
    }
}
