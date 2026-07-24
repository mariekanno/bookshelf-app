<?php

namespace Tests\Feature;

use App\Models\Book;
use App\Models\Review;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RankingControllerTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function レビュー平均評価が高い順にランキング表示される(): void
    {
        // Arrange
        $this->withoutVite();

        $book1 = Book::factory()->create([
            'title' => 'ランキング1位の書籍',
        ]);

        $book2 = Book::factory()->create([
            'title' => 'ランキング2位の書籍',
        ]);

        $book3 = Book::factory()->create([
            'title' => 'ランキング3位の書籍',
        ]);

        Review::factory()->create([
            'book_id' => $book1->id,
            'rating' => 5,
        ]);

        Review::factory()->create([
            'book_id' => $book2->id,
            'rating' => 4,
        ]);

        Review::factory()->create([
            'book_id' => $book3->id,
            'rating' => 3,
        ]);

        // Act
        $response = $this
            ->get(route('ranking.index'));

        // Assert
        $response->assertOk();

        $response->assertViewIs('ranking.index');

        $response->assertViewHas(
            'rankedBooks',
            function ($rankedBooks) use ($book1, $book2, $book3) {
                return $rankedBooks[0]->is($book1)
                    && $rankedBooks[1]->is($book2)
                    && $rankedBooks[2]->is($book3);
            }
        );
    }

    /** @test */
    public function レビュー平均評価上位10件の書籍を取得して表示できる(): void
    {
        // Arrange
        $this->withoutVite();

        $books = Book::factory()
            ->count(11)
            ->create();

        foreach ($books as $index => $book) {
            Review::factory()->create([
                'book_id' => $book->id,
                'rating' => ($index % 5) + 1,
            ]);
        }

        // Act
        $response = $this
            ->get(route('ranking.index'));

        // Assert
        $response->assertOk();

        $response->assertViewIs('ranking.index');

        $response->assertViewHas('rankedBooks', function ($rankedBooks) {
            return $rankedBooks->count() === 10;
        });
    }
}
