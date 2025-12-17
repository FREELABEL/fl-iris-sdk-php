<?php

declare(strict_types=1);

namespace IRIS\SDK\Tests\Unit\Resources;

use IRIS\SDK\Tests\TestCase;
use IRIS\SDK\Resources\Leads\Lead;
use IRIS\SDK\Resources\Leads\LeadCollection;
use IRIS\SDK\Resources\Leads\LeadActivity;
use IRIS\SDK\Resources\Leads\LeadTask;

class LeadsResourceTest extends TestCase
{
    public function test_list_leads(): void
    {
        $this->mockResponse('GET', '/api/v1/leads', [
            'data' => [
                ['id' => 1, 'name' => 'John Doe', 'email' => 'john@example.com'],
                ['id' => 2, 'name' => 'Jane Smith', 'email' => 'jane@example.com'],
            ],
            'meta' => ['total' => 2],
        ]);

        $leads = $this->iris->leads->list();

        $this->assertInstanceOf(LeadCollection::class, $leads);
        $this->assertCount(2, $leads);
        $this->assertEquals('John Doe', $leads->first()->name);
    }

    public function test_create_lead(): void
    {
        $this->mockResponse('POST', '/api/v1/leads', [
            'id' => 789,
            'name' => 'New Lead',
            'email' => 'new@example.com',
            'company' => 'Test Co',
        ]);

        $lead = $this->iris->leads->create([
            'name' => 'New Lead',
            'email' => 'new@example.com',
            'company' => 'Test Co',
        ]);

        $this->assertInstanceOf(Lead::class, $lead);
        $this->assertEquals(789, $lead->id);
        $this->assertTrue($lead->hasEmail());
    }

    public function test_get_lead(): void
    {
        $this->mockResponse('GET', '/api/v1/leads/456', [
            'id' => 456,
            'name' => 'Test Lead',
            'score' => 85.5,
        ]);

        $lead = $this->iris->leads->get(456);

        $this->assertInstanceOf(Lead::class, $lead);
        $this->assertEquals(456, $lead->id);
        $this->assertTrue($lead->isHot());
    }

    public function test_add_note(): void
    {
        $this->mockResponse('POST', '/api/v1/leads/456/notes', [
            'id' => 1,
            'content' => 'Important note',
        ]);

        $result = $this->iris->leads->addNote(456, 'Important note');

        $this->assertEquals('Important note', $result['content']);
    }

    public function test_activities_sub_resource(): void
    {
        $this->mockResponse('GET', '/api/v1/leads/456/activities', [
            'data' => [
                ['id' => 1, 'lead_id' => 456, 'type' => 'call', 'content' => 'Called client'],
            ],
        ]);

        $activities = $this->iris->leads->activities(456)->all();

        $this->assertCount(1, $activities);
        $this->assertInstanceOf(LeadActivity::class, $activities->first());
    }

    public function test_tasks_sub_resource(): void
    {
        $this->mockResponse('GET', '/api/v1/leads/456/tasks', [
            'data' => [
                ['id' => 1, 'lead_id' => 456, 'title' => 'Follow up', 'status' => 'pending'],
            ],
        ]);

        $tasks = $this->iris->leads->tasks(456)->all();

        $this->assertCount(1, $tasks);
        $this->assertInstanceOf(LeadTask::class, $tasks->first());
        $this->assertTrue($tasks->first()->isPending());
    }

    public function test_generate_response(): void
    {
        $this->mockResponse('GET', '/api/v1/leads/456/generate-response', [
            'response' => 'Generated AI response',
        ]);

        $response = $this->iris->leads->generateResponse(456, 'Context here');

        $this->assertEquals('Generated AI response', $response);
    }

    public function test_attach_bloq(): void
    {
        $this->mockResponse('POST', '/api/v1/leads/456/attach-bloq', [
            'success' => true,
        ]);

        $result = $this->iris->leads->attachBloq(456, 789);

        $this->assertTrue($result);
    }
}
