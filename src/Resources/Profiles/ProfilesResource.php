<?php

declare(strict_types=1);

namespace IRIS\SDK\Resources\Profiles;

use IRIS\SDK\Config;
use IRIS\SDK\Http\Client;

/**
 * Profiles Resource
 *
 * Manage user profiles and media content (videos, tracks, images).
 *
 * @example
 * ```php
 * // List user profiles
 * $profiles = $iris->profiles->list();
 *
 * // Search profiles
 * $results = $iris->profiles->search('siralexmayo', [
 *     'limit' => 5,
 *     'order_by' => 'views'
 * ]);
 *
 * // Get media counts for a profile
 * $counts = $iris->profiles->getMediaCounts(1);
 *
 * // Get videos for a profile
 * $videos = $iris->profiles->getVideos(1);
 *
 * // Get tracks/audio for a profile
 * $tracks = $iris->profiles->getTracks(69);
 * ```
 */
class ProfilesResource
{
    protected Client $http;
    protected Config $config;

    public function __construct(Client $http, Config $config)
    {
        $this->http = $http;
        $this->config = $config;
    }

    /**
     * List all profiles for the current user.
     *
     * @param array{
     *     page?: int,
     *     per_page?: int,
     *     search?: string,
     *     limit?: int,
     *     order_by?: string
     * } $filters Filter options
     * @return ProfileCollection
     */
    public function list(array $filters = []): ProfileCollection
    {
        $userId = $this->config->requireUserId();
        $response = $this->http->get("/api/v1/user/{$userId}/profiles", $filters);

        return new ProfileCollection(
            array_map(fn($data) => new Profile($data), $response['data'] ?? $response),
            $response['meta'] ?? []
        );
    }

    /**
     * Search profiles by username or keyword.
     *
     * @param string $search Search query
     * @param array{
     *     limit?: int,
     *     order_by?: string
     * } $options Search options
     * @return ProfileCollection
     */
    public function search(string $search, array $options = []): ProfileCollection
    {
        $params = array_merge(['search' => $search], $options);
        $response = $this->http->get("/api/v1/user/profiles", $params);

        return new ProfileCollection(
            array_map(fn($data) => new Profile($data), $response['data'] ?? $response),
            $response['meta'] ?? []
        );
    }

    /**
     * Get a single profile by ID.
     *
     * @param int $profileId Profile ID
     * @return Profile
     */
    public function get(int $profileId): Profile
    {
        $response = $this->http->get("/api/v1/profile/{$profileId}");

        return new Profile($response);
    }

    /**
     * Get media counts (videos, tracks, images) for a profile.
     *
     * @param int $profileId Profile ID
     * @return MediaCounts
     */
    public function getMediaCounts(int $profileId): MediaCounts
    {
        $response = $this->http->get("/api/v1/profile/{$profileId}/media/counts");

        return new MediaCounts($response);
    }

    /**
     * Get all videos for a profile.
     *
     * @param int $profileId Profile ID
     * @param array{
     *     page?: int,
     *     per_page?: int,
     *     sort?: string
     * } $filters Filter options
     * @return VideoCollection
     */
    public function getVideos(int $profileId, array $filters = []): VideoCollection
    {
        $response = $this->http->get("/api/v1/user/profile/media/{$profileId}/videos", $filters);

        return new VideoCollection(
            array_map(fn($data) => new Video($data), $response['data'] ?? $response),
            $response['meta'] ?? []
        );
    }

    /**
     * Get all audio tracks for a profile.
     *
     * @param int $profileId Profile ID
     * @param array{
     *     page?: int,
     *     per_page?: int,
     *     sort?: string
     * } $filters Filter options
     * @return TrackCollection
     */
    public function getTracks(int $profileId, array $filters = []): TrackCollection
    {
        $response = $this->http->get("/api/v1/user/profile/media/{$profileId}/tracks", $filters);

        return new TrackCollection(
            array_map(fn($data) => new Track($data), $response['data'] ?? $response),
            $response['meta'] ?? []
        );
    }

    /**
     * Create a new profile.
     *
     * @param array{
     *     username: string,
     *     display_name?: string,
     *     bio?: string,
     *     avatar_url?: string,
     *     cover_url?: string
     * } $data Profile data
     * @return Profile
     */
    public function create(array $data): Profile
    {
        $userId = $this->config->requireUserId();
        $response = $this->http->post("/api/v1/user/{$userId}/profiles", $data);

        return new Profile($response);
    }

    /**
     * Update an existing profile.
     *
     * @param int $profileId Profile ID
     * @param array $data Updated profile data
     * @return Profile
     */
    public function update(int $profileId, array $data): Profile
    {
        $response = $this->http->patch("/api/v1/profile/{$profileId}", $data);

        return new Profile($response);
    }

    /**
     * Delete a profile.
     *
     * @param int $profileId Profile ID
     * @return bool
     */
    public function delete(int $profileId): bool
    {
        $this->http->delete("/api/v1/profile/{$profileId}");

        return true;
    }
}
