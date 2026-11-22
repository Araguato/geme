<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
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
        'phone',
        'default_address',
        'default_location_hint',
        'terms_accepted_at',
        'privacy_accepted_at',
        'marketing_opt_in',
        'pin',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
        'api_token',
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
            'terms_accepted_at' => 'datetime',
            'privacy_accepted_at' => 'datetime',
            'marketing_opt_in' => 'boolean',
        ];
    }

    public function roles()
    {
        return $this->belongsToMany(Role::class);
    }

    public function orders()
    {
        return $this->hasMany(Order::class, 'customer_id');
    }

    /** Roles del personal interno (todo lo que no es cliente). */
    public const STAFF_ROLES = ['admin', 'supervisor', 'contable', 'cajero', 'vendedor', 'almacenista', 'despachador'];

    /** Nombres de rol cargados una sola vez por petición. */
    protected ?array $roleNamesCache = null;

    public function roleNames(): array
    {
        if ($this->roleNamesCache === null) {
            $this->roleNamesCache = $this->roles()->pluck('name')->all();
        }

        return $this->roleNamesCache;
    }

    public function flushRoleCache(): void
    {
        $this->roleNamesCache = null;
    }

    public function hasRole(string $role): bool
    {
        return in_array($role, $this->roleNames(), true);
    }

    public function hasAnyRole(array $roles): bool
    {
        return count(array_intersect($roles, $this->roleNames())) > 0;
    }

    public function isStaff(): bool
    {
        return $this->hasAnyRole(self::STAFF_ROLES);
    }

    /**
     * Pantalla de inicio según el rol. Con varios roles gana el de más alcance:
     * admin/supervisor → panel general, contable → panel financiero,
     * cajero/vendedor → TPV, almacenista → panel de almacén.
     */
    public function homeRouteName(): string
    {
        return match (true) {
            $this->hasAnyRole(['admin', 'supervisor']) => 'dashboard',
            $this->hasRole('contable') => 'accounting.dashboard',
            $this->hasAnyRole(['cajero', 'vendedor']) => 'pos.index',
            $this->hasRole('almacenista') => 'inventory.dashboard',
            $this->hasRole('despachador') => 'dashboard',
            default => 'public.order.index',
        };
    }
}
