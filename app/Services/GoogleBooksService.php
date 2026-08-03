<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;

class GoogleBooksService
{
    public function searchByIsbn(string $isbn): ?array
    {
        $response = Http::get('https://www.googleapis.com/books/v1/volumes', [
            'q' => 'isbn:'.$isbn,
            'key' => config('services.google_books.api_key'),
        ]);

        if ($response->failed()) {
            return null;
        }

        $volumeInfo = $response->json('items.0.volumeInfo');

        if (! $volumeInfo) {
            return null;
        }

        return [
            'title' => $volumeInfo['title'] ?? '',
            'author' => implode(', ', $volumeInfo['authors'] ?? []),
            'published_date' => $volumeInfo['publishedDate'] ?? null,
            'description' => $volumeInfo['description'] ?? '',
            'image_url' => $volumeInfo['imageLinks']['thumbnail'] ?? '',
        ];
    }
}
