# CopyCatAI Integration Guide

Complete guide to using the CopyCatAI integration for content generation, video processing, and audio extraction.

## Table of Contents

- [Overview](#overview)
- [Setup](#setup)
- [Features](#features)
  - [Article Generation](#article-generation)
    - [From YouTube Video](#from-youtube-video)
    - [From Topic](#from-topic-research-based)
    - [From Webpage/RSS](#from-webpagerss)
    - [From Research Notes](#from-research-notes-new)
    - [From Draft](#from-draft-new)
  - [YouTube Audio Download](#youtube-audio-download)
  - [Video Downloading](#video-downloading)
  - [Clip Cutting](#clip-cutting)
- [CLI Usage](#cli-usage)
- [PHP SDK Usage](#php-sdk-usage)
- [Troubleshooting](#troubleshooting)

## Overview

CopyCatAI is an integrated content and media processing service that provides:

- **Article Generation:** AI-powered article writing with custom topics
- **YouTube Audio Download:** Extract high-quality MP3 files (320kbps) from YouTube videos
- **Video Downloading:** Download full videos from various platforms
- **Clip Cutting:** Extract and edit specific segments from videos

All features are accessible via:
- IRIS CLI (`./bin/iris tools`)
- PHP SDK (`$iris->agents->callIntegration()`)
- Direct HTTP API calls

## Setup

### Requirements

**Backend:**
- yt-dlp installed (`/usr/local/bin/yt-dlp`)
- FFmpeg installed (`/usr/bin/ffmpeg`)
- PHP 8.1+ with GD/Imagick extensions

**Agent Configuration:**

1. Enable CopycatAI in agent settings:
```php
// bloq_agents.settings column
{
    "agentIntegrations": {
        "copycat-ai": {
            "enabled": true
        }
    }
}
```

2. Create user integration record:
```sql
INSERT INTO integrations (user_id, type, name, status, category, capabilities, credentials)
VALUES (193, 'copycat-ai', 'CopycatAI', 'active', 'content', '[]', '{"api_key":"test"}');
```

## Features

### Article Generation

Generate AI-powered articles from multiple source types with customizable parameters. The article generation pipeline supports six input modes, each optimized for different content creation workflows.

#### Source Types Overview

| Source Type | Description | Use Case |
|-------------|-------------|----------|
| `video` | YouTube video URL | Convert video content to articles |
| `topic` | Research-based generation | Create articles from AI research |
| `webpage` | Single webpage URL | Transform web content |
| `rss` | RSS feed URL | Synthesize from multiple articles |
| `research-notes` | Raw notes/bullet points | Structure unorganized research |
| `draft` | Existing article draft | Polish and refine drafts |

---

#### From YouTube Video

Generate articles from YouTube video transcripts.

**CLI Usage:**
```bash
# Basic video → article
./bin/iris tools article \
  --url="https://www.youtube.com/watch?v=dQw4w9WgXcQ" \
  --length=medium \
  --style=informative

# With publishing options
./bin/iris tools article \
  --url="https://www.youtube.com/watch?v=abc123" \
  --source-type=video \
  --profile-id=9203684 \
  --publish
```

**PHP SDK:**
```php
$result = $iris->articles->generateFromVideo([
    'youtube_url' => 'https://www.youtube.com/watch?v=dQw4w9WgXcQ',
    'article_length' => 'medium',
    'article_style' => 'informative',
    'profile_id' => 9203684,
]);
```

---

#### From Topic (Research-Based)

Generate articles based on AI-powered topic research.

**CLI Usage:**
```bash
./bin/iris tools article \
  --topic="Future of AI in Healthcare" \
  --source-type=topic \
  --length=long \
  --style=analysis
```

**PHP SDK:**
```php
$result = $iris->articles->generateFromTopic(
    'Future of AI in Healthcare',
    ['article_length' => 'long', 'article_style' => 'analysis']
);
```

---

#### From Webpage/RSS

Generate articles from web content or RSS feeds.

**CLI Usage:**
```bash
# From webpage
./bin/iris tools article \
  --url="https://example.com/blog/interesting-article" \
  --source-type=webpage \
  --length=medium

# From RSS feed
./bin/iris tools article \
  --url="https://example.com/feed.xml" \
  --source-type=rss \
  --length=short
```

**PHP SDK:**
```php
// From webpage
$result = $iris->articles->generateFromWebpage(
    'https://example.com/blog/article',
    ['article_length' => 'medium']
);

// From RSS
$result = $iris->articles->generateFromRss(
    'https://example.com/feed.xml',
    ['article_length' => 'short']
);
```

---

#### From Research Notes (NEW)

Transform raw, unstructured research notes into polished, structured articles. This mode applies **heavy AI structuring** - extracting themes, building narrative flow, and creating coherent article structure from disorganized content.

**When to use:**
- Bullet points and raw notes from research sessions
- Unorganized findings from multiple sources
- Brain dumps that need heavy structuring
- Interview notes or meeting summaries

**CLI Usage:**
```bash
# From inline content
./bin/iris tools article \
  --source-type=research \
  --content="AI trends 2025: - Telemedicine up 300% - AI diagnostics improving - Patient engagement focus - Remote monitoring gaining traction" \
  --length=medium \
  --style=informative \
  --profile-id=9203684

# Using alias 'notes' instead of 'research'
./bin/iris tools article \
  --source-type=notes \
  --content="Healthcare AI findings: ..." \
  --profile-id=9203684

# From file (supports .md, .txt, .docx, .pdf)
./bin/iris tools article \
  --source-type=research-notes \
  --file=/path/to/research-notes.md \
  --length=long \
  --style=analysis \
  --profile-id=9203684

# Save as draft (don't publish)
./bin/iris tools article \
  --source-type=research \
  --file=/path/to/notes.txt \
  --draft \
  --profile-id=9203684
```

**PHP SDK:**
```php
// From inline content
$result = $iris->articles->generateFromResearchNotes(
    "AI trends 2025: - Telemedicine up 300% - AI diagnostics improving...",
    [
        'article_length' => 'medium',
        'article_style' => 'informative',
        'profile_id' => 9203684,
    ]
);

// Using the general generate method
$result = $iris->articles->generate([
    'source_type' => 'research-notes',
    'source' => 'Your raw research notes here...',
    'article_length' => 'long',
    'profile_id' => 9203684,
    'publish_to_fl' => true,
]);
```

**Source Type Aliases:**
- `research-notes` (canonical)
- `research` (alias)
- `notes` (alias)

---

#### From Draft (NEW)

Polish an existing article draft to publication quality. This mode applies **light editing** - preserving your voice while improving grammar, clarity, flow, and structure. Optionally provide specific editing instructions for guided revisions.

**When to use:**
- Rough drafts that need professional polish
- Articles requiring grammar and style cleanup
- Content that needs tone adjustments
- Drafts needing structural improvements

**CLI Usage:**
```bash
# Basic draft polishing
./bin/iris tools article \
  --source-type=draft \
  --content="# My Draft Article\n\nThis is my rough article that needs polishing..." \
  --profile-id=9203684

# With specific editing instructions
./bin/iris tools article \
  --source-type=draft \
  --content="Your draft content here..." \
  --edits="Make more casual and conversational, add practical examples" \
  --profile-id=9203684

# From file with editing instructions
./bin/iris tools article \
  --source-type=draft \
  --file=/path/to/draft.md \
  --edits="Strengthen the introduction, add a call-to-action at the end" \
  --length=medium \
  --profile-id=9203684

# Save polished version as draft (don't publish)
./bin/iris tools article \
  --source-type=draft \
  --file=/path/to/rough-draft.docx \
  --edits="Make more technical, add code examples" \
  --draft \
  --profile-id=9203684
```

**PHP SDK:**
```php
// Basic draft polishing
$result = $iris->articles->generateFromDraft(
    "# My Draft Article\n\nThis is my rough article that needs polishing...",
    [
        'profile_id' => 9203684,
    ]
);

// With editing instructions
$result = $iris->articles->generateFromDraft(
    "Your draft content here...",
    [
        'editing_instructions' => 'Make more casual, add practical examples',
        'profile_id' => 9203684,
        'article_length' => 'medium',
    ]
);

// Using the general generate method
$result = $iris->articles->generate([
    'source_type' => 'draft',
    'source' => 'Your draft content...',
    'editing_instructions' => 'Improve flow and add transitions',
    'profile_id' => 9203684,
    'publish_to_fl' => true,
]);
```

---

#### Article Generation Options

| Option | CLI Flag | Description | Default |
|--------|----------|-------------|---------|
| Source Type | `--source-type`, `-s` | video, topic, webpage, rss, research-notes, draft | `video` |
| URL | `--url`, `-u` | YouTube URL, webpage, or RSS feed | - |
| Topic | `--topic`, `-t` | Topic for research-based generation | - |
| Content | `--content` | Inline content for research-notes/draft | - |
| File | `--file`, `-f` | File path (.md, .txt, .docx, .pdf) | - |
| Edits | `--edits` | Editing instructions for draft mode | - |
| Length | `--length` | short, medium, long | `medium` |
| Style | `--style` | informative, editorial, newsletter, analysis | `informative` |
| Profile ID | `--profile-id` | Profile ID for publishing | - |
| Publish | `--publish` | Publish to Freelabel | true |
| Draft | `--draft` | Save as draft (unpublished) | false |
| No Publish | `--no-publish` | Don't publish (test mode) | false |

---

#### Legacy Integration Method

For backward compatibility, you can also use the agent integration method:

**CLI Usage:**
```bash
./bin/iris tools article \
  --topic="Future of AI in Healthcare" \
  --agent-id=11 \
  --min-words=1000 \
  --max-words=1500
```

**PHP SDK:**
```php
$result = $iris->agents->callIntegration(11, 'copycat-ai', 'generate_article', [
    'topic' => 'Future of AI in Healthcare',
    'min_words' => 1000,
    'max_words' => 1500,
    'tone' => 'professional',
]);

echo $result['result']['article'];
```

---

### YouTube Audio Download

Download YouTube videos as high-quality MP3 files with embedded metadata and thumbnails.

**CLI Usage:**
```bash
# Basic download
./bin/iris tools youtube-audio \
  --url="https://www.youtube.com/watch?v=dQw4w9WgXcQ" \
  --agent-id=11

# Custom filename
./bin/iris tools youtube-audio \
  --url="https://www.youtube.com/watch?v=abc123" \
  --agent-id=11 \
  --output-filename="my_song"

# JSON output
./bin/iris tools youtube-audio \
  --url="https://www.youtube.com/watch?v=abc123" \
  --agent-id=11 \
  --json
```

**PHP SDK:**
```php
$result = $iris->agents->callIntegration(11, 'copycat-ai', 'download_youtube_audio', [
    'youtube_url' => 'https://www.youtube.com/watch?v=dQw4w9WgXcQ',
    'upload_to_gcs' => false,  // Local storage (default)
    'output_filename' => 'my_song',  // Optional
]);

// Response
$downloadUrl = $result['result']['download_url'];
$title = $result['result']['title'];
$fileSize = $result['result']['file_size'];  // MB
$quality = $result['result']['quality'];  // "320kbps"
```

**Parameters:**
| Parameter | Type | Required | Default | Description |
|-----------|------|----------|---------|-------------|
| `youtube_url` | string | Yes | - | Full YouTube video URL |
| `agent_id` | integer | Yes | 11 | Agent ID with CopycatAI enabled |
| `output_filename` | string | No | `youtube_audio_{videoId}_{timestamp}` | Custom filename (no extension) |
| `upload_to_gcs` | boolean | No | `false` | Upload to Google Cloud Storage |

**Response Example:**
```json
{
    "success": true,
    "integration": "copycat-ai",
    "action": "download_youtube_audio",
    "result": {
        "success": true,
        "youtube_url": "https://www.youtube.com/watch?v=jbs5h7hBmzY",
        "title": "(FREE) Kaytranada Type Beat - Sicily",
        "download_url": "https://local.raichu.freelabel.net/storage/kaytranada_sicily.mp3",
        "file_name": "kaytranada_sicily.mp3",
        "file_size": 5.95,
        "format": "mp3",
        "quality": "320kbps",
        "storage_provider": "local"
    }
}
```

**Features:**
- ✅ 320kbps MP3 quality
- ✅ Video title auto-extraction
- ✅ Thumbnail embedded as album art
- ✅ Metadata (title, artist) included
- ✅ Local storage (default) or GCS upload
- ✅ File preservation (no auto-cleanup)

**File Locations:**
- **Local Dev:** `/path/to/fl-api/storage/app/public/` → `https://local.raichu.freelabel.net/storage/`
- **Production:** `fl-api/storage/app/public/` → `https://apiv2.heyiris.io/storage/`
- **GCS:** `https://storage.googleapis.com/gs-dev-media-assets/youtube-audio/`

**Common Use Cases:**

1. **Music Collection:**
```bash
./bin/iris tools youtube-audio \
  --url="https://www.youtube.com/watch?v=abc123" \
  --agent-id=11 \
  --output-filename="track_001"
```

2. **Batch Processing:**
```php
$videos = ['dQw4w9WgXcQ', 'jbs5h7hBmzY', 'R2ZsTB09kb4'];

foreach ($videos as $videoId) {
    $result = $iris->agents->callIntegration(11, 'copycat-ai', 'download_youtube_audio', [
        'youtube_url' => "https://www.youtube.com/watch?v={$videoId}",
    ]);
    
    echo "✓ {$result['result']['title']}\n";
}
```

3. **Cloud Archive:**
```php
// Upload to GCS for long-term storage
$result = $iris->agents->callIntegration(11, 'copycat-ai', 'download_youtube_audio', [
    'youtube_url' => 'https://www.youtube.com/watch?v=abc123',
    'upload_to_gcs' => true,
]);
```

**Technical Details:**

yt-dlp command used:
```bash
yt-dlp \
  --no-playlist \
  -x \
  --audio-format mp3 \
  --audio-quality 0 \
  --embed-thumbnail \
  --add-metadata \
  --print after_move:title \
  --ffmpeg-location /usr/bin/ffmpeg \
  -o /output/path.mp3 \
  "VIDEO_URL"
```

Process:
1. yt-dlp downloads best audio stream
2. FFmpeg converts to 320kbps MP3
3. Thumbnail embedded as album art
4. Metadata written to MP3 tags
5. File preserved from cleanup
6. External URL generated

---

### Video Downloading

Download full videos from YouTube and other platforms.

**CLI Usage:**
```bash
./bin/iris tools video-download \
  --url="https://www.youtube.com/watch?v=abc123" \
  --agent-id=11 \
  --quality=1080p
```

**PHP SDK:**
```php
$result = $iris->agents->callIntegration(11, 'copycat-ai', 'download_video', [
    'video_url' => 'https://www.youtube.com/watch?v=abc123',
    'quality' => '1080p',
    'format' => 'mp4',
]);
```

**Parameters:**
- `video_url` (string, required): Video URL
- `quality` (string, optional): Video quality (720p, 1080p, 1440p, 4k)
- `format` (string, optional): Output format (mp4, webm, mkv)

---

### Clip Cutting

Extract specific segments from YouTube videos and publish to social media.

**⚠️ Social media publishing is REQUIRED.** Clips must have a delivery target. All clips are also automatically saved to your Cloud Files for dashboard access.

**CLI Usage:**

```bash
# Basic usage (auto-generated FREELABEL caption)
./bin/iris tools clip-cut \
  --url="https://www.youtube.com/watch?v=..." \
  --start-time="0:10" \
  --duration="60s" \
  --publish-social \
  --platforms="instagram,tiktok"

# Publish to all platforms including X
./bin/iris tools clip-cut \
  --url="https://www.youtube.com/watch?v=..." \
  --start-time="0:10" \
  --duration="60s" \
  --publish-social \
  --platforms="instagram,tiktok,x"

# With custom caption
./bin/iris tools clip-cut \
  --url="https://www.youtube.com/watch?v=..." \
  --start-time="0:10" \
  --duration="60s" \
  --publish-social \
  --platforms="instagram,tiktok,x" \
  --caption="Your custom caption here"
```

**CLI Options:**

| Option | Required | Default | Description |
|--------|----------|---------|-------------|
| `--url` | Yes | - | YouTube video URL |
| `--start-time` | Yes | - | Start time (M:SS or H:MM:SS format) |
| `--duration` | Yes | - | Clip duration (e.g., "60s", "90s") |
| `--publish-social` | **Yes** | - | Enable social media publishing (REQUIRED) |
| `--platforms` | No | `instagram,tiktok` | Comma-separated list: instagram,tiktok,x,threads |
| `--caption` | No | Auto-generated | Custom caption (auto-generates FREELABEL branded caption if not provided) |
| `--json` | No | `false` | Output in JSON format |

**Note:** If `--publish-social` is not specified, the command will fail with an error showing the correct usage. All clips are automatically saved to Cloud Files regardless of social publishing.

**PHP SDK:**
```php
// Basic usage (auto-generated caption)
$result = $iris->agents->callIntegration(11, 'copycat-ai', 'trigger_video_clipper', [
    'youtube_url' => 'https://www.youtube.com/watch?v=abc123',
    'start' => '0:10',
    'duration' => '60s',
    'publish_to_social' => true,                           // REQUIRED
    'social_platforms' => ['instagram', 'tiktok'],         // REQUIRED
]);

// Publish to all platforms including X
$result = $iris->agents->callIntegration(11, 'copycat-ai', 'trigger_video_clipper', [
    'youtube_url' => 'https://www.youtube.com/watch?v=abc123',
    'start' => '0:10',
    'duration' => '60s',
    'publish_to_social' => true,
    'social_platforms' => ['instagram', 'tiktok', 'x'],
]);

// With custom caption
$result = $iris->agents->callIntegration(11, 'copycat-ai', 'trigger_video_clipper', [
    'youtube_url' => 'https://www.youtube.com/watch?v=abc123',
    'start' => '0:10',
    'duration' => '60s',
    'publish_to_social' => true,
    'social_platforms' => ['instagram', 'tiktok', 'x'],
    'caption' => 'Your custom caption here! 🔥',
]);
```

**Supported Platforms:**
- `instagram` - Instagram Reels
- `tiktok` - TikTok videos
- `x` - X (Twitter) video posts
- `threads` - Threads posts (requires separate UploadPost configuration)

**Features:**
- Frame-accurate cutting with FFmpeg
- FREELABEL logo watermark overlay
- LUT color grading (HardBoost.cube)
- Radio drop audio overlay
- Auto-caption generation with FREELABEL branding
- 1080p output with LANCZOS scaling
- Early timing validation with safety buffer

---

### ⚠️ Timing Validation & Safety Buffer

**IMPORTANT:** The system validates clip timing BEFORE downloading to prevent wasted compute.

**Safety Buffer:**
- YouTube API reports duration rounded to whole seconds (e.g., 85s)
- Actual video may be slightly shorter (e.g., 84.361s)
- A **1-second safety buffer** is applied: `max_safe_duration = video_duration - 1`

**Example:**
```
YouTube API reports: 85 seconds
Actual video length: 84.361 seconds
Safe maximum duration: 84 seconds

Request for 85s → REJECTED with suggested fix of 84s
Request for 84s → ACCEPTED
```

**Validation Error Response:**
```json
{
    "success": false,
    "error": "Clip timing exceeds video length. Video is ~85 seconds (safe: 84s). With start time of 0s, maximum duration is 84s.",
    "suggested_fix": "Use duration=84s"
}
```

---

### Video Clipper (YouTube → Social Media)

Cut clips from YouTube videos with automatic FREELABEL branding, LUT color grading, radio drops, and optional social media publishing.

**CLI Usage:**
```bash
# Basic clip (local storage only)
./bin/iris tools trigger-video-clipper \
  --url="https://www.youtube.com/watch?v=abc123" \
  --start="0:30" \
  --duration="60s" \
  --agent-id=11

# With social media publishing
./bin/iris tools trigger-video-clipper \
  --url="https://www.youtube.com/watch?v=abc123" \
  --start="1:00" \
  --duration="60s" \
  --agent-id=11 \
  --publish-to-social \
  --platforms="instagram,tiktok"
```

**PHP SDK:**
```php
// Basic clip (no social media)
$result = $iris->agents->callIntegration(11, 'copycat-ai', 'trigger_video_clipper', [
    'youtube_url' => 'https://www.youtube.com/watch?v=abc123',
    'start' => '0:30',
    'duration' => '60s',
]);

// With social media publishing
$result = $iris->agents->callIntegration(11, 'copycat-ai', 'trigger_video_clipper', [
    'youtube_url' => 'https://www.youtube.com/watch?v=abc123',
    'start' => '1:00',
    'duration' => '60s',
    'publish_to_social' => true,
    'social_platforms' => ['instagram', 'tiktok'],
]);

// With custom caption
$result = $iris->agents->callIntegration(11, 'copycat-ai', 'trigger_video_clipper', [
    'youtube_url' => 'https://www.youtube.com/watch?v=abc123',
    'start' => '0:00',
    'duration' => '90s',
    'text' => 'Custom caption here! 🔥',
    'publish_to_social' => true,
    'social_platforms' => ['instagram', 'tiktok'],
]);
```

**Parameters:**
| Parameter | Type | Required | Default | Description |
|-----------|------|----------|---------|-------------|
| `youtube_url` | string | Yes | - | Full YouTube video URL |
| `start` | string | No | `0:00` | Start time (M:SS or H:MM:SS format) |
| `duration` | string | No | `60s` | Clip duration (e.g., "60s", "90s") |
| `text` | string | No | Auto-generated | Caption for social media (see Auto-Caption below) |
| `publish_to_social` | boolean | No | `false` | Enable social media publishing |
| `social_platforms` | array | No | `[]` | Platforms: `instagram`, `tiktok`, `threads` |
| `apply_lut` | boolean | No | `true` | Apply color grading LUT |
| `lut_percentage` | integer | No | `15` | LUT intensity (0-100) |
| `radio_drop_position` | string | No | `end` | Radio drop position: `start`, `middle`, `end` |
| `keep_local` | boolean | No | `true` | Keep video on local storage |

**Auto-Caption Generation:**

When no `text`, `title`, or `marketing_title` parameter is provided, the system automatically generates a FREELABEL-branded marketing caption using:

1. **YouTube Data API** (or oEmbed fallback) to fetch video metadata
2. **OpenAI GPT-4o-mini** to generate a "News Ticker" style caption

The auto-generated caption follows this format:
```
[ARTIST] dropped visuals to 'Song Name' featuring [GUEST].
Y'all tapping in? 👀

Watch the full video on FREELABEL.NET

Join the #1 music creator club. Build your career with FREELABEL ➡️ @freelabelnet

#Artist #SongName #NewMusic #HipHop #Viral
```

**Response Example:**
```json
{
    "success": true,
    "integration": "copycat-ai",
    "action": "trigger_video_clipper",
    "result": {
        "success": true,
        "message": "Video clipping process started",
        "youtube_url": "https://www.youtube.com/watch?v=abc123",
        "start_time": "0:30",
        "duration": "60s",
        "job_id": 5,
        "estimated_time": "60-120 seconds"
    }
}
```

**Features:**
- ✅ 1080p output with LANCZOS scaling
- ✅ FREELABEL logo watermark overlay
- ✅ LUT color grading (HardBoost.cube)
- ✅ Radio drop audio overlay
- ✅ Auto-caption with FREELABEL branding
- ✅ Social media publishing (Instagram, TikTok, Threads)
- ✅ AI profile selection for publishing

---

### ⚠️ Social Media Publishing Caveats

**IMPORTANT:** Social media publishing requires specific configuration:

1. **`publish_to_social` must be explicitly set to `true`**
   - Without this parameter, clips are only saved locally
   - Social media publishing will NOT happen by default

2. **`social_platforms` array must be specified**
   - Must be an array: `['instagram', 'tiktok']`
   - Not a comma-separated string

3. **Platform-Specific Requirements:**

   | Platform | Requirement | Notes |
   |----------|-------------|-------|
   | `instagram` | UploadPost account linked | Default profile: `@thediscoverpage_` |
   | `tiktok` | UploadPost account linked | Same profile as Instagram |
   | `threads` | **Separate configuration required** | Must be explicitly enabled in UploadPost |

4. **Threads Platform:**
   - Threads requires separate account configuration in UploadPost
   - Error message if not configured: `"Profile @thediscoverpage_ has no Threads account configured"`
   - **Recommendation:** Only include `threads` if you've verified it's configured

**Working Example (Instagram + TikTok only):**
```php
$result = $iris->agents->callIntegration(11, 'copycat-ai', 'trigger_video_clipper', [
    'youtube_url' => 'https://www.youtube.com/watch?v=abc123',
    'start' => '0:30',
    'duration' => '60s',
    'publish_to_social' => true,           // REQUIRED for publishing
    'social_platforms' => ['instagram', 'tiktok'],  // Array, not string
]);
```

**Common Mistakes:**
```php
// ❌ WRONG: No publishing parameters - clip saved locally only
$result = $iris->agents->callIntegration(11, 'copycat-ai', 'trigger_video_clipper', [
    'youtube_url' => 'https://www.youtube.com/watch?v=abc123',
    'start' => '0:30',
    'duration' => '60s',
]);

// ❌ WRONG: String instead of array for platforms
'social_platforms' => 'instagram,tiktok'  // This won't work

// ❌ WRONG: Including unconfigured Threads
'social_platforms' => ['instagram', 'tiktok', 'threads']  // Fails if Threads not configured

// ✅ CORRECT: Proper configuration
'publish_to_social' => true,
'social_platforms' => ['instagram', 'tiktok'],
```

## CLI Usage

### Available Commands

```bash
# Article generation
./bin/iris tools article --topic="..." --agent-id=11

# YouTube audio download
./bin/iris tools youtube-audio --url="..." --agent-id=11

# Video download
./bin/iris tools video-download --url="..." --agent-id=11

# Clip cutting (social media publishing REQUIRED)
./bin/iris tools clip-cut \
  --url="https://www.youtube.com/watch?v=..." \
  --start-time="0:10" \
  --duration="60s" \
  --publish-social \
  --platforms="instagram,tiktok,x"

# Clip cutting with custom caption
./bin/iris tools clip-cut \
  --url="https://www.youtube.com/watch?v=..." \
  --start-time="0:10" \
  --duration="60s" \
  --publish-social \
  --platforms="instagram,tiktok,x" \
  --caption="Your custom caption"
```

### Global Options

- `--agent-id, -a`: Agent ID (default: 11)
- `--json`: Output in JSON format
- `--help, -h`: Display help information

## PHP SDK Usage

### Initialize SDK

```php
use IRIS\SDK\IRIS;

$iris = new IRIS([
    'api_key' => 'your_api_key',
    'user_id' => 193,
    'base_url' => 'https://apiv2.heyiris.io',
]);
```

### Call Integration

```php
// General pattern
$result = $iris->agents->callIntegration(
    $agentId,        // Agent ID
    'copycat-ai',    // Integration type
    $action,         // Action name
    $parameters      // Action parameters
);

// Available actions:
// - generate_article
// - download_youtube_audio
// - download_video
// - cut_clip

// Check success
if ($result['success'] && $result['result']['success']) {
    // Process result
    $data = $result['result'];
} else {
    // Handle error
    $error = $result['error'] ?? 'Unknown error';
}
```

## Troubleshooting

### YouTube Audio Download Issues

**"File not found" after download:**
```bash
# Check if file exists
ls -la fl-api/storage/app/public/*.mp3

# Verify storage symlink
ls -la fl-api/public/storage

# Check logs
docker exec fl-api tail -100 storage/logs/laravel.log
```

**"yt-dlp not found":**
```bash
# Verify installation
docker exec fl-api which yt-dlp

# Install if missing
docker exec fl-api bash -c "curl -L https://github.com/yt-dlp/yt-dlp/releases/latest/download/yt-dlp -o /usr/local/bin/yt-dlp && chmod a+rx /usr/local/bin/yt-dlp"
```

**404 on download URL:**
- Check file was preserved (look for "preserved from cleanup" in logs)
- Verify nginx serves storage directory
- Ensure `APP_URL` in `.env` is correct

### Integration Issues

**"Integration not found":**
1. Enable in agent settings: `settings['agentIntegrations']['copycat-ai']['enabled'] = true`
2. Create user integration record in database
3. Verify agent ID is correct

**"Unauthorized" errors:**
- Check integration status is 'active'
- Verify user_id matches integration record
- Ensure agent has integration enabled

### Performance Tips

- **Download Times:** 5-30 seconds per video depending on length
- **File Sizes:** ~3-10 MB per minute at 320kbps
- **Concurrent Downloads:** Supported, but limit to 5-10 simultaneous
- **Storage Cleanup:** Implement custom cleanup for old files

### Common Errors

| Error | Cause | Solution |
|-------|-------|----------|
| "Video unavailable" | Video removed or private | Verify video URL is accessible |
| "Format not supported" | Unsupported video format | Try different quality setting |
| "FFmpeg error" | Missing dependency | Install FFmpeg in container |
| "Permission denied" | Storage permissions | Fix directory permissions: `chmod -R 755 storage` |

## Support

For additional help:
- Check [TECHNICAL.md](TECHNICAL.md) for SDK documentation
- Review agent integration settings in database
- Check Docker container logs: `docker logs fl-api`
- Verify environment configuration in `.env`

## License

MIT License - see [LICENSE](LICENSE) for details.
