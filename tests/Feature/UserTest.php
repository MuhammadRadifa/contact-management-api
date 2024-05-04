<?php

namespace Tests\Feature;

use App\Models\User;
use Database\Seeders\UserSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Support\Facades\Auth;
use Tests\TestCase;

class UserTest extends TestCase
{

    public function testRegisterSuccess(): void
    {
        $this->post('/api/users',[
            'username' => 'test',
            'password' => 'test',
            'name' => 'test@test.com',
        ])->assertStatus(201)->assertJson([
            'data'=>[
                'username'=>'test',
                'name'=>'test@test.com',
            ]
        ]);
    }

    public function testRegisterFail(): void
    {
        $this->post('/api/users',[
            'username' => '',
            'password' => '',
            'name' => '',
        ])->assertStatus(400)->assertJson([
            'error'=>[
                'username'=> ['The username field is required.'],
                'password'=> ['The password field is required.'],
                'name'=>['The name field is required.'],
            ]
        ]);
    }

    public function testRegisterUsernameAlreadyExists(): void
    {
        $this->testRegisterSuccess();
        $this->post('/api/users',[
            'username' => 'test',
            'password' => 'test',
            'name' => 'test@test.com',
        ])->assertStatus(400)->assertJson([
            'error'=>[
                'username'=>[
                    "username already registered"
                ],
            ]
        ]);
    }

    public function testLoginSuccess():void
    {
        $this->seed([UserSeeder::class]);
        $this->post('/api/users/login',[
            'username' => 'test',
            'password' => 'test',
        ])->assertStatus(200)->assertJson([
            'data'=>[
                'username'=>'test',
                'name'=>'test',
            ]
        ]);

        $user = User::where('username','test')->first();
        self::assertNotNull($user->token);
    }

    function testLoginUsernameNotFound():void
    {
        $this->post('/api/users/login',[
            'username' => 'testa',
            'password' => 'test',
        ])->assertStatus(401)->assertJson([
            "error" => [
                "message" => [
                    "username or password wrong"
                ]
            ]
        ]);
    }

    function testLoginPasswordWrong():void
    {
        $this->seed([UserSeeder::class]);
        $this->post('/api/users/login',[
            'username' => 'test',
            'password' => 'testa',
        ])->assertStatus(401)->assertJson([
            "error" => [
                "message" => [
                    "username or password wrong"
                ]
            ]
        ]);
    }


    function testGetSuccess():void
    {
        $this->seed([UserSeeder::class]);
        $this->get('/api/users/current',[
            'Authorization' => 'test'
        ])->assertStatus(200)->assertJson([
            'data'=>[
                'username'=>'test',
                'name'=>'test',
            ]
        ]);
    }

    function testGetUnauthorized():void
    {
        $this->seed([UserSeeder::class]);
        $this->get('/api/users/current')->assertStatus(401)->assertJson([
            'error'=>[
                'message'=>[
                    'Unauthorized'
                ]
            ]
        ]);
    }

    function testGetInvalidToken():void
    {
        $this->seed([UserSeeder::class]);
        $this->get('/api/users/current',
        ['Authorization' => 'testa']
        )->assertStatus(401)->assertJson([
            'error'=>[
                'message'=>[
                    'Unauthorized'
                ]
            ]
        ]);
    }

    public function testUpdatePasswordSuccess()
    {
        $this->seed([UserSeeder::class]);
        $oldUser = User::where('username', 'test')->first();

        $this->patch('/api/users/current',
            [
                'password' => 'baru'
            ],
            [
                'Authorization' => 'test'
            ]
        )->assertStatus(200)
            ->assertJson([
                'data' => [
                    'username' => 'test',
                    'name' => 'test'
                ]
            ]);

        $newUser = User::where('username', 'test')->first();
        self::assertNotEquals($oldUser->password, $newUser->password);
    }

    public function testUpdateNameSuccess()
    {
        $this->seed([UserSeeder::class]);
        $oldUser = User::where('username', 'test')->first();

        $this->patch('/api/users/current',
            [
                'name' => 'baru'
            ],
            [
                'Authorization' => 'test'
            ]
        )->assertStatus(200)
            ->assertJson([
                'data' => [
                    'username' => 'test',
                    'name' => 'baru'
                ]
            ]);

        $newUser = User::where('username', 'test')->first();
        self::assertNotEquals($oldUser->name, $newUser->name);
    }

    public function testUpdateFailed()
    {
        $this->seed([UserSeeder::class]);

        $this->patch('/api/users/current',
            [
                'name' => 'testtesttestetstestetstetstetstetstetstetstetstetstetstetstetststetsttetsttetstetststetstetstettstetstettstetsttes'
            ],
            [
                'Authorization' => 'test'
            ]
        )->assertStatus(400)
            ->assertJson([
                'error' => [
                    'name' => [
                        "The name field must not be greater than 100 characters."
                    ]
                ]
            ]);
    }

    public function testLogoutSuccess()
    {
        $this->seed([UserSeeder::class]);

        $this->delete(uri: '/api/users/logout', headers: [
            'Authorization' => 'test'
        ])->assertStatus(200)
            ->assertJson([
                "data" => true
            ]);

        $user = User::where('username', 'test')->first();
        self::assertNull($user->token);

    }

    public function testLogoutFailed()
    {
        $this->seed([UserSeeder::class]);

        $this->delete(uri: '/api/users/logout', headers: [
            'Authorization' => 'salah'
        ])->assertStatus(401)
            ->assertJson([
                "error" => [
                    "message" => [
                        "Unauthorized"
                    ]
                ]
            ]);
    }


}
