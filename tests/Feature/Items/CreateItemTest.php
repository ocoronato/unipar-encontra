<?php

namespace Tests\Feature\Items;

use App\Enums\ApprovalStatus;
use App\Enums\ItemStatus;
use App\Enums\ItemType;
use App\Models\Category;
use App\Models\Location;
use App\Models\LostFoundItem;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Features\SupportLockedProperties\CannotUpdateLockedPropertyException;
use Livewire\Volt\Volt;
use Tests\TestCase;

class CreateItemTest extends TestCase
{
    use RefreshDatabase;

    private Category $category;

    private Location $location;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('public');

        $this->category = Category::factory()->create();
        $this->location = Location::factory()->create();
    }

    /**
     * Formulário preenchido com dados válidos.
     */
    private function filledForm(ItemType $type = ItemType::Found)
    {
        return Volt::test('items.form', ['type' => $type])
            ->set('title', 'Garrafa térmica azul')
            ->set('category_id', (string) $this->category->id)
            ->set('location_id', (string) $this->location->id)
            ->set('occurred_at', today()->toDateString())
            ->set('description', 'Garrafa térmica azul com adesivo da UNIPAR.');
    }

    public function test_create_pages_require_login(): void
    {
        $this->get(route('items.create.lost'))->assertRedirect(route('login'));
        $this->get(route('items.create.found'))->assertRedirect(route('login'));
    }

    public function test_each_route_opens_the_form_with_its_type(): void
    {
        $this->actingAs(User::factory()->create());

        $this->get(route('items.create.lost'))->assertOk()->assertSee('Cadastrar objeto perdido');
        $this->get(route('items.create.found'))->assertOk()->assertSee('Cadastrar objeto encontrado');
    }

    public function test_user_can_register_a_lost_item_without_photos(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $this->filledForm(ItemType::Lost)
            ->call('save')
            ->assertHasNoErrors()
            ->assertRedirect(route('items.mine'));

        $item = LostFoundItem::first();
        $this->assertTrue($item->user->is($user));
        $this->assertSame(ItemType::Lost, $item->type);
        $this->assertSame(ItemStatus::Active, $item->status);
        $this->assertSame(ApprovalStatus::Pending, $item->approval_status);
        $this->assertSame('Objeto cadastrado com sucesso e enviado para análise.', session('status'));
    }

    public function test_user_can_register_a_found_item_with_photos(): void
    {
        $this->actingAs(User::factory()->create());

        $this->filledForm(ItemType::Found)
            ->set('newPhotos', [
                UploadedFile::fake()->image('frente.jpg', 800, 600),
                UploadedFile::fake()->image('verso.png', 400, 400),
            ])
            ->assertCount('photos', 2)
            ->call('save')
            ->assertHasNoErrors();

        $item = LostFoundItem::first();
        $this->assertSame(ItemType::Found, $item->type);
        $this->assertCount(2, $item->photos);
        $this->assertSame('frente.jpg', $item->photos[0]->original_name);

        foreach ($item->photos as $photo) {
            Storage::disk('public')->assertExists($photo->path);
            $this->assertStringStartsWith("items/{$item->id}/", $photo->path);
            $this->assertStringEndsWith('.jpg', $photo->path);
        }
    }

    public function test_large_photos_are_resized(): void
    {
        $this->actingAs(User::factory()->create());

        $this->filledForm()
            ->set('newPhotos', [UploadedFile::fake()->image('grande.jpg', 3200, 2400)])
            ->call('save')
            ->assertHasNoErrors();

        $path = LostFoundItem::first()->photos->first()->path;
        [$width, $height] = getimagesize(Storage::disk('public')->path($path));

        $this->assertSame([1600, 1200], [$width, $height]);
    }

    public function test_photo_metadata_is_removed(): void
    {
        $this->actingAs(User::factory()->create());

        // JPEG com um bloco EXIF (APP1) contendo um texto que simula a localização GPS.
        $fake = UploadedFile::fake()->image('gps.jpg', 50, 50);
        $jpeg = file_get_contents($fake->getRealPath());
        $exif = "Exif\0\0MM\0\x2A\0\0\0\x08\0\0\0\0\0\0LOCALIZACAO-SECRETA";
        $withExif = substr($jpeg, 0, 2)."\xFF\xE1".pack('n', strlen($exif) + 2).$exif.substr($jpeg, 2);

        $photo = UploadedFile::fake()->createWithContent('gps.jpg', $withExif);
        $this->assertStringContainsString('LOCALIZACAO-SECRETA', file_get_contents($photo->getRealPath()));

        $this->filledForm()
            ->set('newPhotos', [$photo])
            ->call('save')
            ->assertHasNoErrors();

        $stored = Storage::disk('public')->get(LostFoundItem::first()->photos->first()->path);
        $this->assertStringNotContainsString('LOCALIZACAO-SECRETA', $stored);
        $this->assertStringNotContainsString('Exif', $stored);
    }

    public function test_photos_can_be_added_in_batches_and_removed(): void
    {
        $this->actingAs(User::factory()->create());

        $this->filledForm()
            ->set('newPhotos', [UploadedFile::fake()->image('a.jpg')])
            ->set('newPhotos', [UploadedFile::fake()->image('b.jpg')])
            ->assertCount('photos', 2)
            ->call('removePhoto', 0)
            ->assertCount('photos', 1)
            ->call('save')
            ->assertHasNoErrors();

        $this->assertSame('b.jpg', LostFoundItem::first()->photos->first()->original_name);
    }

    public function test_required_fields_are_validated(): void
    {
        $this->actingAs(User::factory()->create());

        Volt::test('items.form', ['type' => ItemType::Lost])
            ->set('occurred_at', '')
            ->call('save')
            ->assertHasErrors(['title', 'category_id', 'location_id', 'occurred_at', 'description']);

        $this->assertSame(0, LostFoundItem::count());
    }

    public function test_inactive_category_and_future_date_are_rejected(): void
    {
        $this->actingAs(User::factory()->create());
        $inactive = Category::factory()->create(['active' => false]);

        $this->filledForm()
            ->set('category_id', (string) $inactive->id)
            ->set('occurred_at', today()->addDay()->toDateString())
            ->call('save')
            ->assertHasErrors(['category_id', 'occurred_at']);
    }

    public function test_non_image_files_are_rejected(): void
    {
        $this->actingAs(User::factory()->create());

        $this->filledForm()
            ->set('newPhotos', [UploadedFile::fake()->create('documento.pdf', 100, 'application/pdf')])
            ->assertHasErrors(['newPhotos.0'])
            ->assertCount('photos', 0);
    }

    public function test_photos_larger_than_5mb_are_rejected(): void
    {
        $this->actingAs(User::factory()->create());

        $this->filledForm()
            ->set('newPhotos', [UploadedFile::fake()->image('enorme.jpg')->size(6000)])
            ->assertHasErrors(['newPhotos.0' => 'max'])
            ->assertCount('photos', 0);
    }

    public function test_at_most_five_photos_are_accepted(): void
    {
        $this->actingAs(User::factory()->create());

        $photos = fn (int $count) => array_map(fn ($i) => UploadedFile::fake()->image("foto{$i}.jpg"), range(1, $count));

        $this->filledForm()
            ->set('newPhotos', $photos(4))
            ->assertCount('photos', 4)
            ->set('newPhotos', $photos(2))
            ->assertHasErrors(['newPhotos' => 'max'])
            ->assertCount('photos', 4);
    }

    public function test_type_cannot_be_changed_by_the_browser(): void
    {
        $this->actingAs(User::factory()->create());

        $this->expectException(CannotUpdateLockedPropertyException::class);

        Volt::test('items.form', ['type' => ItemType::Lost])->set('type', ItemType::Found);
    }
}
