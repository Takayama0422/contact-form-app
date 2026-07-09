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
        $request = new LoginRequest;
        $validator = Validator::make([
            'email' => 'invalid-email',
            'password' => '',
        ], $request->rules(), $request->messages());

        $this->assertFalse($validator->passes());
        $this->assertSame(
            ['email', 'password'],
            $validator->errors()->keys()
        );
        $this->assertSame('メールアドレスはメール形式で入力してください', $validator->errors()->first('email'));
        $this->assertSame('パスワードを入力してください', $validator->errors()->first('password'));
    }
}
