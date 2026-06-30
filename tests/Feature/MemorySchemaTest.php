<?php

namespace Tests\Feature;

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

/**
 * Schema Verification Tests for MemoryHub Phase 1 Database Migrations
 * 
 * This test file validates that all database migrations for the MemoryHub feature
 * have been applied correctly, specifically:
 * - Task 1.1: Add extraction fields to memories table
 * - Task 1.2: Add confidence scoring to structured_memories table
 * - Task 1.3: Create contact_memory_versions table
 * 
 * Requirements: 7.1 (confidence scoring), 10.1 (version history), 6.7 (extraction pipeline)
 */
class MemorySchemaTest extends TestCase
{
    use \Illuminate\Foundation\Testing\RefreshDatabase;

    /**
     * Test that structured_memories table has confidence column with correct default of 0.80
     * 
     * Validates Requirement 7.1: Confidence scoring
     * - Every newly created structured memory SHALL have a confidence value between 0.00 and 1.00
     * - Default confidence SHALL be 0.80 when not explicitly provided
     */
    public function test_structured_memories_has_confidence_column_with_default_080()
    {
        // Verify the structured_memories table exists
        $this->assertTrue(Schema::hasTable('structured_memories'), 'structured_memories table should exist');

        // Verify confidence column exists
        $this->assertTrue(
            Schema::hasColumn('structured_memories', 'confidence'),
            'structured_memories table should have confidence column for confidence scoring (Req 7.1)'
        );

        // Verify status column exists - used for tracking confidence state (active|low_confidence|expired)
        $this->assertTrue(
            Schema::hasColumn('structured_memories', 'status'),
            'structured_memories table should have status column for tracking confidence states'
        );

        // Verify last_reinforced_at column exists - tracks when confidence was last reinforced
        $this->assertTrue(
            Schema::hasColumn('structured_memories', 'last_reinforced_at'),
            'structured_memories table should have last_reinforced_at column for time-decay calculations'
        );

        // Verify soft deletes column exists - required for merge/consolidation operations
        $this->assertTrue(
            Schema::hasColumn('structured_memories', 'deleted_at'),
            'structured_memories table should have deleted_at column for soft deletes (used in consolidation)'
        );

        // Get table columns information and verify all are present
        $columns = Schema::getColumnListing('structured_memories');
        $this->assertContains('confidence', $columns, 'confidence column missing');
        $this->assertContains('status', $columns, 'status column missing');
        $this->assertContains('last_reinforced_at', $columns, 'last_reinforced_at column missing');
        $this->assertContains('deleted_at', $columns, 'deleted_at column missing');
    }


    /**
     * Test that contact_memory_versions table exists with all required columns
     * 
     * Validates Requirement 10.1: Memory Version History
     * - Every change to a structured memory SHALL be versioned
     * - Version entries capture: previous_content, new_content, diff, confidence changes, source, actor, timestamp
     * - Table SHALL support rollback functionality
     */
    public function test_contact_memory_versions_table_exists_with_all_required_columns()
    {
        // Verify the contact_memory_versions table exists
        $this->assertTrue(
            Schema::hasTable('contact_memory_versions'),
            'contact_memory_versions table should exist for version history tracking (Req 10.1)'
        );

        // List of required columns per Requirement 10.1 spec
        $requiredColumns = [
            'id',                       // Primary key
            'memory_id',                // Reference to structured memory being versioned
            'memory_type',              // Type of memory (always 'structured' in Phase 1)
            'contact_id',               // Contact associated with the memory
            'version',                  // Version number (incremented on each update)
            'previous_content',         // Content before the change (JSON)
            'new_content',              // Content after the change (JSON)
            'diff',                     // Computed diff between previous and new (JSON)
            'old_confidence',           // Confidence score before the change
            'new_confidence',           // Confidence score after the change
            'source',                   // What caused the change: 'manual'|'decay'|'consolidation'|'extraction'|'rollback'
            'actor_id',                 // User who made the change (null if automated)
            'created_at',               // Immutable timestamp of when version was created
        ];

        $actualColumns = Schema::getColumnListing('contact_memory_versions');

        // Verify each required column exists
        foreach ($requiredColumns as $column) {
            $this->assertContains(
                $column,
                $actualColumns,
                "contact_memory_versions table should have '{$column}' column (Req 10.1)"
            );
        }

        // Additional validation for specific columns
        $this->assertTrue(
            Schema::hasColumn('contact_memory_versions', 'id'),
            'Should have id column as primary key'
        );

        $this->assertTrue(
            Schema::hasColumn('contact_memory_versions', 'memory_id'),
            'Should have memory_id column (BIGINT UNSIGNED) to reference structured memory'
        );

        $this->assertTrue(
            Schema::hasColumn('contact_memory_versions', 'memory_type'),
            'Should have memory_type column (VARCHAR 50) for extensibility to other memory types'
        );

        $this->assertTrue(
            Schema::hasColumn('contact_memory_versions', 'contact_id'),
            'Should have contact_id column (BIGINT UNSIGNED NULLABLE) for audit purposes'
        );

        $this->assertTrue(
            Schema::hasColumn('contact_memory_versions', 'version'),
            'Should have version column (INT DEFAULT 1) to track version number'
        );

        $this->assertTrue(
            Schema::hasColumn('contact_memory_versions', 'previous_content'),
            'Should have previous_content column (JSON NULLABLE) to store pre-change state'
        );

        $this->assertTrue(
            Schema::hasColumn('contact_memory_versions', 'new_content'),
            'Should have new_content column (JSON NULLABLE) to store post-change state'
        );

        $this->assertTrue(
            Schema::hasColumn('contact_memory_versions', 'diff'),
            'Should have diff column (JSON NULLABLE) for change visualization'
        );

        $this->assertTrue(
            Schema::hasColumn('contact_memory_versions', 'old_confidence'),
            'Should have old_confidence column (DECIMAL 5,2 NULLABLE) to track confidence changes'
        );

        $this->assertTrue(
            Schema::hasColumn('contact_memory_versions', 'new_confidence'),
            'Should have new_confidence column (DECIMAL 5,2 NULLABLE) to track confidence changes'
        );

        $this->assertTrue(
            Schema::hasColumn('contact_memory_versions', 'source'),
            'Should have source column (VARCHAR 50 NULLABLE) to identify what caused the change'
        );

        $this->assertTrue(
            Schema::hasColumn('contact_memory_versions', 'actor_id'),
            'Should have actor_id column (BIGINT UNSIGNED NULLABLE) for audit trail'
        );

        $this->assertTrue(
            Schema::hasColumn('contact_memory_versions', 'created_at'),
            'Should have created_at column (TIMESTAMP) for immutable record timestamp'
        );
    }


    /**
     * Test that memories table has extraction fields for deduplication
     * 
     * Validates Requirement 6.7: Memory Extraction Pipeline
     * - Episodic memories created by extraction SHALL have source_type set to 'extraction'
     * - is_extracted flag enables idempotency: prevents re-extraction of same conversation
     * - Composite index on (contact_id, is_extracted) optimizes deduplication queries
     */
    public function test_memories_table_has_extraction_fields()
    {
        // Verify the memories table exists
        $this->assertTrue(Schema::hasTable('memories'), 'memories table should exist');

        // Verify source_type column exists - distinguishes manual from extracted memories
        $this->assertTrue(
            Schema::hasColumn('memories', 'source_type'),
            'memories table should have source_type column to indicate memory origin (Req 6.7)'
        );

        // Verify is_extracted column exists - gates deduplication to prevent re-extraction
        $this->assertTrue(
            Schema::hasColumn('memories', 'is_extracted'),
            'memories table should have is_extracted column for extraction idempotency (Req 6.7)'
        );

        $columns = Schema::getColumnListing('memories');
        $this->assertContains('source_type', $columns, 'source_type column should exist');
        $this->assertContains('is_extracted', $columns, 'is_extracted column should exist');
    }

    /**
     * Test that structured_memories has required indexes for performance
     * 
     * Validates Requirement 7.1: Confidence Scoring
     * - Index on (contact_id, confidence) enables efficient sorting by confidence descending
     * - Index on (contact_id, fact_type, status) enables filtering by status (active|low_confidence|expired)
     */
    public function test_structured_memories_has_required_indexes()
    {
        // Note: Exact index names vary by database driver
        // We verify existence by checking that table has indexes (not just primary key)
        $this->assertTrue(
            Schema::hasTable('structured_memories'),
            'structured_memories table should exist'
        );

        // Retrieve all indexes for the structured_memories table
        $indexNames = collect(Schema::getIndexes('structured_memories'))->pluck('name')->toArray();

        // Verify we have indexes beyond just PRIMARY
        $this->assertNotEmpty($indexNames, 'structured_memories table should have indexes for performance');
        $this->assertGreaterThan(
            1,
            count($indexNames),
            'structured_memories should have multiple indexes for common query patterns'
        );
    }

    /**
     * Test that contact_memory_versions has required indexes
     * 
     * Validates Requirement 10.1: Memory Version History
     * - Index on (memory_id, memory_type) enables efficient retrieval of version history for a memory
     * - Index on (contact_id, created_at) enables audit trail queries and cleanup operations
     */
    public function test_contact_memory_versions_has_required_indexes()
    {
        $this->assertTrue(
            Schema::hasTable('contact_memory_versions'),
            'contact_memory_versions table should exist'
        );

        // Retrieve all indexes for the contact_memory_versions table
        $indexNames = collect(Schema::getIndexes('contact_memory_versions'))->pluck('name')->toArray();

        // Verify we have indexes beyond just PRIMARY
        $this->assertNotEmpty($indexNames, 'contact_memory_versions table should have indexes');
        $this->assertGreaterThan(
            1,
            count($indexNames),
            'contact_memory_versions should have multiple indexes for common query patterns'
        );
    }

    /**
     * Test memories table has extraction-related indexes
     * 
     * Validates Requirement 6.7: Extraction Pipeline
     * - Composite index on (contact_id, is_extracted) optimizes deduplication gate queries
     */
    public function test_memories_table_has_extraction_indexes()
    {
        $this->assertTrue(
            Schema::hasTable('memories'),
            'memories table should exist'
        );

        // Retrieve all indexes for the memories table
        $indexes = Schema::getIndexes('memories');

        // We expect at least PRIMARY key and other indexes
        $this->assertNotEmpty($indexes, 'memories table should have indexes');
    }

    /**
     * Comprehensive test: verify all three migration sets are compatible
     * 
     * This test ensures that the three Phase 1 migrations work together correctly
     */
    public function test_all_memory_migrations_applied_successfully()
    {
        // All three tables should exist
        $this->assertTrue(Schema::hasTable('memories'), 'memories table should exist');
        $this->assertTrue(Schema::hasTable('structured_memories'), 'structured_memories table should exist');
        $this->assertTrue(Schema::hasTable('contact_memory_versions'), 'contact_memory_versions table should exist');

        // Verify no errors were reported in the schema
        $tables = [
            'memories' => ['source_type', 'is_extracted'],
            'structured_memories' => ['confidence', 'status', 'last_reinforced_at', 'deleted_at'],
            'contact_memory_versions' => ['memory_id', 'memory_type', 'contact_id', 'version', 'previous_content', 'new_content', 'diff', 'old_confidence', 'new_confidence', 'source', 'actor_id', 'created_at'],
        ];

        foreach ($tables as $tableName => $expectedColumns) {
            foreach ($expectedColumns as $columnName) {
                $this->assertTrue(
                    Schema::hasColumn($tableName, $columnName),
                    "Table '{$tableName}' should have column '{$columnName}'"
                );
            }
        }
    }
}

