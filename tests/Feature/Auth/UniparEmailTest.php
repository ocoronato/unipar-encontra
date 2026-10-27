<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Volt\Volt;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

/**
 * Somente e-mails da UNIPAR ("unipar" depois do @) no cadastro e na troca de e-mail.
 */
class UniparEmailTest extends TestCase
{
    use RefreshDatabase;

    public static function emailsForaDaUnipar(): array
    {
        return [
            'gmail' => ['pessoa@gmail.com'],
            'unipar só no começo do nome' => ['pessoa@naounipar.com'],
            'unipar colado a outra palavra' => ['pessoa@unipar-falso.com'],
            'unipar antes do @' => ['unipar@hotmail.com'],
        ];
    }

    public static function emailsDaUnipar(): array
    {
        return [
            'domínio principal' => ['aluno@unipar.br'],
            'subdomínio' => ['aluno@edu.unipar.br'],
            'letras maiúsculas' => ['Aluno@UNIPAR.BR'],
        ];
    }

    #[DataProvider('emailsForaDaUnipar')]
    public function test_registration_rejects_emails_outside_unipar(string $email): void
    {
        Volt::test('auth.register')
            ->set('name', 'Pessoa de Fora')
            ->set('email', $email)
            ->set('password', 'password')
            ->set('password_confirmation', 'password')
            ->call('register')
            ->assertHasErrors(['email']);

        $this->assertDatabaseCount('users', 0);
        $this->assertGuest();
    }

    #[DataProvider('emailsDaUnipar')]
    public function test_registration_accepts_unipar_emails(string $email): void
    {
        // A regra "lowercase" do cadastro exige minúsculas; o formulário recebe o que o usuário digitou.
        Volt::test('auth.register')
            ->set('name', 'Aluno')
            ->set('email', strtolower($email))
            ->set('password', 'password')
            ->set('password_confirmation', 'password')
            ->call('register')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('users', ['email' => strtolower($email)]);
    }

    public function test_registration_form_explains_the_rule(): void
    {
        $this->get('/register')->assertOk()->assertSee('Use o seu e-mail da UNIPAR.');
    }

    public function test_profile_cannot_change_email_to_outside_unipar(): void
    {
        $user = User::factory()->create(['email' => 'aluno@unipar.br']);

        $this->actingAs($user);

        Volt::test('settings.profile')
            ->set('name', $user->name)
            ->set('email', 'aluno@gmail.com')
            ->call('updateProfileInformation')
            ->assertHasErrors(['email']);

        $this->assertSame('aluno@unipar.br', $user->fresh()->email);
    }

    public function test_existing_account_with_other_domain_can_still_update_profile(): void
    {
        // Contas criadas antes da regra (ou pela administração) continuam funcionando.
        $user = User::factory()->create(['email' => 'antigo@example.com']);

        $this->actingAs($user);

        Volt::test('settings.profile')
            ->set('name', 'Nome Atualizado')
            ->set('email', 'antigo@example.com')
            ->call('updateProfileInformation')
            ->assertHasNoErrors();

        $this->assertSame('Nome Atualizado', $user->fresh()->name);
    }
}
