# PESO/EMC Platform Development Handoff
Date: January 9, 2026
Role: Client Success Engineer
Project: Peso/EMC Pre-SXSW Music Series

## 1. Overview
The Peso/EMC project requires two primary platform extensions to the existing IRIS infrastructure. This document serves as the technical requirements guide for the Platform and Integration development teams.

---

## 2. Deliverable A: Clip Cutter Branding Engine
Currently, the `FFMPEGService` and `/api/v1/video/clip-branded` endpoint utilize hardcoded FreeLABEL assets. For Peso/EMC, we need to transition this into a multi-tenant "Branding Engine."

### Key Requirements (Dev Team):
- **Parameterize Logo:** Implement `logo_path`, `logo_position`, and `logo_opacity` in the `FFMPEGService` clipping methods.
- **Support Video Bumpers:** Implement FFmpeg `concat` support to allow `intro_video` and `outro_video` parameters.
- **Audio Overlays:** Allow passing a `brand_audio_drop` (e.g., EMC sound sting) with volume ducking logic.
- **Brand Profiles Model:** Create a schema to store these assets (logo, bumpers, drops) as a "Brand Profile" so the CLI/SDK can simply pass `brand_id=emc`.

**Success Criteria:** Peso can run `./bin/iris video:clip [url] --brand=emc` and receive a fully branded EMC video.

---

## 3. Deliverable B: Dynamic Program Forms & Intake
The artist submission process for the compilation album requires a flexible form system.

### Key Requirements (Dev Team):
- **Custom Field Schema:** Ensure the `programs.create` endpoint correctly persists and renders `custom_fields` (Instagram handle, Spotify link, File upload URL).
- **Public URL Stability:** Ensure every program/form generated returns a reliable `https://heyiris.io/programs/{slug}` URL immediately upon creation.
- **Submission API Expansion:** Optimize the `program-enrollments` retrieval to support filtering by field values (e.g., find all submissions with a valid Spotify link).
- **Asset Attachment:** Ensure form-submitted files (demo tracks) are correctly triaged into the `CloudFile` system and associated with the Lead record that is created/updated during enrollment.

**Success Criteria:** Peso can generate a unique submission form for the "Austin March 2026 Compilation" via SDK, send the link to artists, and see their demos pop up in his CRM automatically.

---

## 4. Current SDK Status
The following helper methods have been added to the PHP SDK to prepare for these platform updates:
- `iris->programs->getSubmissions($id)` (Alias for getEnrollments)
- `iris->programs->get($id)->getPublicUrl()` 
- `iris->video->createBrandedClip($params)` (Pending platform parameterization)

## 5. Timeline & Handoff
- **Platform Dev:** Estimated 5-7 days for clipping engine updates.
- **Integration Dev:** Estimated 3-5 days for Program/Form UI refinements.
- **MVP Launch:** Target January 20th for Peso's first outreach campaign.

**Point of Contact:** Client Success Team (IRIS)
