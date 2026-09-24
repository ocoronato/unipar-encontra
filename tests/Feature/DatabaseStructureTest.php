<?php

namespace Tests\Feature;

use App\Enums\ApprovalStatus;
use App\Enums\ItemStatus;
use App\Enums\ItemType;
use App\Enums\UserRole;
use App\Models\Category;
use App\Models\ItemPhoto;
use App\Models\ItemReturn;
use App\Models\Location;
use App\Models\LostFoundItem;
use App\Models\ReturnRequest;
use App\Models\User;
use Database\Seeders\AdminUserSeeder;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DatabaseStructureTest extends TestCase
{
    use RefreshDatabase;

    public function test_seeders_create_initial_data(): void
    {
        $this->seed(DatabaseSeeder::class);

        $this->assertSame(9, Category::count());
        $this->assertSame(9, Location::count());
        $this->assertDatabaseHas('categories', ['name' => 'Eletrônicos']);
        $this->assertDatabaseHas('locations', ['name' => 'Biblioteca']);

        $admin = User::where('email', AdminUserSeeder::DEV_EMAIL)->first();
        $this->assertTrue($admin->isAdmin());
    }

    public function test_seeders_can_run_twice_without_duplicating(): void
    {
        $this->seed(DatabaseSeeder::class);
        $this->seed(DatabaseSeeder::class);

        $this->assertSame(9, Category::count());
        $this->assertSame(9, Location::count());
        $this->assertSame(1, User::where('role', UserRole::Admin)->count());
    }

    public function test_new_users_are_regular_users_and_role_is_not_mass_assignable(): void
    {
        $user = User::create([
            'name' => 'Fulano',
            'email' => 'fulano@example.com',
            'password' => 'password',
            'role' => 'admin', // deve ser ignorado
        ]);

        $this->assertSame(UserRole::User, $user->fresh()->role);
        $this->assertFalse($user->isAdmin());
    }

    public function test_new_item_starts_active_and_pending(): void
    {
        $user = User::factory()->create();

        $item = $user->lostFoundItems()->create([
            'category_id' => Category::factory()->create()->id,
            'location_id' => Location::factory()->create()->id,
            'type' => ItemType::Found,
            'title' => 'Celular',
            'description' => 'Celular encontrado na cantina.',
            'occurred_at' => now()->toDateString(),
        ])->fresh();

        $this->assertSame(ItemStatus::Active, $item->status);
        $this->assertSame(ApprovalStatus::Pending, $item->approval_status);
        $this->assertTrue($item->user->is($user));
    }

    public function test_relationships_are_configured(): void
    {
        $itemReturn = ItemReturn::factory()->create();
        $item = $itemReturn->lostFoundItem;
        ItemPhoto::factory(2)->for($item)->create();

        $this->assertCount(2, $item->photos);
        $this->assertInstanceOf(Category::class, $item->category);
        $this->assertInstanceOf(Location::class, $item->location);
        $this->assertTrue($item->itemReturn->is($itemReturn));
        $this->assertTrue($item->returnRequests->first()->is($itemReturn->returnRequest));
        $this->assertTrue($itemReturn->returnRequest->itemReturn->is($itemReturn));
        $this->assertTrue($itemReturn->administrator->isAdmin());
        $this->assertCount(1, $itemReturn->administrator->processedReturns);
        $this->assertCount(1, $itemReturn->returnRequest->user->returnRequests);
        $this->assertCount(1, $item->category->lostFoundItems);
        $this->assertCount(1, $item->location->lostFoundItems);
    }

    public function test_approved_scope_filters_public_items(): void
    {
        LostFoundItem::factory()->approved()->create();
        LostFoundItem::factory()->create();
        LostFoundItem::factory()->rejected()->create();

        $this->assertSame(1, LostFoundItem::approved()->count());
    }

    public function test_an_item_can_only_be_returned_once(): void
    {
        $itemReturn = ItemReturn::factory()->create();
        $otherRequest = ReturnRequest::factory()->approved()->create([
            'lost_found_item_id' => $itemReturn->lost_found_item_id,
        ]);

        $this->expectException(QueryException::class);

        ItemReturn::factory()->create([
            'lost_found_item_id' => $itemReturn->lost_found_item_id,
            'return_request_id' => $otherRequest->id,
        ]);
    }

    public function test_items_with_history_block_category_deletion(): void
    {
        $item = LostFoundItem::factory()->create();

        $this->expectException(QueryException::class);

        $item->category->delete();
    }
}
