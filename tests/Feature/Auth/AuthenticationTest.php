<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuthenticationTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function 正しい認証情報でログインできる(): void
    {
        // Arrangre
        $user = User::factory()->create([
            'password' => bcrypt('password'),
        ]);

        // Act
        $response = $this->post('/login', [
            'email' => $user->email,
            'password' => 'password',
        ]);

        // Assert
        $this->assertAuthenticatedAs($user);
        $response->assertRedirect('/');
    }

    /** @test */
    public function ログイン済みユーザーがログアウトできる(): void
    {
        // Arrange
        $user = User::factory()->create();

        // Act
        $response = $this->actingAs($user)->post('/logout');

        // Assert
        $this->assertGuest();
        $response->assertRedirect('/');
    }

    /** @test */
    public function 認証済みユーザーはログインページにアクセスするとリダイレクトされる(): void
    {
        // Arrange
        $user = User::factory()->create();

        // Act
        $response = $this->actingAs($user)->get('/login');

        // Assert
        $response->assertRedirect('/');
    }

    /** @test */
    public function 誤った認証情報ではログインできない(): void
    {
        // Arrangre
        $user = User::factory()->create([
            'password' => bcrypt('password'),
        ]);

        // Act
        $response = $this->from(route('login'))->post(route('login'), [
            'email' => $user->email,
            'password' => 'wrong-password',
        ]);

        // Assert
        $this->assertGuest();

        $response->assertRedirect(route('login'));
        $response->assertSessionHasErrors('email');
    }
}
