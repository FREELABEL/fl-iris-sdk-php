# Clip Cutter Custom Branding Extension - Technical Specification

**For:** Peso/EMC Music Series Project (Lead #553)  
**Use Case:** Add EMC branding (logo, intro/outro bumpers) to artist submission clips  
**Priority:** HIGH  

---

## 🎯 Business Requirement

Peso/EMC wants to create branded promotional clips for the music series:
- Custom EMC logo watermark (instead of FREELABEL logo)
- Intro bumper ("EMC Music Series presents...")
- Outro bumper ("Submit your music at EMC.com")
- Custom audio drop/sting

**Example Flow:**
```
[EMC Intro (3s)] → [Artist Clip (60s)] → [EMC Outro (5s)] = Final branded video
         ↓                  ↓                    ↓
   Custom bumper      Custom logo         Submission CTA
                      watermark           
```

---

## ✅ Current Clip Cutter Capabilities

**Location:** `FFMPEGService.php`, `COPYCAT_AI_INTEGRATION.md`

**What Works Now:**
- ✅ YouTube video → clip extraction
- ✅ FREELABEL logo watermark overlay
- ✅ LUT color grading (HardBoost.cube)
- ✅ Radio drop audio overlay (start/middle/end)
- ✅ Social media publishing (Instagram, TikTok, X)
- ✅ Auto-caption generation

**Hardcoded Elements:**
```php
// Line 295 in FFMPEGService.php
$logoPath = storage_path('app/utils/logo/fllogo-instagram.png'); // HARDCODED

// Radio drop is fixed file
$radioDropPath = $assets['radioDrop']; // Fixed FREELABEL drop
```

---

## 🛠️ Proposed Extensions

### Extension 1: Custom Logo Watermark

**Add Parameter:**
```php
'custom_logo' => '/path/to/emc-logo.png'  // Optional, defaults to FREELABEL
'logo_position' => 'top-right'             // top-left, top-right, bottom-left, bottom-right
'logo_size' => 'medium'                    // small, medium, large (50px, 100px, 200px)
'logo_opacity' => 0.8                      // 0.0-1.0
```

**CLI Usage:**
```bash
./bin/iris tools clip-cut \
  --url="https://youtube.com/watch?v=abc" \
  --start-time="0:10" \
  --duration="60s" \
  --custom-logo="/path/to/emc-logo.png" \
  --logo-position="bottom-right" \
  --publish-social \
  --platforms="instagram,tiktok"
```

**FFmpeg Implementation:**
```bash
# Current (hardcoded FREELABEL logo)
-filter_complex "[1:v] scale=50:-1 [logo]; [0:v][logo] overlay=20:20"

# New (parameterized)
-filter_complex "[1:v] scale={$logoSize}:-1 [logo]; [0:v][logo] overlay={$position}:format=auto,format=yuv420p[v]" -map "[v]"
```

---

### Extension 2: Intro/Outro Video Bumpers

**Add Parameters:**
```php
'intro_video' => '/path/to/emc-intro-3s.mp4'  // Optional intro bumper
'outro_video' => '/path/to/emc-outro-5s.mp4'  // Optional outro bumper
'transition_type' => 'cut'                     // cut, fade, dissolve
'transition_duration' => 1                     // seconds (for fade/dissolve)
```

**CLI Usage:**
```bash
./bin/iris tools clip-cut \
  --url="https://youtube.com/watch?v=abc" \
  --start-time="0:10" \
  --duration="60s" \
  --intro-video="/storage/emc-intro-3s.mp4" \
  --outro-video="/storage/emc-outro-5s.mp4" \
  --transition="fade" \
  --custom-logo="/storage/emc-logo.png" \
  --publish-social \
  --platforms="instagram,tiktok"
```

**FFmpeg Implementation (Concat Filter):**
```bash
# Step 1: Create concat file list
file '/path/to/intro.mp4'
file '/path/to/main-clip.mp4'
file '/path/to/outro.mp4'

# Step 2: Concatenate with transitions
ffmpeg -f concat -safe 0 -i concat_list.txt \
  -filter_complex "
    [0:v]fade=t=out:st=2.5:d=0.5[v0];
    [1:v]fade=t=in:st=0:d=0.5,fade=t=out:st=59.5:d=0.5[v1];
    [2:v]fade=t=in:st=0:d=0.5[v2];
    [v0][0:a][v1][1:a][v2][2:a]concat=n=3:v=1:a=1[outv][outa]
  " \
  -map "[outv]" -map "[outa]" output.mp4
```

---

### Extension 3: Custom Audio Overlay

**Add Parameters:**
```php
'custom_audio_drop' => '/path/to/emc-sting.mp3'  // Optional, defaults to FREELABEL
'audio_drop_position' => 'end'                    // start, middle, end, none
'audio_drop_volume' => 0.8                        // 0.0-1.0 (relative to main audio)
```

**CLI Usage:**
```bash
./bin/iris tools clip-cut \
  --url="https://youtube.com/watch?v=abc" \
  --start-time="0:10" \
  --duration="60s" \
  --custom-audio-drop="/storage/emc-sound-sting.mp3" \
  --audio-drop-position="start" \
  --audio-drop-volume=0.7
```

---

### Extension 4: Brand Preset Profiles

**Create reusable brand profiles** for easy application:

**Brand Profile Structure:**
```json
{
  "brand_id": "emc",
  "name": "Exotic Mafia Co",
  "logo": "/storage/brands/emc/logo.png",
  "logo_position": "bottom-right",
  "logo_size": "medium",
  "intro_video": "/storage/brands/emc/intro-3s.mp4",
  "outro_video": "/storage/brands/emc/outro-5s.mp4",
  "audio_drop": "/storage/brands/emc/sound-sting.mp3",
  "audio_drop_position": "end",
  "lut_file": "/storage/brands/emc/custom-lut.cube",
  "default_caption_template": "🎵 {{title}} | EMC Music Series\n\nSubmit your track: EMC.com\n\n#EMCMusic #AustinMusic",
  "social_platforms": ["instagram", "tiktok", "youtube"]
}
```

**CLI Usage with Profile:**
```bash
# Apply entire EMC brand profile
./bin/iris tools clip-cut \
  --url="https://youtube.com/watch?v=abc" \
  --start-time="0:10" \
  --duration="60s" \
  --brand-profile="emc" \
  --publish-social

# Override specific elements
./bin/iris tools clip-cut \
  --url="https://youtube.com/watch?v=abc" \
  --start-time="0:10" \
  --duration="60s" \
  --brand-profile="emc" \
  --custom-logo="/storage/special-event-logo.png"  # Override logo
```

**SDK Usage:**
```php
// Create brand profile
$iris->brands->create([
    'brand_id' => 'emc',
    'name' => 'Exotic Mafia Co',
    'logo' => '/storage/brands/emc/logo.png',
    'intro_video' => '/storage/brands/emc/intro-3s.mp4',
    'outro_video' => '/storage/brands/emc/outro-5s.mp4',
    // ... other settings
]);

// Use brand profile in clip cutter
$result = $iris->agents->callIntegration(11, 'copycat-ai', 'trigger_video_clipper', [
    'youtube_url' => 'https://www.youtube.com/watch?v=abc123',
    'start' => '0:10',
    'duration' => '60s',
    'brand_profile' => 'emc',  // Apply all EMC branding
    'publish_to_social' => true,
    'social_platforms' => ['instagram', 'tiktok'],
]);

// Or customize individual elements
$result = $iris->agents->callIntegration(11, 'copycat-ai', 'trigger_video_clipper', [
    'youtube_url' => 'https://www.youtube.com/watch?v=abc123',
    'start' => '0:10',
    'duration' => '60s',
    'custom_logo' => '/storage/emc-logo.png',
    'intro_video' => '/storage/emc-intro-3s.mp4',
    'outro_video' => '/storage/emc-outro-5s.mp4',
    'custom_audio_drop' => '/storage/emc-sting.mp3',
    'publish_to_social' => true,
    'social_platforms' => ['instagram', 'tiktok'],
]);
```

---

## 📐 Technical Implementation Plan

### Phase 1: Parameterize Existing Features (3-4 days)

**Task 1.1: Custom Logo Support** (1 day)
- Add `custom_logo`, `logo_position`, `logo_size`, `logo_opacity` parameters
- Update FFmpeg overlay filter with dynamic positioning
- Default to FREELABEL logo if not provided

**Task 1.2: Custom Audio Drop** (1 day)
- Add `custom_audio_drop`, `audio_drop_volume` parameters
- Make audio drop optional (`audio_drop_position='none'`)
- Default to FREELABEL drop if not provided

**Task 1.3: CLI Parameter Support** (1 day)
- Add new options to `clip-cut` command
- Update help documentation
- Add validation for file paths

### Phase 2: Video Concatenation (4-5 days)

**Task 2.1: FFmpeg Concat Implementation** (2 days)
- Implement video concatenation with transitions
- Handle resolution/framerate matching
- Add fade/dissolve transitions

**Task 2.2: Intro/Outro Parameters** (2 days)
- Add `intro_video`, `outro_video` parameters
- Create concat file list dynamically
- Handle audio mixing between segments

**Task 2.3: Testing** (1 day)
- Test various video formats
- Test with/without intros/outros
- Test transition types

### Phase 3: Brand Profiles (3-4 days)

**Task 3.1: Brand Database Schema** (1 day)
```sql
CREATE TABLE brand_profiles (
    id BIGINT PRIMARY KEY,
    brand_id VARCHAR(50) UNIQUE,
    user_id BIGINT,
    name VARCHAR(255),
    settings JSON,  -- logo, videos, audio, templates
    created_at TIMESTAMP,
    updated_at TIMESTAMP
);
```

**Task 3.2: Brand API Endpoints** (2 days)
- `POST /api/brands` - Create brand profile
- `GET /api/brands/{id}` - Get brand profile
- `PUT /api/brands/{id}` - Update brand profile
- `DELETE /api/brands/{id}` - Delete brand profile
- `GET /api/brands` - List user's brand profiles

**Task 3.3: Integration with Clip Cutter** (1 day)
- Add `brand_profile` parameter
- Load brand settings from database
- Merge with override parameters

---

## 🎬 EMC Use Case Example

**Scenario:** Peso receives artist submission. Wants to create branded teaser clip.

**Step 1: Setup EMC Brand Profile** (one-time)
```bash
./bin/iris brands:create \
  --brand-id="emc" \
  --name="Exotic Mafia Co" \
  --logo="/storage/emc-logo.png" \
  --intro="/storage/emc-intro-3s.mp4" \
  --outro="/storage/emc-outro-submit.mp4" \
  --audio-drop="/storage/emc-sting.mp3" \
  --caption-template="🎵 {{title}} | EMC Music Series\n\nSubmit: EMC.com"
```

**Step 2: Create Branded Clip from Artist Video**
```bash
./bin/iris tools clip-cut \
  --url="https://youtube.com/watch?v=artist-song" \
  --start-time="1:15" \
  --duration="30s" \
  --brand-profile="emc" \
  --caption="New artist alert! 🔥 Check out this Austin talent" \
  --publish-social \
  --platforms="instagram,tiktok"
```

**Output:**
```
[EMC Intro 3s] → [Artist Clip 30s] → [EMC Outro 5s]
Total: 38 seconds
- EMC logo bottom-right throughout
- EMC sound sting at end
- Custom caption with EMC branding
- Posted to Instagram + TikTok
```

---

## 🔧 FFmpeg Command Example (Full Implementation)

```bash
# Complete branded clip with intro, outro, custom logo, and audio drop

# Step 1: Extract clip
ffmpeg -i youtube_video.mp4 -ss 75 -t 30 -c copy clip_raw.mp4

# Step 2: Add logo watermark to clip
ffmpeg -i clip_raw.mp4 -i emc-logo.png \
  -filter_complex "[1:v]scale=100:-1[logo];[0:v][logo]overlay=W-w-20:H-h-20:format=auto,format=yuv420p[v]" \
  -map "[v]" -map 0:a -c:a copy clip_with_logo.mp4

# Step 3: Concatenate intro + clip + outro with fades
echo "file 'emc-intro-3s.mp4'" > concat_list.txt
echo "file 'clip_with_logo.mp4'" >> concat_list.txt
echo "file 'emc-outro-5s.mp4'" >> concat_list.txt

ffmpeg -f concat -safe 0 -i concat_list.txt \
  -i emc-sting.mp3 \
  -filter_complex "
    [0:v]fade=t=out:st=2.5:d=0.5[v0];
    [1:v]fade=t=in:st=0:d=0.5,fade=t=out:st=29.5:d=0.5[v1];
    [2:v]fade=t=in:st=0:d=0.5[v2];
    [v0][0:a][v1][1:a][v2][2:a]concat=n=3:v=1:a=1[vout][abase];
    [3:a]adelay=33000|33000,volume=0.7[asting];
    [abase][asting]amix=inputs=2:duration=longest[aout]
  " \
  -map "[vout]" -map "[aout]" \
  -c:v libx264 -preset slow -crf 18 -pix_fmt yuv420p \
  -c:a aac -b:a 192k \
  final_branded_clip.mp4
```

---

## 💰 Development Estimate

### Time Breakdown:
| Task | Estimate | Priority |
|------|----------|----------|
| Parameterize logo/audio | 2 days | HIGH |
| Video concat (intro/outro) | 4 days | HIGH |
| Brand profiles system | 4 days | MEDIUM |
| CLI updates | 1 day | HIGH |
| Testing & docs | 2 days | MEDIUM |
| **TOTAL** | **13 days** | - |

### MVP (Week 1-2): 7 days
- Custom logo support
- Custom audio drop
- Basic intro/outro concat
- CLI parameters
- **Delivers:** EMC can brand their clips

### Full Feature (Week 3): 6 days
- Brand profile system
- Advanced transitions
- Bulk processing
- **Delivers:** Reusable brand templates

---

## 🎯 Success Criteria

**For Peso/EMC:**
- ✅ Can add EMC logo to any clip
- ✅ Can add 3s intro bumper
- ✅ Can add 5s outro with submission CTA
- ✅ Can apply custom audio sting
- ✅ Can save as "EMC brand profile" for reuse
- ✅ One command creates fully branded clip
- ✅ Automatically posts to Instagram/TikTok

**Example Command:**
```bash
# One command, fully branded output
./bin/iris tools clip-cut \
  --url="https://youtube.com/artist-video" \
  --start="1:15" \
  --duration="30s" \
  --brand-profile="emc" \
  --publish-social
```

---

## 📚 Documentation Needed

1. **Brand Profile Guide** - How to create/manage brand profiles
2. **Custom Branding Tutorial** - Step-by-step for custom logos/videos
3. **FFmpeg Reference** - Technical details for advanced users
4. **EMC Specific Guide** - Quick start for Peso's team

---

## 🚀 Next Steps

1. **Approve specification** - Review with Peso
2. **Gather assets** - EMC logo, intro video, outro video, audio sting
3. **Start Phase 1** - Parameterize existing features (1 week)
4. **Demo to Peso** - Show custom branding working
5. **Phase 2** - Brand profiles (if time permits)

---

**Status:** ✅ **READY FOR DEVELOPMENT**  
**Complexity:** MEDIUM (building on existing FFmpeg infrastructure)  
**Risk:** LOW (extending working system)  
**Value:** HIGH (enables white-label content creation)

This feature would make the clip cutter a **white-label video production tool** that any client (not just Peso) can use for branded content! 🎬🚀
