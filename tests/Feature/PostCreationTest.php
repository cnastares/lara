<?php

namespace Tests\Feature;

use App\Models\Post;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class PostCreationTest extends TestCase
{
    use RefreshDatabase;

    public function test_multiple_images_are_stored(): void
    {
        Storage::fake('public');
        Storage::fake('local');

        $user = User::create([
            'name'         => 'Test User',
            'email'        => 'test@example.com',
            'password'     => bcrypt('password'),
            'country_code' => 'US',
        ]);

        $this->actingAs($user);

        $files = [
            UploadedFile::fake()->image('photo1.jpg'),
            UploadedFile::fake()->image('photo2.jpg'),
            UploadedFile::fake()->image('photo3.jpg'),
        ];

        $response = $this->post('/api/posts', [
            'country_code'       => 'US',
            'category_id'        => 1,
            'post_type_id'       => 1,
            'title'              => 'Test post',
            'description'        => 'Test description',
            'contact_name'       => 'Tester',
            'auth_field'         => 'email',
            'email'             => 'test@example.com',
            'city_id'           => 1,
            'price'             => 100,
            'package_id'        => 1,
            'payment_method_id' => 1,
            'accept_terms'      => 1,
            'pictures'          => $files,
        ]);

        $response->assertStatus(201);

        $post = Post::first();
        $this->assertNotNull($post);
        $this->assertEquals(3, $post->pictures()->count());
    }
}
