<?php

namespace Tests\Unit;

use App\Models\User;
use PHPUnit\Framework\TestCase;

class UserInitialsTest extends TestCase
{
    public function test_initials_use_at_most_two_letters(): void
    {
        $this->assertSame('AD', (new User(['name' => 'Aluno Demonstração']))->initials());
        $this->assertSame('MD', (new User(['name' => 'Maria da Silva Souza']))->initials());
    }

    public function test_initials_ignore_words_that_do_not_start_with_a_letter(): void
    {
        $this->assertSame('A', (new User(['name' => 'Administrador (dev)']))->initials());
        $this->assertSame('ÉS', (new User(['name' => 'éder 2º Santos']))->initials());
    }
}
