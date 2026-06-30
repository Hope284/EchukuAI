<?php

declare(strict_types=1);

use App\Enums\Roles;
use App\Models\Blog;
use App\Models\Setting;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\File;

function tinyPngUpload(string $name): UploadedFile
{
    $png = base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mP8/x8AAusB9WlMdWQAAAAASUVORK5CYII=', true);

    return UploadedFile::fake()->createWithContent($name, $png);
}

beforeEach(function () {
    app()->usePublicPath(storage_path('framework/testing/public'));
    File::ensureDirectoryExists(public_path());
    Setting::factory()->create();
});

afterEach(function () {
    File::deleteDirectory(storage_path('framework/testing/public'));
    app()->usePublicPath(base_path('public'));
});

it('saves profile text fields without requiring an avatar', function () {
    $user = User::factory()->create(['name' => 'Old', 'surname' => 'Name']);

    $this->actingAs($user)
        ->post('/dashboard/user/settings/save', [
            'name'    => 'Chidi',
            'surname' => 'Eze',
            'phone'   => '+2348098765432',
            'address' => '8 Broad Street',
            'country' => 'NG',
            'state'   => 'Lagos',
            'city'    => 'Lagos',
        ])
        ->assertOk();

    expect($user->refresh()->name)->toBe('Chidi')
        ->and($user->phone)->toBe('+2348098765432')
        ->and($user->address)->toBe('8 Broad Street');
});

it('saves profile fields and a valid avatar under the authenticated user path', function () {
    $user = User::factory()->create(['name' => 'Old', 'surname' => 'Name']);

    $this->actingAs($user)
        ->post('/dashboard/user/settings/save', [
            'name'    => 'Ada',
            'surname' => 'Okafor',
            'phone'   => '+2348012345678',
            'address' => '12 Marina Road',
            'avatar'  => tinyPngUpload('avatar.png'),
        ])
        ->assertOk()
        ->assertJsonPath('message', 'User settings saved successfully');

    $user->refresh();
    expect($user->avatar)->toStartWith('upload/images/avatar/' . $user->id . '/')
        ->and(File::exists(public_path($user->avatar)))->toBeTrue()
        ->and($user->address)->toBe('12 Marina Road');
});

it('rejects executable profile uploads', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->postJson('/dashboard/user/settings/save', [
            'name'    => $user->name,
            'surname' => $user->surname,
            'avatar'  => UploadedFile::fake()->createWithContent('avatar.php', '<?php echo "unsafe";'),
        ])
        ->assertUnprocessable()
        ->assertJsonValidationErrors('avatar');
});

it('never deletes an avatar owned by another user when replacing a profile image', function () {
    $other = User::factory()->create();
    $user = User::factory()->create([
        'avatar' => 'upload/images/avatar/' . $other->id . '/existing.png',
    ]);
    $otherAvatar = public_path($user->avatar);
    File::ensureDirectoryExists(dirname($otherAvatar));
    File::put($otherAvatar, 'existing');

    $this->actingAs($user)
        ->post('/dashboard/user/settings/save', [
            'name'    => $user->name,
            'surname' => $user->surname,
            'avatar'  => tinyPngUpload('replacement.png'),
        ])
        ->assertOk();

    expect(File::exists($otherAvatar))->toBeTrue()
        ->and($user->refresh()->avatar)->toStartWith('upload/images/avatar/' . $user->id . '/');
});

it('creates a published blog post without a feature image', function () {
    $admin = User::factory()->create(['type' => Roles::SUPER_ADMIN->value]);

    $this->actingAs($admin)->post('/dashboard/blog/save', [
        'title'   => 'DZEVA Text Update',
        'content' => '<p>Text-only production update.</p>',
        'status'  => 1,
    ])->assertOk();

    expect(Blog::query()->where('title', 'DZEVA Text Update')->where('status', 1)->exists())->toBeTrue();
});

it('creates a published blog post with its feature image and preserves it on text-only edit', function () {
    $admin = User::factory()->create(['type' => Roles::SUPER_ADMIN->value]);

    $response = $this->actingAs($admin)->post('/dashboard/blog/save', [
        'title'         => 'DZEVA Launch Notes',
        'content'       => '<p>Production update.</p>',
        'status'        => 1,
        'feature_image' => tinyPngUpload('feature.png'),
    ]);

    $response->assertOk()->assertJsonPath('message', 'Post saved successfully.');
    $post = Blog::query()->where('title', 'DZEVA Launch Notes')->firstOrFail();
    $originalImage = $post->feature_image;

    expect(File::exists(public_path($originalImage)))->toBeTrue();

    $this->actingAs($admin)->post('/dashboard/blog/save', [
        'post_id' => $post->id,
        'title'   => 'DZEVA Launch Notes Updated',
        'content' => '<p>Updated production note.</p>',
        'status'  => 1,
    ])->assertOk();

    expect($post->refresh()->feature_image)->toBe($originalImage)
        ->and(File::exists(public_path($originalImage)))->toBeTrue();
});

it('replaces an owned blog image and removes the superseded file', function () {
    $admin = User::factory()->create(['type' => Roles::SUPER_ADMIN->value]);
    $post = new Blog;
    $post->title = 'Existing post';
    $post->slug = 'existing-post';
    $post->content = '<p>Old.</p>';
    $post->feature_image = 'upload/images/blog/old.png';
    $post->status = 1;
    $post->user_id = $admin->id;
    $post->save();
    File::ensureDirectoryExists(public_path('upload/images/blog'));
    File::put(public_path($post->feature_image), 'old');

    $this->actingAs($admin)->post('/dashboard/blog/save', [
        'post_id'       => $post->id,
        'title'         => 'Existing post',
        'content'       => '<p>New.</p>',
        'status'        => 1,
        'feature_image' => tinyPngUpload('replacement.png'),
    ])->assertOk();

    expect(File::exists(public_path('upload/images/blog/old.png')))->toBeFalse()
        ->and(File::exists(public_path($post->refresh()->feature_image)))->toBeTrue();
});

it('rejects executable blog feature images', function () {
    $admin = User::factory()->create(['type' => Roles::SUPER_ADMIN->value]);

    $this->actingAs($admin)->postJson('/dashboard/blog/save', [
        'title'         => 'Unsafe upload',
        'content'       => '<p>Unsafe.</p>',
        'status'        => 1,
        'feature_image' => UploadedFile::fake()->createWithContent('feature.php', '<?php echo "unsafe";'),
    ])->assertUnprocessable();

    expect(Blog::query()->where('title', 'Unsafe upload')->exists())->toBeFalse();
});
