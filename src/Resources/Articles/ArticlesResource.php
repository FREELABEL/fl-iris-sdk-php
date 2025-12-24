<?php

declare(strict_types=1);

namespace IRIS\SDK\Resources\Articles;

use IRIS\SDK\Config;
use IRIS\SDK\Http\Client;

/**
 * Articles Resource
 *
 * Generate articles from various sources:
 * - YouTube videos (transcript-based)
 * - Topics (research-based)
 * - Webpages (content extraction)
 * - RSS feeds (synthesis)
 *
 * @example
 * ```php
 * // Generate article from YouTube video
 * $result = $iris->articles->generateFromVideo([
 *     'youtube_url' => 'https://www.youtube.com/watch?v=abc123',
 *     'article_length' => 'medium',
 *     'article_style' => 'informative',
 * ]);
 *
 * // Generate from any source
 * $result = $iris->articles->generate([
 *     'source_type' => 'video',
 *     'source' => 'https://www.youtube.com/watch?v=abc123',
 * ]);
 * ```
 */
class ArticlesResource
{
    protected Client $http;
    protected Config $config;

    public function __construct(Client $http, Config $config)
    {
        $this->http = $http;
        $this->config = $config;
    }

    /**
     * Generate article from various sources.
     *
     * @param array{
     *     source_type: string,
     *     source: string,
     *     article_length?: string,
     *     article_style?: string,
     *     profile_id?: int,
     *     publish_to_fl?: bool,
     *     publish_to_social?: bool,
     *     social_platforms?: array,
     *     photo?: string,
     *     generate_image?: bool,
     *     user_id?: int
     * } $params Generation parameters
     * @return array Job dispatch result
     *
     * @example
     * ```php
     * $result = $iris->articles->generate([
     *     'source_type' => 'video',
     *     'source' => 'https://www.youtube.com/watch?v=dQw4w9WgXcQ',
     *     'article_length' => 'medium',
     *     'article_style' => 'informative',
     * ]);
     * ```
     */
    public function generate(array $params): array
    {
        // Auto-inject user_id if not provided
        if (!isset($params['user_id']) && $this->config->userId) {
            $params['user_id'] = $this->config->userId;
        }

        return $this->http->post('/api/v1/articles/generate', $params, 'fl-api');
    }

    /**
     * Generate article from YouTube video.
     *
     * Convenience method for video source type.
     *
     * @param array{
     *     youtube_url: string,
     *     article_length?: string,
     *     article_style?: string,
     *     profile_id?: int,
     *     publish_to_fl?: bool,
     *     publish_to_social?: bool,
     *     social_platforms?: array,
     *     photo?: string,
     *     generate_image?: bool,
     *     user_id?: int
     * } $params Generation parameters
     * @return array Job dispatch result
     *
     * @example
     * ```php
     * $result = $iris->articles->generateFromVideo([
     *     'youtube_url' => 'https://www.youtube.com/watch?v=dQw4w9WgXcQ',
     *     'article_length' => 'long',
     *     'article_style' => 'analysis',
     * ]);
     * ```
     */
    public function generateFromVideo(array $params): array
    {
        // Auto-inject user_id if not provided
        if (!isset($params['user_id']) && $this->config->userId) {
            $params['user_id'] = $this->config->userId;
        }

        return $this->http->post('/api/v1/articles/generate-from-video', $params, 'fl-api');
    }

    /**
     * Generate article from topic research.
     *
     * @param string $topic Topic to research
     * @param array $options Additional options
     * @return array Job dispatch result
     */
    public function generateFromTopic(string $topic, array $options = []): array
    {
        return $this->generate(array_merge([
            'source_type' => 'topic',
            'source' => $topic,
        ], $options));
    }

    /**
     * Generate article from webpage.
     *
     * @param string $url Webpage URL
     * @param array $options Additional options
     * @return array Job dispatch result
     */
    public function generateFromWebpage(string $url, array $options = []): array
    {
        return $this->generate(array_merge([
            'source_type' => 'webpage',
            'source' => $url,
        ], $options));
    }

    /**
     * Generate article from RSS feed.
     *
     * @param string $feedUrl RSS feed URL
     * @param array $options Additional options
     * @return array Job dispatch result
     */
    public function generateFromRss(string $feedUrl, array $options = []): array
    {
        return $this->generate(array_merge([
            'source_type' => 'rss',
            'source' => $feedUrl,
        ], $options));
    }

    /**
     * Create an article directly.
     *
     * @param array{
     *     profile_id: int,
     *     title: string,
     *     content: string,
     *     photo?: string,
     *     is_bulletin?: bool,
     *     status?: int
     * } $data Article data
     * @return array Created article
     */
    public function create(array $data): array
    {
        return $this->http->post('/api/v1/articles', $data, 'fl-api');
    }
}
