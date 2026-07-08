<?php

namespace Tests\Unit\Actions\Fortify;

use App\Actions\Fortify\CreateNewUser;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class CreateNewUserTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_is_created_with_valid_input(): void
    {
        $user = (new CreateNewUser)->create([
            'name' => '管理者',
            'email' => 'admin@example.com',
            'password' => 'password',
            'password_confirmation' => 'password',
        ]);

        $this->assertInstanceOf(User::class, $user);
        $this->assertSame('管理者', $user->name);
        $this->assertSame('admin@example.com', $user->email);
        $this->assertTrue(Hash::check('password', $user->password));
        $this->assertDatabaseHas('users', [
            'name' => '管理者',
            'email' => 'admin@example.com',
        ]);
    }

    public function test_invalid_input_is_rejected(): void
    {
        User::factory()->create([
            'email' => 'duplicate@example.com',
        ]);

        try {
            (new CreateNewUser)->create([
                'name' => '',
                'email' => 'duplicate@example.com',
                'password' => 'short',
                'password_confirmation' => 'different',
            ]);

            $this->fail('ValidationException was not thrown.');
        } catch (ValidationException $exception) {
            $this->assertSame(
                ['name', 'email', 'password'],
                array_keys($exception->errors())
            );
        }
    }
}
