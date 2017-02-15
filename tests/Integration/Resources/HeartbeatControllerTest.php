<?php

namespace REBELinBLUE\Deployer\Tests\Integration\Resources;

use Illuminate\Foundation\Testing\DatabaseMigrations;
use REBELinBLUE\Deployer\Heartbeat;
use REBELinBLUE\Deployer\Project;
use REBELinBLUE\Deployer\Tests\AuthenticatedTestCase;
use Symfony\Component\HttpFoundation\Response;

/**
 * @coversDefaultClass \REBELinBLUE\Deployer\Http\Controllers\Resources\HeartbeatController
 */
class HeartbeatControllerTest extends AuthenticatedTestCase
{
    use DatabaseMigrations;

    /**
     * @covers ::__construct
     * @covers ::store
     * @covers \REBELinBLUE\Deployer\Http\Requests\StoreHeartbeatRequest
     * @covers \REBELinBLUE\Deployer\Http\Requests\Request
     */
    public function testStore()
    {
        factory(Project::class)->create();

        $input = [
            'name'       => 'My Cronjob',
            'interval'   => 5,
            'project_id' => 1,
        ];

        $output = array_merge([
            'id' => 1,
        ], $input);

        $response = $this->postJson('/heartbeats', $input);

        $response->assertStatus(Response::HTTP_CREATED)->assertJson($output);
        $this->assertDatabaseHas('heartbeats', $output);
    }

    /**
     * @covers ::__construct
     * @covers ::update
     * @covers \REBELinBLUE\Deployer\Http\Requests\StoreHeartbeatRequest
     * @covers \REBELinBLUE\Deployer\Http\Requests\Request
     */
    public function testUpdate()
    {
        $original = 'My Cronjob';
        $updated  = 'Your Cronjob';

        /** @var Heartbeat $heartbeat */
        $heartbeat = factory(Heartbeat::class)->create(['name' => $original]);

        $data = array_only($heartbeat->fresh()->toArray(), [
            'name',
            'interval',
        ]);

        $input = array_merge($data, [
            'name' => $updated,
        ]);

        $response = $this->putJson('/heartbeats/1', $input);

        $response->assertStatus(Response::HTTP_OK)->assertJson($input);
        $this->assertDatabaseHas('heartbeats', ['name' => $updated]);
        $this->assertDatabaseMissing('heartbeats', ['name' => $original]);
    }

    /**
     * @covers ::__construct
     * @covers ::update
     */
    public function testUpdateReturnsErrorWhenInvalid()
    {
        $response = $this->putJson('/heartbeats/1000', [
            'name'     => 'My Cronjob',
            'interval' => 5,
        ]);

        $response->assertStatus(Response::HTTP_NOT_FOUND);
    }

    /**
     * @covers \REBELinBLUE\Deployer\Http\Controllers\Resources\ResourceController::destroy
     */
    public function testDelete()
    {
        $name = 'My Cronjob';

        factory(Heartbeat::class)->create(['name' => $name]);

        $response = $this->deleteJson('/heartbeats/1');

        $response->assertStatus(Response::HTTP_NO_CONTENT);
        $this->assertDatabaseMissing('heartbeats', ['name' => $name, 'deleted_at' => null]);
    }

    /**
     * @covers \REBELinBLUE\Deployer\Http\Controllers\Resources\ResourceController::destroy
     */
    public function testDeleteReturnsErrorWhenInvalid()
    {
        $response = $this->deleteJson('/heartbeats/1000');

        $response->assertStatus(Response::HTTP_NOT_FOUND);
    }
}
