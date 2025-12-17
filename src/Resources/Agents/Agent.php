<?php

declare(strict_types=1);

namespace IRIS\SDK\Resources\Agents;

/**
 * Agent Model
 *
 * Represents an AI agent with its configuration and capabilities.
 */
class Agent
{
    public int $id;
    public string $name;
    public string $prompt;
    public string $type;
    public string $model;
    public ?int $bloqId;
    public bool $isPublic;
    public ?string $slug;
    public array $personality;
    public array $capabilities;
    public array $integrations;
    public ?string $webhookUrl;
    public ?string $createdAt;
    public ?string $updatedAt;

    /**
     * Raw data from API.
     */
    protected array $attributes;

    public function __construct(array $data)
    {
        $this->attributes = $data;

        $this->id = (int) ($data['id'] ?? 0);
        $this->name = $data['name'] ?? '';
        $this->prompt = $data['prompt'] ?? $data['system_prompt'] ?? '';
        $this->type = $data['type'] ?? 'assistant';
        $this->model = $data['model'] ?? 'gpt-4o-mini';
        $this->bloqId = $data['bloq_id'] ?? null;
        $this->isPublic = (bool) ($data['is_public'] ?? false);
        $this->slug = $data['slug'] ?? null;
        $this->personality = $data['personality'] ?? [];
        $this->capabilities = $data['capabilities'] ?? [];
        $this->integrations = $data['integrations'] ?? [];
        $this->webhookUrl = $data['webhook_url'] ?? null;
        $this->createdAt = $data['created_at'] ?? null;
        $this->updatedAt = $data['updated_at'] ?? null;
    }

    /**
     * Check if agent has a specific capability.
     */
    public function hasCapability(string $capability): bool
    {
        return in_array($capability, $this->capabilities, true);
    }

    /**
     * Check if agent has a specific integration enabled.
     */
    public function hasIntegration(string $integration): bool
    {
        return in_array($integration, $this->integrations, true);
    }

    /**
     * Check if agent has a knowledge base (RAG).
     */
    public function hasKnowledgeBase(): bool
    {
        return $this->bloqId !== null;
    }

    /**
     * Get raw attribute value.
     */
    public function getAttribute(string $key, mixed $default = null): mixed
    {
        return $this->attributes[$key] ?? $default;
    }

    /**
     * Convert to array.
     */
    public function toArray(): array
    {
        return $this->attributes;
    }

    /**
     * Get agent's public URL (if public).
     */
    public function getPublicUrl(string $baseUrl = 'https://app.freelabel.net'): ?string
    {
        if (!$this->isPublic || !$this->slug) {
            return null;
        }

        return "{$baseUrl}/agent/{$this->slug}";
    }
}
