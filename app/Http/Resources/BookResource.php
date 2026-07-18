<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class BookResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'title' => $this->title,
            'author' => $this->author,
            'isbn' => $this->isbn,
            'published_date' => $this->published_date,
            'description' => $this->description,
            'image_url' => $this->image_url,
            'created_by' => $this->created_by,
            'average_rating' => $this->reviews_avg_rating,
            'review_count' => $this->reviews_count,
            'genres' => $this->genres->map(function ($genre) {
                return [
                    'id' => $genre->id,
                    'name' => $genre->name,
                ];
            }),

            'reviews' => $this->reviews->map(function ($review) {
                return [
                    'id' => $review->id,
                    'book_id' => $review->book_id,
                    'user_id' => $review->user_id,
                    'rating' => $review->rating,
                    'comment' => $review->comment,
                    'created_at' => $review->created_at,
                    'updated_at' => $review->updated_at,
                ];
            }),

            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
