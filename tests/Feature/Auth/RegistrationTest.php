<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RegistrationTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function 登録画面を表示できる(): void
    {
        // Arrange
        $this->withoutVite();

        // Act
        $response = $this->get(route('register'));

        // Assert
        $response->assertStatus(200);
    }

    /** @test */
    public function 新規ユーザーを登録できる(): void
    {
        // Act
        $response = $this->post(route('register'), [
            'name' => 'テストユーザー',
            'email' => 'test@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ]);

        // Assert
        $response->assertRedirect(route('books.index'));
        $this->assertDatabaseHas('users', [
            'name' => 'テストユーザー',
            'email' => 'test@example.com',
        ]);
        $this->assertAuthenticated();
    }

    /** @test */
    public function 名前が空だとバリデーションエラーになる(): void
    {
        // Act
        $response = $this->post(route('register'), [
            'name' => '',
            'email' => 'test@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ]);

        // Assert
        $response->assertSessionHasErrors('name');
    }

    /** @test */
    public function メールアドレスが空だとバリデーションエラーになる(): void
    {
        // Act
        $response = $this->post(route('register'), [
            'name' => 'テストユーザー',
            'email' => '',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ]);

        // Assert
        $response->assertSessionHasErrors('email');
    }

    /** @test */
    public function 無効なメールアドレス形式だとバリデーションエラーになる(): void
    {
        // Act
        $response = $this->post(route('register'), [
            'name' => 'テストユーザー',
            'email' => 'invalid-email',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ]);

        // Assert
        $response->assertSessionHasErrors('email');
    }

    /** @test */
    public function 既に登録済みのメールアドレスだとバリデーションエラーになる(): void
    {
        // Arrange
        User::factory()->create(['email' => 'existing@example.com']);

        // Act
        $response = $this->post(route('register'), [
            'name' => 'テストユーザー',
            'email' => 'existing@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ]);

        // Assert
        $response->assertSessionHasErrors('email');
    }

    /** @test */
    public function パスワードが8文字未満だとバリデーションエラーになる(): void
    {
        // Act
        $response = $this->post(route('register'), [
            'name' => 'テストユーザー',
            'email' => 'test@example.com',
            'password' => 'short',
            'password_confirmation' => 'short',
        ]);

        // Assert
        $response->assertSessionHasErrors('password');
    }

    /** @test */
    public function パスワード確認が一致しないとバリデーションエラーになる(): void
    {
        // Act
        $response = $this->post(route('register'), [
            'name' => 'テストユーザー',
            'email' => 'test@example.com',
            'password' => 'password123',
            'password_confirmation' => 'different-password',
        ]);

        // Assert
        $response->assertSessionHasErrors('password');
    }
}
