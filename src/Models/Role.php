<?php namespace Arcanedev\LaravelAuth\Models;

use Arcanedev\LaravelAuth\Bases\Model;
use Arcanedev\LaravelAuth\Contracts\Role as RoleContract;
use Arcanedev\LaravelAuth\Traits\AuthRoleRelationships;

/**
 * Class     Role
 *
 * @package  Arcanedev\LaravelAuth\Models
 * @author   ARCANEDEV <arcanedev.maroc@gmail.com>
 *
 * @property  int                                       id
 * @property  string                                    name
 * @property  string                                    slug
 * @property  string                                    description
 * @property  bool                                      is_active
 * @property  bool                                      is_locked
 * @property  \Carbon\Carbon                            created_at
 * @property  \Carbon\Carbon                            updated_at
 * @property  \Illuminate\Database\Eloquent\Collection  users
 * @property  \Illuminate\Database\Eloquent\Collection  permissions
 */
class Role extends Model implements RoleContract
{
    /* ------------------------------------------------------------------------------------------------
     |  Traits
     | ------------------------------------------------------------------------------------------------
     */
    use AuthRoleRelationships;

    /* ------------------------------------------------------------------------------------------------
     |  Properties
     | ------------------------------------------------------------------------------------------------
     */
    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = ['name', 'slug', 'description'];

    /**
     * The attributes that should be casted to native types.
     *
     * @var array
     */
    protected $casts = [
        'is_active' => 'boolean',
        'is_locked' => 'boolean',
    ];

    /* ------------------------------------------------------------------------------------------------
     |  Constructor
     | ------------------------------------------------------------------------------------------------
     */
    /**
     * Create a new Eloquent model instance.
     *
     * @param  array  $attributes
     */
    public function __construct(array $attributes = [])
    {
        $this->setTable(config('laravel-auth.roles.table', 'roles'));

        parent::__construct($attributes);
    }

    /* ------------------------------------------------------------------------------------------------
     |  Getters & Setters
     | ------------------------------------------------------------------------------------------------
     */
    /**
     * Set the name attribute.
     *
     * @param  string  $name
     */
    public function setNameAttribute($name)
    {
        $this->attributes['name'] = $name;
        $this->setSlugAttribute($name);
    }

    /**
     * Set the slug attribute.
     *
     * @param  string  $slug
     */
    public function setSlugAttribute($slug)
    {
        $this->attributes['slug'] = str_slug($slug, config('laravel-auth.slug-separator', '.'));
    }

    /* ------------------------------------------------------------------------------------------------
     |  CRUD Functions
     | ------------------------------------------------------------------------------------------------
     */
    /**
     * Attach a permission to a role.
     *
     * @param  \Arcanedev\LaravelAuth\Models\User|int  $user
     * @param  bool                                    $reload
     */
    public function attachUser($user, $reload = true)
    {
        if ($this->hasUser($user)) {
            return;
        }

        $this->users()->attach($user);

        if ($reload) {
            $this->load('users');
        }
    }

    /**
     * Check if role has the given user (User Model or Id).
     *
     * @param  mixed  $id
     *
     * @return bool
     */
    public function hasUser($id)
    {
        if ($id instanceof User) {
            $id = $id->getKey();
        }

        return $this->users->contains($id);
    }

    /**
     * Detach a user from a role.
     *
     * @param  \Arcanedev\LaravelAuth\Models\User|int  $user
     * @param  bool                                    $reload
     *
     * @return int
     */
    public function detachUser($user, $reload = true)
    {
        if ($user instanceof User) {
            $user = (array) $user->getKey();
        }

        $result = $this->users()->detach($user);

        if ($reload) {
            $this->load('users');
        }

        return $result;
    }

    /**
     * Detach all users from a role.
     *
     * @param  bool  $reload
     *
     * @return int
     */
    public function detachAllUsers($reload = true)
    {
        $result = $this->users()->detach();

        if ($reload) {
            $this->load('users');
        }

        return $result;
    }

    /**
     * Attach a permission to a role.
     *
     * @param  \Arcanedev\LaravelAuth\Models\Permission|int  $permission
     * @param  bool                                          $reload
     */
    public function attachPermission($permission, $reload = true)
    {
        if ($this->hasPermission($permission)) {
            return;
        }

        $this->permissions()->attach($permission);

        if ($reload) {
            $this->load('permissions');
        }
    }

    /**
     * Detach a permission from a role.
     *
     * @param  \Arcanedev\LaravelAuth\Models\Permission|int  $permission
     * @param  bool                                          $reload
     *
     * @return int
     */
    public function detachPermission($permission, $reload = true)
    {
        if ($permission instanceof Permission) {
            $permission = (array) $permission->getKey();
        }

        $result = $this->permissions()->detach($permission);

        if ($reload) {
            $this->load('permissions');
        }

        return $result;
    }

    /**
     * Detach all permissions from a role.
     *
     * @param  bool  $reload
     *
     * @return int
     */
    public function detachAllPermissions($reload = true)
    {
        $result = $this->permissions()->detach();

        if ($reload) {
            $this->load('permissions');
        }

        return $result;
    }

    /**
     * Check if role has the given permission (Permission Model or Id).
     *
     * @param  mixed  $id
     *
     * @return bool
     */
    public function hasPermission($id)
    {
        if ($id instanceof Permission) {
            $id = $id->getKey();
        }

        return $this->permissions->contains($id);
    }

    /* ------------------------------------------------------------------------------------------------
     |  Check Functions
     | ------------------------------------------------------------------------------------------------
     */
    /**
     * Check if role is associated with a permission by slug.
     *
     * @param  string  $slug
     *
     * @return bool
     */
    public function can($slug)
    {
        $permissions = $this->permissions->filter(function(Permission $permission) use ($slug) {
            return $permission->slug === str_slug($slug, config('laravel-auth.slug-separator', '.'));
        });

        return $permissions->count() === 1;
    }

    /**
     * Check if a role is associated with any of given permissions.
     *
     * @param  array  $permissions
     * @param  array  &$failedPermissions
     *
     * @return bool
     */
    public function canAny(array $permissions, array &$failedPermissions = [])
    {
        foreach ($permissions as $permission) {
            if ( ! $this->can($permission)) {
                $failedPermissions[] = $permission;
            }
        }

        return count($permissions) !== count($failedPermissions);
    }

    /**
     * Check if role is associated with all given permissions.
     *
     * @param  array  $permissions
     * @param  array  &$failedPermissions
     *
     * @return bool
     */
    public function canAll(array $permissions, array &$failedPermissions = [])
    {
        $this->canAny($permissions, $failedPermissions);

        return count($failedPermissions) === 0;
    }

    /**
     * Check if the role is active.
     *
     * @return bool
     */
    public function isActive()
    {
        return $this->is_active;
    }

    /**
     * Check if the role is locked.
     *
     * @return bool
     */
    public function isLocked()
    {
        return $this->is_locked;
    }
}
