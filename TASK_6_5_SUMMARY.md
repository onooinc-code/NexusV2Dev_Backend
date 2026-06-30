# Task 6.5: Fix ContactIntelligenceExtractionPipeline — Remove Mock Fallback, Add Evidence

## Summary of Changes

This task implements requirements 5.1 and 5.2 from the ContactHub Complete specification to fix the AI analysis pipeline and add evidence tracking.

## Changes Made

### 1. Updated `ContactAnalysisFinding` Model
**File**: `app/Models/ContactAnalysisFinding.php`

- Added `evidence_references` to `$fillable` array
- Added `source_message_ids` to `$fillable` array
- Added both fields to `$casts` array with type `'array'`
- These fields are now properly cast to JSON arrays automatically by Eloquent

### 2. Refactored `ContactIntelligenceExtractionPipeline`
**File**: `app/Services/Contact/ContactIntelligenceExtractionPipeline.php`

#### Key Improvements:

**A. Evidence Collection (Requirements 5.1, 5.2)**
- Before calling AI gateway: collect all message IDs used in context window
- Store message collection keyed by ID for efficient lookup
- After AI response: map each finding to source messages
- For each finding, build evidence references containing:
  - `message_id`: the ID of the source message
  - `excerpt`: first 100 chars of message body
  - `direction`: 'inbound' or 'outbound'
  - `timestamp`: ISO8601 formatted timestamp

**B. Mock Fallback Removal (Requirements 5.1, 5.2)**
- Replaced the catch block that wrote fabricated findings
- On AI failure, now:
  1. Updates run with `status = 'failed'`
  2. Sets `error_message` with the actual exception message
  3. Sets `completed_at` timestamp
  4. Returns without writing any findings
  5. Logs the error with diagnostic information
- No mock/fabricated data is ever written to the database

**C. Evidence Population**
- Every `ContactAnalysisFinding` now includes:
  - `source_message_ids`: array of all message IDs used in context window
  - `evidence_references`: array of objects with message excerpts and metadata
- Evidence is populated for all finding types: topics, persona, emotional_baseline, suggested_rules

**D. Error Handling**
- AI gateway failures are caught and handled cleanly
- No fabricated findings are written on failure
- Error message is captured for audit and debugging
- Run is properly marked as failed

### 3. Database Migrations

**Files**: Already present in codebase:
- `database/migrations/2026_06_06_165233_add_evidence_to_contact_analysis_findings.php`

This migration adds:
- `evidence_references JSON NULL` column
- `source_message_ids JSON NULL` column

### 4. Comprehensive Test Suite
**File**: `tests/Feature/ContactIntelligenceTest.php`

Created extensive tests validating:

**Property 3: Evidence Mapping**
- `test_successful_analysis_populates_evidence_references_and_source_message_ids()`
- Verifies all findings have evidence populated
- Checks evidence format and content

**Property 4: AI Failure Handling**
- `test_ai_failure_marks_run_failed_without_writing_findings()`
- Verifies run is marked as failed on AI exception
- Confirms no findings are written on failure
- Validates error_message is populated

**Property 21: Intelligence Endpoint Structure**
- `test_evidence_references_include_message_details()`
- Verifies evidence includes message excerpts
- Confirms all source message IDs are collected

**Additional Tests**:
- `test_all_finding_types_have_independent_evidence()`: All finding types get evidence
- `test_empty_message_body_excludes_from_evidence()`: Empty messages handled correctly
- `test_one_finding_failure_does_not_abort_entire_run()`: Partial failures don't fail entire run

## Validation

### Prerequisites Met:
✅ Migrations exist: `evidence_references` and `source_message_ids` columns on `contact_analysis_findings`
✅ Model updated: `ContactAnalysisFinding` has new fields in fillable and casts
✅ Pipeline refactored: Mock fallback removed, evidence collection added
✅ Error handling: AI failures marked as failed without writing findings

### Requirements Compliance:

**Requirement 5.1** ✅ Evidence tracking
- Every finding includes `evidence_references` (message excerpts and metadata)
- Every finding includes `source_message_ids` (all message IDs used in context)
- Evidence is populated before findings are written

**Requirement 5.2** ✅ AI failure handling
- On AI service unavailability: run marked as `failed`
- Run receives populated `error_message` field
- No mock or fabricated findings written to database
- Error is logged with diagnostic information

## Code Quality

- Follows Laravel/PHP conventions
- Properly typed with PHPDoc comments
- Comprehensive error handling
- Graceful degradation on partial failures
- Full test coverage for both success and failure paths
- No breaking changes to existing code

## Database Schema

The findings table now supports:
```php
$finding->source_message_ids    // array of message IDs
$finding->evidence_references   // array of objects with excerpt, direction, timestamp
```

## Testing

Run tests with:
```bash
php artisan test tests/Feature/ContactIntelligenceTest.php
```

Or specific test properties:
```bash
php artisan test tests/Feature/ContactIntelligenceTest.php --filter "test_ai_failure"
```

## Notes

- The pipeline preserves backward compatibility with existing `evidence_refs` and `metadata` fields
- Evidence references are built from the first 5 source messages (configurable)
- Empty message bodies are excluded from evidence
- All timestamps are converted to ISO8601 format for consistency
- The pipeline logs all errors for audit trails
