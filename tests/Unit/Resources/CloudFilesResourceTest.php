<?php

declare(strict_types=1);

namespace IRIS\SDK\Tests\Unit\Resources;

use IRIS\SDK\Tests\TestCase;

/**
 * CloudFilesResource Tests
 *
 * Tests for cloud file management, uploads, and agent attachments.
 */
class CloudFilesResourceTest extends TestCase
{
    // ========================================
    // List Operations
    // ========================================

    public function test_list_files(): void
    {
        $this->mockResponse('GET', '/api/v1/cloud-files', [
            'data' => [
                ['id' => 1, 'name' => 'document.pdf', 'type' => 'application/pdf', 'size' => 1024000],
                ['id' => 2, 'name' => 'data.csv', 'type' => 'text/csv', 'size' => 5000],
            ],
            'meta' => ['total' => 2],
        ]);

        $files = $this->iris->cloudFiles->list();

        $this->assertArrayHasKey('data', $files);
        $this->assertCount(2, $files['data']);
    }

    public function test_list_files_with_filters(): void
    {
        $this->mockResponse('GET', '/api/v1/cloud-files', [
            'data' => [
                ['id' => 1, 'name' => 'report.pdf', 'bloq_id' => 32],
            ],
            'meta' => ['total' => 1],
        ]);

        $files = $this->iris->cloudFiles->list([
            'bloq_id' => 32,
            'type' => 'pdf',
        ]);

        $this->assertCount(1, $files['data']);
    }

    public function test_get_files_for_bloq(): void
    {
        $this->mockResponse('GET', '/api/v1/bloqs/32/files', [
            'data' => [
                ['id' => 1, 'name' => 'project-brief.pdf'],
                ['id' => 2, 'name' => 'requirements.docx'],
            ],
        ]);

        $files = $this->iris->cloudFiles->forBloq(32);

        $this->assertArrayHasKey('data', $files);
        $this->assertCount(2, $files['data']);
    }

    public function test_get_files_for_agent(): void
    {
        $this->mockResponse('GET', '/api/v1/agents/456/files', [
            'data' => [
                ['id' => 100, 'name' => 'training-data.csv'],
            ],
        ]);

        $files = $this->iris->cloudFiles->forAgent(456);

        $this->assertArrayHasKey('data', $files);
    }

    // ========================================
    // CRUD Operations
    // ========================================

    public function test_get_file(): void
    {
        $this->mockResponse('GET', '/api/v1/cloud-files/100', [
            'id' => 100,
            'name' => 'document.pdf',
            'title' => 'Project Document',
            'mime_type' => 'application/pdf',
            'size' => 2048000,
            'user_id' => 123,
            'bloq_id' => 32,
            'created_at' => '2025-12-23T10:00:00Z',
        ]);

        $file = $this->iris->cloudFiles->get(100);

        $this->assertEquals(100, $file['id']);
        $this->assertEquals('document.pdf', $file['name']);
    }

    public function test_upload_file(): void
    {
        $this->mockResponse('POST', '/api/v1/cloud-files/upload', [
            'id' => 200,
            'name' => 'new-file.pdf',
            'mime_type' => 'application/pdf',
            'size' => 1024,
            'status' => 'completed',
            'url' => 'https://storage.iris.ai/files/new-file.pdf',
        ]);

        $file = $this->iris->cloudFiles->upload('/path/to/file.pdf', [
            'bloq_id' => 32,
            'title' => 'New File',
        ]);

        $this->assertEquals(200, $file['id']);
        $this->assertEquals('completed', $file['status']);
    }

    public function test_update_file(): void
    {
        $this->mockResponse('PUT', '/api/v1/cloud-files/100', [
            'id' => 100,
            'name' => 'document.pdf',
            'title' => 'Updated Title',
            'description' => 'Updated description',
        ]);

        $file = $this->iris->cloudFiles->update(100, [
            'title' => 'Updated Title',
            'description' => 'Updated description',
        ]);

        $this->assertEquals('Updated Title', $file['title']);
    }

    public function test_delete_file(): void
    {
        $this->mockResponse('DELETE', '/api/v1/cloud-files/100', [
            'success' => true,
        ]);

        $result = $this->iris->cloudFiles->delete(100);

        $this->assertTrue($result);
    }

    // ========================================
    // File Operations
    // ========================================

    public function test_get_download_url(): void
    {
        $this->mockResponse('GET', '/api/v1/cloud-files/100/download', [
            'url' => 'https://storage.iris.ai/files/100/download?token=abc123',
            'expires_at' => '2025-12-23T11:00:00Z',
        ]);

        $url = $this->iris->cloudFiles->downloadUrl(100);

        $this->assertStringContainsString('download', $url);
        $this->assertStringContainsString('token=', $url);
    }

    public function test_get_file_status(): void
    {
        $this->mockResponse('GET', '/api/v1/cloud-files/100/status', [
            'id' => 100,
            'status' => 'ready',
            'processing_progress' => 100,
            'indexed' => true,
            'vector_count' => 45,
        ]);

        $status = $this->iris->cloudFiles->status(100);

        $this->assertEquals('ready', $status['status']);
        $this->assertTrue($status['indexed']);
    }

    public function test_get_file_status_processing(): void
    {
        $this->mockResponse('GET', '/api/v1/cloud-files/200/status', [
            'id' => 200,
            'status' => 'processing',
            'processing_progress' => 60,
            'indexed' => false,
        ]);

        $status = $this->iris->cloudFiles->status(200);

        $this->assertEquals('processing', $status['status']);
        $this->assertEquals(60, $status['processing_progress']);
    }

    public function test_get_extracted_content(): void
    {
        $this->mockResponse('GET', '/api/v1/cloud-files/100/content', [
            'id' => 100,
            'content' => 'This is the extracted text content from the PDF document...',
            'word_count' => 1500,
            'char_count' => 8500,
            'extraction_metadata' => [
                'method' => 'pdf_text_extraction',
                'quality' => 'high',
            ],
        ]);

        $content = $this->iris->cloudFiles->content(100);

        $this->assertArrayHasKey('content', $content);
        $this->assertNotEmpty($content['content']);
    }

    public function test_get_supported_types(): void
    {
        $this->mockResponse('GET', '/api/v1/cloud-files/supported-types', [
            'types' => [
                'application/pdf',
                'text/csv',
                'text/plain',
                'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
                'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            ],
        ]);

        $types = $this->iris->cloudFiles->supportedTypes();

        $this->assertContains('application/pdf', $types);
        $this->assertContains('text/csv', $types);
    }

    // ========================================
    // Agent Attachment Operations
    // ========================================

    public function test_attach_file_to_agent(): void
    {
        $this->mockResponse('POST', '/api/v1/cloud-files/100/attach-agent', [
            'success' => true,
            'file_id' => 100,
            'agent_id' => 456,
        ]);

        $result = $this->iris->cloudFiles->attachToAgent(100, 456);

        $this->assertArrayHasKey('success', $result);
        $this->assertTrue($result['success']);
    }

    public function test_detach_file_from_agent(): void
    {
        $this->mockResponse('POST', '/api/v1/cloud-files/100/detach-agent', [
            'success' => true,
        ]);

        $result = $this->iris->cloudFiles->detachFromAgent(100, 456);

        $this->assertTrue($result);
    }

    public function test_reindex_file(): void
    {
        $this->mockResponse('POST', '/api/v1/cloud-files/100/reindex', [
            'id' => 100,
            'status' => 'indexing',
            'message' => 'File queued for re-indexing',
        ]);

        $result = $this->iris->cloudFiles->reindex(100);

        $this->assertEquals('indexing', $result['status']);
    }

    // ========================================
    // Convenience Methods
    // ========================================

    public function test_upload_for_agent(): void
    {
        $this->mockResponse('POST', '/api/v1/cloud-files/upload', [
            'id' => 300,
            'name' => 'training.csv',
            'mime_type' => 'text/csv',
            'size' => 5000,
            'url' => 'https://storage.iris.ai/files/training.csv',
            'status' => 'completed',
            'created_at' => '2025-12-23T10:00:00Z',
        ]);

        $attachment = $this->iris->cloudFiles->uploadForAgent('/path/to/training.csv', 40, [
            'title' => 'Training Data',
            'description' => 'Agent training document',
        ]);

        // Should return agent attachment format
        $this->assertArrayHasKey('cloud_file_id', $attachment);
        $this->assertArrayHasKey('name', $attachment);
        $this->assertArrayHasKey('size', $attachment);
        $this->assertArrayHasKey('type', $attachment);
        $this->assertArrayHasKey('processingStatus', $attachment);
        $this->assertEquals(300, $attachment['cloud_file_id']);
    }

    public function test_upload_multiple_for_agent(): void
    {
        // First file upload
        $this->mockResponse('POST', '/api/v1/cloud-files/upload', [
            'id' => 301,
            'name' => 'file1.pdf',
            'mime_type' => 'application/pdf',
            'size' => 1000,
            'status' => 'completed',
        ]);

        // Note: MockHttpClient will reuse same response for repeated calls
        // In real tests you'd want to queue multiple responses

        $attachments = $this->iris->cloudFiles->uploadMultipleForAgent([
            '/path/to/file1.pdf',
        ], 40);

        $this->assertIsArray($attachments);
        $this->assertCount(1, $attachments);
        $this->assertArrayHasKey('cloud_file_id', $attachments[0]);
    }
}
