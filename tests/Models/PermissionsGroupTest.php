<?php namespace Arcanedev\LaravelAuth\Tests\Models;

use Arcanedev\LaravelAuth\Models\Permission;
use Arcanedev\LaravelAuth\Models\PermissionsGroup;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Class     PermissionsGroupTest
 *
 * @package  Arcanedev\LaravelAuth\Tests\Models
 * @author   ARCANEDEV <arcanedev.maroc@gmail.com>
 */
class PermissionsGroupTest extends ModelsTest
{
    /* ------------------------------------------------------------------------------------------------
     |  Properties
     | ------------------------------------------------------------------------------------------------
     */
    /**
     * @var \Arcanedev\LaravelAuth\Models\PermissionsGroup
     */
    protected $groupModel;

    /* ------------------------------------------------------------------------------------------------
     |  Main Functions
     | ------------------------------------------------------------------------------------------------
     */
    public function setUp()
    {
        parent::setUp();

        $this->groupModel = new PermissionsGroup;
    }

    public function tearDown()
    {
        unset($this->groupModel);

        parent::tearDown();
    }

    /* ------------------------------------------------------------------------------------------------
     |  Test Functions
     | ------------------------------------------------------------------------------------------------
     */
    /** @test */
    public function it_can_be_instantiated()
    {
        $expectations = [
            \Illuminate\Database\Eloquent\Model::class,
            // \Arcanesoft\Contracts\Auth\Models\PermissionsGroup::class,
            \Arcanedev\LaravelAuth\Models\PermissionsGroup::class,
        ];

        foreach ($expectations as $expected) {
            $this->assertInstanceOf($expected, $this->groupModel);
        }
    }

    /** @test */
    public function it_has_relationships()
    {
        $permissionsRelationship = $this->groupModel->permissions();

        $this->assertInstanceOf(HasMany::class, $permissionsRelationship);

        /** @var  Permission  $permission */
        $permission = $permissionsRelationship->getRelated();

        $this->assertInstanceOf(Permission::class, $permission);
    }

    /** @test */
    public function it_can_create()
    {
        $attributes = $this->getAuthGroupAttributes();
        $group      = $this->createGroup($attributes);

        $this->seeInDatabase('permissions_group', $attributes);
        $this->assertCount(0, $group->permissions);
    }

    /** @test */
    public function it_can_create_permission()
    {
        $group      = $this->createGroup(
            $this->getAuthGroupAttributes()
        );

        $this->assertCount(0, $group->permissions);

        $group->createPermission([
            'name'        => 'Create users',
            'slug'        => 'auth.users.create',
            'description' => 'Allow to create users',
        ]);

        $this->assertCount(1, $group->permissions);

        $group->createPermission([
            'name'        => 'Update users',
            'slug'        => 'auth.users.update',
            'description' => 'Allow to update users',
        ]);

        $this->assertCount(2, $group->permissions);
    }

    /**
     * @test
     *
     * @expectedException \Illuminate\Database\QueryException
     */
    public function it_must_throw_an_exception_on_duplicated_permissions()
    {
        $group      = $this->createGroup(
            $this->getAuthGroupAttributes()
        );

        for ($i = 0; $i < 2; $i ++) {
            $group->createPermission([
                'name'        => 'Create users',
                'slug'        => 'auth.users.create',
                'description' => 'Allow to create users',
            ]);
        }
    }

    /** @test */
    public function it_can_attach_permission()
    {
        $permission = Permission::create([
            'name'        => 'Create users',
            'slug'        => 'auth.users.create',
            'description' => 'Allow to create users',
        ]);

        $group      = $this->createGroup(
            $this->getAuthGroupAttributes()
        );

        $this->assertFalse($group->hasPermission($permission));
        $this->assertCount(0, $group->permissions);

        $group->attachPermission($permission);

        $this->assertTrue($group->hasPermission($permission));
        $this->assertCount(1, $group->permissions);

        $group->attachPermission($permission);

        $this->assertTrue($group->hasPermission($permission));
        $this->assertCount(1, $group->permissions);
    }

    /** @test */
    public function it_can_detach_permission()
    {
        $permission = Permission::create([
            'name'        => 'Create users',
            'slug'        => 'auth.users.create',
            'description' => 'Allow to create users',
        ]);

        $group      = $this->createGroup(
            $this->getAuthGroupAttributes()
        );

        $this->assertFalse($group->hasPermission($permission));
        $this->assertCount(0, $group->permissions);

        $group->attachPermission($permission);

        $this->assertTrue($group->hasPermission($permission));
        $this->assertCount(1, $group->permissions);

        $group->detachPermission($permission);

        $this->assertFalse($group->hasPermission($permission));
        $this->assertCount(0, $group->permissions);

        // Make sure it can not detach this
        $group->detachPermission($permission);

        $this->assertFalse($group->hasPermission($permission));
        $this->assertCount(0, $group->permissions);
    }

    /* ------------------------------------------------------------------------------------------------
     |  Other Functions
     | ------------------------------------------------------------------------------------------------
     */
    /**
     * Get auth group attributes.
     *
     * @return array
     */
    private function getAuthGroupAttributes()
    {
        return [
            'name'        => 'Auth Group',
            'slug'        => str_slug('Auth Group', config('laravel-auth.slug-separator')),
            'description' => 'Auth Permissions Group description.',
        ];
    }

    /**
     * Create a permissions group.
     *
     * @param  array  $attributes
     *
     * @return PermissionsGroup
     */
    private function createGroup(array $attributes)
    {
        $group = $this->groupModel->create($attributes);

        return $this->groupModel->find($group->id);
    }
}
