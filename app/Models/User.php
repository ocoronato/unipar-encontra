<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use App\Enums\UserRole;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Str;

class User extends Authenticatable // implements MustVerifyEmail
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'registration_number',
        'course',
    ];

    /**
     * "role" fica fora do $fillable de propósito: nenhum formulário
     * pode transformar um usuário em administrador via mass assignment.
     *
     * @var array<string, mixed>
     */
    protected $attributes = [
        'role' => 'user',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'role' => UserRole::class,
        ];
    }

    public function isAdmin(): bool
    {
        return $this->role === UserRole::Admin;
    }

    /**
     * O usuário possui registros que fazem parte do histórico do sistema?
     * Nesse caso a conta não pode ser excluída.
     */
    public function hasHistory(): bool
    {
        return $this->lostFoundItems()->exists()
            || $this->returnRequests()->exists()
            || $this->processedReturns()->exists();
    }

    public function lostFoundItems(): HasMany
    {
        return $this->hasMany(LostFoundItem::class);
    }

    public function returnRequests(): HasMany
    {
        return $this->hasMany(ReturnRequest::class);
    }

    /**
     * Devoluções registradas por este usuário como administrador.
     */
    public function processedReturns(): HasMany
    {
        return $this->hasMany(ItemReturn::class, 'administrator_id');
    }

    /**
     * Get the user's initials
     */
    public function initials(): string
    {
        // Até duas iniciais, ignorando palavras que não começam com letra (ex.: "(dev)").
        return Str::of($this->name)
            ->explode(' ')
            ->filter(fn (string $word) => preg_match('/^\pL/u', $word))
            ->take(2)
            ->map(fn (string $word) => Str::upper(Str::substr($word, 0, 1)))
            ->implode('');
    }
}
