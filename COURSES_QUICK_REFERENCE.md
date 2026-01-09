# Courses API - Quick Reference

One-page cheat sheet for the IRIS Courses API.

---

## SDK Usage

```php
use IRIS\SDK\IRIS;

$iris = new IRIS([
    'api_key' => 'your_api_key',
    'user_id' => 123
]);
```

### Browse Marketplace

```php
// List all courses
$courses = $iris->courses->list();

// Filter by difficulty
$courses = $iris->courses->list(['difficulty' => 'beginner']);

// Search
$courses = $iris->courses->search('React', ['difficulty' => 'intermediate']);

// Pagination
$courses = $iris->courses->list(['page' => 2, 'per_page' => 10]);
```

### Get Course

```php
// Get full course with chapters
$course = $iris->courses->get(123);

// Access structure
$course->title
$course->difficulty_level  // 'beginner', 'intermediate', 'advanced'
$course->estimated_duration_minutes
$course->learning_objectives  // Array
$course->chapters  // Array of chapters with content
```

### Create Course

```php
$course = $iris->courses->create([
    'program_id' => 5,
    'instructor_user_id' => 456,
    'difficulty_level' => 'intermediate',
    'estimated_duration_minutes' => 480,
    'is_published' => true,
    'certificate_enabled' => true,
    'learning_objectives' => ['Learn X', 'Master Y']
]);
```

### Update/Delete

```php
// Update
$course = $iris->courses->update(123, [
    'is_published' => true,
    'difficulty_level' => 'advanced'
]);

// Delete
$iris->courses->delete(123);
```

### Chapters

```php
// Add chapter
$chapter = $iris->courses->addChapter(123, [
    'title' => 'Introduction',
    'description' => 'Learn the basics',
    'display_order' => 1
]);

// Update chapter
$iris->courses->updateChapter(123, $chapterId, [
    'title' => 'Updated Title'
]);

// Reorder chapters (pass IDs in desired order)
$iris->courses->reorderChapters(123, [45, 46, 47]);

// Delete chapter
$iris->courses->deleteChapter(123, $chapterId);
```

### Content

```php
// Add video
$iris->courses->addContentToChapter(123, $chapterId, [
    'content_type' => 'video',
    'content_data' => [
        'video_id' => 789,
        'title' => 'Introduction Video',
        'duration_seconds' => 600
    ],
    'display_order' => 1
]);

// Add article
$iris->courses->addContentToChapter(123, $chapterId, [
    'content_type' => 'article',
    'content_data' => [
        'article_id' => 456,
        'title' => 'Setup Guide',
        'estimated_read_time_minutes' => 10
    ],
    'display_order' => 2
]);

// Reorder content
$iris->courses->reorderChapterContent(123, $chapterId, [100, 101, 102]);

// Remove content
$iris->courses->removeContentFromChapter(123, $chapterId, $contentId);
```

### Enrollment & Progress

```php
// Enroll user
$enrollment = $iris->courses->enroll(123, $userId);

// Get progress
$progress = $iris->courses->getProgress(123, $userId);
// Returns: ['total_items', 'completed_items', 'percentage', 'is_completed']

// Update progress (video completed)
$iris->courses->updateProgress(123, $userId, [
    'chapter_id' => 1,
    'content_id' => 789,
    'content_type' => 'video',
    'status' => 'completed',  // 'not_started', 'in_progress', 'completed'
    'progress_percentage' => 100,
    'time_spent_seconds' => 600
]);

// Update progress (in-progress video)
$iris->courses->updateProgress(123, $userId, [
    'chapter_id' => 1,
    'content_id' => 790,
    'content_type' => 'video',
    'status' => 'in_progress',
    'progress_percentage' => 50
]);
```

---

## CLI Usage

```bash
# List courses
bin/iris call courses.list
bin/iris call courses.list difficulty=beginner
bin/iris call courses.search "React"

# Get course
bin/iris call courses.get 123

# Create course
bin/iris call courses.create '{
  "program_id": 5,
  "instructor_user_id": 456,
  "difficulty_level": "intermediate",
  "is_published": true
}'

# Update course
bin/iris call courses.update 123 '{"is_published": true}'

# Delete course
bin/iris call courses.delete 123

# Add chapter
bin/iris call courses.addChapter 123 '{
  "title": "Introduction",
  "display_order": 1
}'

# Update chapter
bin/iris call courses.updateChapter 123 45 '{"title": "New Title"}'

# Reorder chapters
bin/iris call courses.reorderChapters 123 '[45, 46, 47]'

# Delete chapter
bin/iris call courses.deleteChapter 123 45

# Add video to chapter
bin/iris call courses.addContentToChapter 123 45 '{
  "content_type": "video",
  "content_data": {"video_id": 789, "title": "Intro Video"},
  "display_order": 1
}'

# Add article to chapter
bin/iris call courses.addContentToChapter 123 45 '{
  "content_type": "article",
  "content_data": {"article_id": 456, "title": "Setup Guide"},
  "display_order": 2
}'

# Reorder content
bin/iris call courses.reorderChapterContent 123 45 '[100, 101, 102]'

# Remove content
bin/iris call courses.removeContentFromChapter 123 45 100

# Enroll user
bin/iris call courses.enroll 123 456

# Get progress
bin/iris call courses.getProgress 123 456

# Update progress
bin/iris call courses.updateProgress 123 456 '{
  "chapter_id": 1,
  "content_id": 789,
  "content_type": "video",
  "status": "completed",
  "progress_percentage": 100
}'
```

---

## Data Structure

### Course Object
```json
{
  "id": 123,
  "program_id": 5,
  "instructor_user_id": 456,
  "difficulty_level": "intermediate",
  "estimated_duration_minutes": 480,
  "is_published": true,
  "certificate_enabled": true,
  "thumbnail_url": "https://...",
  "learning_objectives": ["Learn X", "Master Y"],
  "created_at": "2024-01-01T00:00:00Z",
  "updated_at": "2024-01-01T00:00:00Z",
  "instructor": {
    "id": 456,
    "name": "John Doe",
    "email": "john@example.com"
  },
  "program": {
    "id": 5,
    "name": "Advanced React",
    "description": "...",
    "base_price": 99.00
  },
  "chapters": [...]
}
```

### Chapter Object
```json
{
  "id": 45,
  "course_id": 123,
  "title": "Introduction to React",
  "description": "Learn the basics",
  "display_order": 1,
  "created_at": "2024-01-01T00:00:00Z",
  "content": [...]
}
```

### Content Object
```json
{
  "id": 100,
  "chapter_id": 45,
  "content_type": "video",
  "content_id": 789,
  "display_order": 1,
  "created_at": "2024-01-01T00:00:00Z",
  "content_data": {
    "video_id": 789,
    "title": "What is React?",
    "duration_seconds": 600
  }
}
```

### Progress Object
```json
{
  "course_id": 123,
  "user_id": 456,
  "total_items": 12,
  "completed_items": 5,
  "percentage": 42,
  "is_completed": false,
  "items": [
    {
      "chapter_id": 1,
      "content_id": 789,
      "content_type": "video",
      "status": "completed",
      "progress_percentage": 100,
      "time_spent_seconds": 600,
      "updated_at": "2024-01-01T00:00:00Z"
    }
  ]
}
```

---

## API Endpoints

| Method | Endpoint | Description |
|--------|----------|-------------|
| GET | `/api/v1/courses` | List marketplace courses |
| GET | `/api/v1/courses/{id}` | Get course details |
| POST | `/api/v1/courses` | Create course |
| PUT | `/api/v1/courses/{id}` | Update course |
| DELETE | `/api/v1/courses/{id}` | Delete course |
| POST | `/api/v1/courses/{id}/enroll` | Enroll user |
| GET | `/api/v1/courses/{id}/progress` | Get progress |
| POST | `/api/v1/courses/{id}/progress` | Update progress |
| POST | `/api/v1/courses/{id}/chapters` | Add chapter |
| PUT | `/api/v1/courses/{id}/chapters/{chapterId}` | Update chapter |
| DELETE | `/api/v1/courses/{id}/chapters/{chapterId}` | Delete chapter |
| PUT | `/api/v1/courses/{id}/chapters/reorder` | Reorder chapters |
| POST | `/api/v1/courses/{id}/chapters/{chapterId}/content` | Add content |
| DELETE | `/api/v1/courses/{id}/chapters/{chapterId}/content/{contentId}` | Remove content |
| PUT | `/api/v1/courses/{id}/chapters/{chapterId}/content/reorder` | Reorder content |

---

## Key Concepts

- **Course** = Learning structure (extends Program for billing)
- **Chapter** = Sequential organization (e.g., "Week 1", "Module 2")
- **Content** = Actual learning materials (videos, articles)
- **Progress** = Per-item tracking (not just overall completion)

### Difficulty Levels
- `beginner` - New to the topic
- `intermediate` - Some experience
- `advanced` - Expert level

### Content Types
- `video` - Video from `tv` table
- `article` - Article from `magazine` table

### Progress Status
- `not_started` - User hasn't viewed
- `in_progress` - Partially completed
- `completed` - 100% done

---

## Common Patterns

### Creating a Full Course
```php
// 1. Create Program (billing)
$program = $iris->programs->create([...]);

// 2. Create Course (learning)
$course = $iris->courses->create(['program_id' => $program->id, ...]);

// 3. Add Chapters
$chapter1 = $iris->courses->addChapter($course->id, [...]);
$chapter2 = $iris->courses->addChapter($course->id, [...]);

// 4. Add Content
$iris->courses->addContentToChapter($course->id, $chapter1['id'], [...]);
$iris->courses->addContentToChapter($course->id, $chapter1['id'], [...]);

// 5. Publish
$iris->courses->update($course->id, ['is_published' => true]);
```

### Student Learning Flow
```php
// 1. Browse courses
$courses = $iris->courses->list(['difficulty' => 'beginner']);

// 2. View details
$course = $iris->courses->get(123);

// 3. Enroll
$iris->courses->enroll(123, $userId);

// 4. Track progress as content is consumed
$iris->courses->updateProgress(123, $userId, [
    'chapter_id' => 1,
    'content_id' => 789,
    'status' => 'completed'
]);

// 5. Check overall progress
$progress = $iris->courses->getProgress(123, $userId);
```

### Bulk Operations (CLI)
```bash
# Enroll multiple users
for user_id in 456 457 458; do
  bin/iris call courses.enroll 123 $user_id
done

# Check completion rates
for user_id in 456 457 458; do
  progress=$(bin/iris call courses.getProgress 123 $user_id --json)
  echo "User $user_id: $(echo $progress | jq -r '.percentage')%"
done
```

---

## Full Documentation

- **[COURSES_API.md](COURSES_API.md)** - Complete guide with examples
- **[README.md](README.md)** - Main SDK documentation
- **[CLI_USAGE.md](CLI_USAGE.md)** - CLI usage guide

---

## Support

- GitHub: https://github.com/your-org/iris-sdk-php
- Docs: https://docs.heyiris.io
- Support: support@heyiris.io
