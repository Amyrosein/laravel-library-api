<?php

namespace App\Http\Controllers;

use App\Models\Author;
use App\Models\Book;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class BookController extends Controller
{
    private function formatBook($book)
    {
        $authors = $book->authors()->pluck("name");

        return [
            "title"        => $book->title,
            "publish_date" => $book->publish_date,
            "isbn"         => $book->isbn,
            "price"        => $book->price,
            "authors"      => $book->authors->pluck("name"),
        ];
    }

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $query = Book::query();

        if ($request->has('search')) {
            $searchTerm = $request->input('search');
            $query->where('title', 'like', "%$searchTerm%");
        }

        if ($request->has('min_price')) {
            $minPrice = $request->input('min_price');
            $query->where('price', '>=', $minPrice);
        }

        if ($request->has('max_price')) {
            $maxPrice = $request->input('max_price');
            $query->where('price', '<=', $maxPrice);
        }

        if ($request->has('sort')) {
            $sort = $request->input('sort');
            $query->orderBy('price', $sort);
        }

        $books = $query->get();
        $formattedBooks = $books->map(function ($book) {
            return $this->formatBook($book);
        });

        return response()->json($formattedBooks, Response::HTTP_OK);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'title'        => 'required',
            'authors'      => 'required|array',
            'publish_date' => 'required',
            'isbn'         => 'required|unique:App\Models\Book|digits:13',
            'price'        => 'required',
        ]);

        $book = Book::create([
            'title'        => $validated['title'],
            'publish_date' => $validated['publish_date'],
            'isbn'         => $validated['isbn'],
            'price'        => $validated['price'],
        ]);

        $book->authors()->attach($validated['authors']);
        $bookData = $this->formatBook($book);

        return response()->json($bookData, Response::HTTP_CREATED);
    }

    /**
     * Display the specified resource.
     */
    public function show($bookId) : JsonResponse
    {
        $book = Book::find($bookId);
        if ($book)
            return response()->json($this->formatBook($book), Response::HTTP_OK);
        return \response()->json(['error' => 'Book not found'], Response::HTTP_NOT_FOUND);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Book $book)
    {
        $validated = $request->validate([
            'title'        => 'sometimes',
            'author'       => 'sometimes',
            'isbn'         => 'sometimes|unique:App\Models\Book|digits:13',
            'is_available' => 'sometimes',
        ]);
        $book->update($validated);

        return $book;
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Book $book)
    {
        $book->delete();

        return response()->noContent();
    }
}
