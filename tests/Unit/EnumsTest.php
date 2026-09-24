<?php

namespace Tests\Unit;

use App\Enums\ApprovalStatus;
use App\Enums\ItemStatus;
use App\Enums\ItemType;
use App\Enums\ReturnRequestStatus;
use App\Enums\UserRole;
use PHPUnit\Framework\TestCase;

class EnumsTest extends TestCase
{
    /**
     * Todo status exibido em badge precisa de rótulo e cor.
     */
    public function test_every_status_has_label_and_color(): void
    {
        foreach ([ItemType::class, ItemStatus::class, ApprovalStatus::class, ReturnRequestStatus::class] as $enum) {
            foreach ($enum::cases() as $case) {
                $this->assertNotEmpty($case->label(), "{$enum}::{$case->name} sem rótulo");
                $this->assertNotEmpty($case->color(), "{$enum}::{$case->name} sem cor");
            }
        }
    }

    public function test_labels_are_in_portuguese(): void
    {
        $this->assertSame('Perdido', ItemType::Lost->label());
        $this->assertSame('Em processo de devolução', ItemStatus::InReturnProcess->label());
        $this->assertSame('Aguardando aprovação', ApprovalStatus::Pending->label());
        $this->assertSame(['Pendente', 'Aprovada', 'Rejeitada', 'Cancelada'], array_map(fn ($case) => $case->label(), ReturnRequestStatus::cases()));
        $this->assertSame('Administrador', UserRole::Admin->label());
    }
}
