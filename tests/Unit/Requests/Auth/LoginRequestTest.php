<?php

namespace Tests\Unit\Requests\Auth;

use App\Http\Requests\Auth\LoginRequest;
use Illuminate\Support\Facades\Validator;
use Tests\TestCase;

class LoginRequestTest extends TestCase
{
    public function test_valid_login_values_are_accepted(): void
    {
        $validator = Validator::make([
            'email' => 'admin@example.com',
            'password' => 'password',
        ], (new LoginRequest)->rules());

        $this->assertTrue($validator->passes());
    }

    public function test_invalid_login_values_are_rejected(): void
    {
        $validator = Validator::make([
            'email' => 'invalid-email',
            'password' => '',
        ], (new LoginRequest)->rules());

        $this->assertFalse($validator->passes());
        $this->assertSame(
            ['email', 'password'],
            $validator->errors()->keys()
        );
    }
}
