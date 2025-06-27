<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class UploadControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_cannot_upload_more_than_five_files(): void
    {
        Storage::fake('public');

        $files = [];
        for ($i = 0; $i < 6; $i++) {
            $files[] = UploadedFile::fake()->image("photo{$i}.jpg");
        }

        $response = $this->postJson('/api/uploads', ['files' => $files]);
        $response->assertStatus(422);
    }

    public function test_successful_upload(): void
    {
        Storage::fake('public');

        $files = [
            UploadedFile::fake()->image('a.jpg'),
            UploadedFile::fake()->image('b.jpg'),
        ];

        $response = $this->postJson('/api/uploads', ['files' => $files]);
        $response->assertStatus(201);
    }
}
