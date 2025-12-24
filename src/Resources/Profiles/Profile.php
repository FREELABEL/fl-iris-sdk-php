<?php

declare(strict_types=1);

namespace IRIS\SDK\Resources\Profiles;

/**
 * Profile Model
 *
 * Represents a user profile with media content.
 */
class Profile
{
    public int $pk;              // Numeric primary key
    public string $id;           // Username/slug
    public ?int $user_id;
    public ?string $name;
    public ?string $username;
    public ?string $display_name;
    public ?string $bio;
    public ?string $avatar_url;
    public ?string $photo;
    public ?string $city;
    public ?string $state;
    public ?int $views;
    public ?int $followers;
    public ?int $following;
    public ?array $stats;
    public ?string $created_at;
    public ?string $updated_at;

    public function __construct(array $data)
    {
        $this->pk = (int) ($data['pk'] ?? $data['id'] ?? 0);
        $this->id = (string) ($data['id'] ?? '');
        $this->user_id = isset($data['user_id']) ? (int) $data['user_id'] : null;
        $this->name = $data['name'] ?? null;
        $this->username = $data['username'] ?? $data['id'] ?? '';
        $this->display_name = $data['display_name'] ?? $data['name'] ?? null;
        $this->bio = $data['bio'] ?? null;
        $this->avatar_url = $data['avatar_url'] ?? null;
        $this->photo = $data['photo'] ?? $data['avatar_url'] ?? null;
        $this->city = $data['city'] ?? null;
        $this->state = $data['state'] ?? null;
        $this->views = isset($data['views']) ? (int) $data['views'] : null;
        $this->followers = isset($data['followers']) ? (int) $data['followers'] : null;
        $this->following = isset($data['following']) ? (int) $data['following'] : null;
        $this->stats = $data['stats'] ?? null;
        $this->created_at = $data['created_at'] ?? null;
        $this->updated_at = $data['updated_at'] ?? null;
    }

    public function toArray(): array
    {
        return [
            'pk' => $this->pk,
            'id' => $this->id,
            'user_id' => $this->user_id,
            'name' => $this->name,
            'username' => $this->username,
            'display_name' => $this->display_name,
            'bio' => $this->bio,
            'avatar_url' => $this->avatar_url,
            'photo' => $this->photo,
            'city' => $this->city,
            'state' => $this->state,
            'views' => $this->views,
            'followers' => $this->followers,
            'following' => $this->following,
            'stats' => $this->stats,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
