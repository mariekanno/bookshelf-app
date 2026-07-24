<?php

namespace Tests\Feature;

use App\Models\Book;
use App\Models\Genre;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class GenreControllerTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function ログイン済みユーザーがジャンルを登録できる(): void
    {
        // Arrange
        $user = User::factory()->create();

        $genreData = [
            'name' => 'ミステリー',
        ];

        // Act
        $response = $this
            ->actingAs($user)
            ->post(route('genres.store'), $genreData);

        // Assert
        $this->assertDatabaseHas('genres', [
            'name' => 'ミステリー',
        ]);

        $response->assertRedirect(route('genres.index'));

        $response->assertSessionHas(
            'success',
            'ジャンルを作成しました。'
        );
    }

    /** @test */
    public function バリデーションエラー時はジャンル登録画面へリダイレクトされ、エラーが返る(): void
    {
        // Arrange
        $user = User::factory()->create();

        $invalidGenreData = [
            'name' => '',
        ];

        // Act
        $response = $this
            ->actingAs($user)
            ->from(route('genres.create'))
            ->post(route('genres.store'), $invalidGenreData);

        // Assert
        $response->assertRedirect(route('genres.create'));

        $response->assertSessionHasErrors([
            'name',
        ]);

        $this->assertDatabaseMissing('genres', [
            'name' => '',
        ]);
    }

    /** @test */
    public function ジャンルを更新できる(): void
    {
        // Arrange
        $user = User::factory()->create();

        $genre = Genre::factory()->create([
            'name' => '更新前のジャンル',
        ]);

        $updateGenreData = [
            'name' => '更新後のジャンル',
        ];

        // Act
        $response = $this
            ->actingAs($user)
            ->put(route('genres.update', $genre), $updateGenreData);

        // Assert
        $this->assertDatabaseHas('genres', [
            'id' => $genre->id,
            'name' => '更新後のジャンル',
        ]);

        $response->assertRedirect(route('genres.index'));

        $response->assertSessionHas(
            'success',
            'ジャンルを更新しました。'
        );
    }

    /** @test */
    public function バリデーションエラー時はジャンル編集画面へリダイレクトされ、エラーが返る(): void
    {
        // Arrange
        $user = User::factory()->create();

        $genre = Genre::factory()->create([
            'name' => 'Laravel',
        ]);

        $invalidGenreData = [
            'name' => '',
        ];

        // Act
        $response = $this
            ->actingAs($user)
            ->from(route('genres.edit', $genre))
            ->put(route('genres.update', $genre), $invalidGenreData);

        // Assert
        $response->assertRedirect(route('genres.edit', $genre));

        $response->assertSessionHasErrors([
            'name',
        ]);

        $this->assertDatabaseHas('genres', [
            'id' => $genre->id,
            'name' => 'Laravel',
        ]);
    }

    /** @test */
    public function ジャンルを削除できる(): void
    {
        // Arrange
        $user = User::factory()->create();

        $genre = Genre::factory()->create([
            'name' => '削除対象ジャンル',
        ]);

        // Act
        $response = $this
            ->actingAs($user)
            ->delete(route('genres.destroy', $genre));

        // Assert
        $this->assertDatabaseMissing('genres', [
            'id' => $genre->id,
        ]);

        $response->assertRedirect(route('genres.index'));

        $response->assertSessionHas(
            'success',
            'ジャンルを削除しました。'
        );
    }

    /** @test */
    public function 書籍に紐づくジャンルは削除できない(): void
    {
        // Arrange
        $user = User::factory()->create();

        $book = Book::factory()->create([
            'created_by' => $user->id,
        ]);

        $genre = Genre::factory()->create([
            'name' => '削除できないジャンル',
        ]);

        $book->genres()->attach($genre->id);

        // Act
        $response = $this
            ->actingAs($user)
            ->delete(route('genres.destroy', $genre));

        // Assert
        $this->assertDatabaseHas('genres', [
            'id' => $genre->id,
            'name' => '削除できないジャンル',
        ]);

        $this->assertDatabaseHas('book_genre', [
            'book_id' => $book->id,
            'genre_id' => $genre->id,
        ]);

        $response->assertRedirect(route('genres.index'));

        $response->assertSessionHas(
            'error',
            'このジャンルには書籍が紐づいているため削除できません。'
        );
    }
}
