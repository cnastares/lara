<?php

namespace Tests\Unit;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;
use App\Models\Post;
use App\Models\User;

class PostCreationTest extends TestCase
{
    use RefreshDatabase;

    public function test_post_creation_with_multiple_images()
    {
        // Crear usuario y autenticarse
        $user = User::factory()->create();
        $this->actingAs($user);

        // Simular imágenes
        Storage::fake('local');
        $images = [
            UploadedFile::fake()->image('img1.jpg'),
            UploadedFile::fake()->image('img2.jpg'),
            UploadedFile::fake()->image('img3.jpg'),
        ];

        // Simular datos mínimos del post
        $postData = [
            'category_id' => 1,
            'title' => 'Test Post',
            'description' => 'Test description',
            'price' => 100,
            'country_code' => 'US',
            'city_id' => 1,
            'contact_name' => 'Tester',
            'email' => 'test@example.com',
            'phone' => '1234567890',
            'pictures' => $images,
        ];

        // Enviar formulario (ajustar ruta según corresponda)
        $response = $this->post('/posts/create', $postData);

        $response->assertStatus(302); // Redirección tras éxito

        $post = Post::with('pictures')->first();
        $this->assertNotNull($post);
        $this->assertCount(3, $post->pictures);
    }
} 