<?php

namespace Tests\Feature;

use App\Enums\ItemStatus;
use App\Enums\ReturnRequestStatus;
use App\Models\LostFoundItem;
use App\Models\ReturnRequest;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Gate;
use Tests\TestCase;

class PolicyTest extends TestCase
{
    use RefreshDatabase;

    public function test_only_approved_items_are_public(): void
    {
        $approved = LostFoundItem::factory()->approved()->create();
        $pending = LostFoundItem::factory()->create();
        $stranger = User::factory()->create();

        $this->assertTrue(Gate::forUser(null)->allows('view', $approved));
        $this->assertFalse(Gate::forUser(null)->allows('view', $pending));
        $this->assertFalse($stranger->can('view', $pending));
        $this->assertTrue($pending->user->can('view', $pending));
        $this->assertTrue(User::factory()->admin()->create()->can('view', $pending));
    }

    public function test_only_owner_can_edit_active_items(): void
    {
        $item = LostFoundItem::factory()->approved()->create();

        $this->assertTrue($item->user->can('update', $item));
        $this->assertFalse(User::factory()->create()->can('update', $item));
        $this->assertFalse(User::factory()->admin()->create()->can('update', $item));

        $item->status = ItemStatus::Returned;
        $this->assertFalse($item->user->can('update', $item));
        $this->assertFalse($item->user->can('cancel', $item));
    }

    public function test_items_with_open_return_request_cannot_be_edited(): void
    {
        $request = ReturnRequest::factory()->create();
        $item = $request->lostFoundItem;

        $this->assertFalse($item->user->can('update', $item));
    }

    public function test_only_admins_moderate(): void
    {
        $item = LostFoundItem::factory()->create();

        $this->assertFalse($item->user->can('moderate', $item));
        $this->assertTrue(User::factory()->admin()->create()->can('moderate', $item));
    }

    public function test_return_request_rules(): void
    {
        $found = LostFoundItem::factory()->found()->approved()->create();
        $user = User::factory()->create();

        // Pode solicitar um objeto encontrado aprovado.
        $this->assertTrue($user->can('create', [ReturnRequest::class, $found]));

        // O autor da publicação não pode pedir o próprio objeto.
        $this->assertFalse($found->user->can('create', [ReturnRequest::class, $found]));

        // Objeto perdido ou ainda não aprovado não recebe solicitações.
        $this->assertFalse($user->can('create', [ReturnRequest::class, LostFoundItem::factory()->lost()->approved()->create()]));
        $this->assertFalse($user->can('create', [ReturnRequest::class, LostFoundItem::factory()->found()->create()]));

        // Objeto devolvido não recebe solicitações.
        $this->assertFalse($user->can('create', [ReturnRequest::class, LostFoundItem::factory()->found()->returned()->create()]));

        // Solicitação duplicada.
        ReturnRequest::factory()->for($user)->create(['lost_found_item_id' => $found->id]);
        $this->assertFalse($user->can('create', [ReturnRequest::class, $found]));
    }

    public function test_user_can_request_again_after_cancelling(): void
    {
        $request = ReturnRequest::factory()->create(['status' => ReturnRequestStatus::Cancelled]);

        $this->assertTrue($request->user->can('create', [ReturnRequest::class, $request->lostFoundItem]));
    }

    public function test_user_cannot_request_again_after_rejection(): void
    {
        $request = ReturnRequest::factory()->create(['status' => ReturnRequestStatus::Rejected]);

        $this->assertFalse($request->user->can('create', [ReturnRequest::class, $request->lostFoundItem]));
    }

    public function test_return_request_view_cancel_and_review(): void
    {
        $request = ReturnRequest::factory()->create();
        $admin = User::factory()->admin()->create();
        $stranger = User::factory()->create();

        $this->assertTrue($request->user->can('view', $request));
        $this->assertTrue($admin->can('view', $request));
        $this->assertFalse($stranger->can('view', $request));

        $this->assertTrue($request->user->can('cancel', $request));
        $this->assertFalse($stranger->can('cancel', $request));

        $this->assertTrue($admin->can('approve', $request));
        $this->assertTrue($admin->can('reject', $request));
        $this->assertFalse($admin->can('confirmReturn', $request));
        $this->assertFalse($request->user->can('approve', $request));
    }

    public function test_admin_cannot_review_own_request(): void
    {
        $admin = User::factory()->admin()->create();
        $request = ReturnRequest::factory()->for($admin)->create();

        $this->assertFalse($admin->can('approve', $request));
        $this->assertFalse($admin->can('reject', $request));
    }
}
