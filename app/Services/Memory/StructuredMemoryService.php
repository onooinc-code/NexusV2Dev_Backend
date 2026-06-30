<?php

namespace App\Services\Memory;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;

class StructuredMemoryService
{
    /**
     * Store a structured fact or relationship
     *
     * @param int $contactId
     * @param string $factType
     * @param mixed $data
     * @param array $metadata
     * @return bool
     */
    public function store(int $contactId, string $factType, $data, array $metadata = []): bool
    {
        try {
            // Validate contact exists (optional, but good practice)
            // $contact = Contact::find($contactId);
            // if (!$contact) {
            //     Log::warning('StructuredMemoryService::store - Contact not found', [
            //         'contactId' => $contactId
            //     ]);
            //     return false;
            // }

            // Store in structured_memories table
            DB::table('structured_memories')->insert([
                'contact_id' => $contactId,
                'fact_type' => $factType,
                'data' => is_array($data) ? json_encode($data) : $data,
                'metadata' => json_encode($metadata),
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            return true;
        } catch (\Exception $e) {
            Log::error('StructuredMemoryService::store failed', [
                'contactId' => $contactId,
                'factType' => $factType,
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }

    /**
     * Retrieve structured memories for a contact
     *
     * @param int $contactId
     * @param string|null $factType
     * @param int $limit
     * @param int $offset
     * @return array
     */
    public function retrieve(int $contactId, string $factType = null, int $limit = 50, int $offset = 0): array
    {
        try {
            $query = DB::table('structured_memories')
                ->where('contact_id', $contactId);

            if ($factType) {
                $query->where('fact_type', $factType);
            }

            $results = $query->offset($offset)
                ->limit($limit)
                ->orderBy('created_at', 'desc')
                ->get();

            // Decode JSON data and metadata
            $memories = [];
            foreach ($results as $row) {
                $memories[] = [
                    'id' => $row->id,
                    'fact_type' => $row->fact_type,
                    'data' => json_decode($row->data, true),
                    'metadata' => json_decode($row->metadata, true),
                    'created_at' => $row->created_at,
                    'updated_at' => $row->updated_at,
                ];
            }

            return $memories;
        } catch (\Exception $e) {
            Log::error('StructuredMemoryService::retrieve failed', [
                'contactId' => $contactId,
                'factType' => $factType,
                'error' => $e->getMessage()
            ]);
            return [];
        }
    }

    /**
     * Update a structured memory
     *
     * @param int $id
     * @param string|null $factType
     * @param mixed $data
     * @param array $metadata
     * @return bool
     */
    public function update(int $id, string $factType = null, $data = null, array $metadata = []): bool
    {
        try {
            $updateData = [
                'updated_at' => now(),
            ];

            if ($factType !== null) {
                $updateData['fact_type'] = $factType;
            }

            if ($data !== null) {
                $updateData['data'] = is_array($data) ? json_encode($data) : $data;
            }

            if (!empty($metadata)) {
                $updateData['metadata'] = json_encode($metadata);
            }

            $affected = DB::table('structured_memories')
                ->where('id', $id)
                ->update($updateData);

            return $affected > 0;
        } catch (\Exception $e) {
            Log::error('StructuredMemoryService::update failed', [
                'id' => $id,
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }

    /**
     * Delete a structured memory
     *
     * @param int $id
     * @return bool
     */
    public function delete(int $id): bool
    {
        try {
            $affected = DB::table('structured_memories')
                ->where('id', $id)
                ->delete();

            return $affected > 0;
        } catch (\Exception $e) {
            Log::error('StructuredMemoryService::delete failed', [
                'id' => $id,
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }

    /**
     * Search structured memories by content
     *
     * @param int $contactId
     * @param string $searchTerm
     * @param int $limit
     * @return array
     */
    public function search(int $contactId, string $searchTerm, int $limit = 50): array
    {
        try {
            // Simple search in data field (for more advanced search, consider using full-text search)
            $results = DB::table('structured_memories')
                ->where('contact_id', $contactId)
                ->where('data', 'like', "%{$searchTerm}%")
                ->orWhere('fact_type', 'like', "%{$searchTerm}%")
                ->limit($limit)
                ->orderBy('created_at', 'desc')
                ->get();

            $memories = [];
            foreach ($results as $row) {
                $memories[] = [
                    'id' => $row->id,
                    'fact_type' => $row->fact_type,
                    'data' => json_decode($row->data, true),
                    'metadata' => json_decode($row->metadata, true),
                    'created_at' => $row->created_at,
                    'updated_at' => $row->updated_at,
                ];
            }

            return $memories;
        } catch (\Exception $e) {
            Log::error('StructuredMemoryService::search failed', [
                'contactId' => $contactId,
                'searchTerm' => $searchTerm,
                'error' => $e->getMessage()
            ]);
            return [];
        }
    }

    /**
     * Paginate structured memories
     */
    public function paginate(int $contactId = null, int $perPage = 25, string $sort = 'confidence', bool $includeExpired = false): array
    {
        try {
            $query = DB::table('structured_memories')->whereNull('deleted_at');

            if ($contactId !== null) {
                $query->where('contact_id', $contactId);
            }

            if (!$includeExpired) {
                $query->where('status', '!=', 'expired');
            }

            $total = $query->count();
            
            $results = $query->orderBy($sort, 'desc')
                ->limit($perPage)
                ->get(); // Using simple get for now since paginate() is complex manually or requires DB::table(...)->paginate() which works in Laravel.
                
            // Let's actually use paginate if possible. Since we're using query builder:
            // $paginator = $query->paginate($perPage); 
            // We'll simulate paginator for brevity if paginate() isn't working on this older syntax, but query builder does support paginate() in Laravel.
            // Let's use it:
            $paginator = DB::table('structured_memories')
                ->whereNull('deleted_at');
                
            if ($contactId !== null) {
                $paginator->where('contact_id', $contactId);
            }
            if (!$includeExpired) {
                $paginator->where('status', '!=', 'expired');
            }
            
            $paginated = $paginator->orderBy($sort, 'desc')->paginate($perPage);

            $data = [];
            foreach ($paginated->items() as $row) {
                $data[] = [
                    'id' => $row->id,
                    'fact_type' => $row->fact_type,
                    'data' => json_decode($row->data, true),
                    'metadata' => json_decode($row->metadata, true),
                    'confidence' => $row->confidence,
                    'status' => $row->status,
                    'created_at' => $row->created_at,
                    'updated_at' => $row->updated_at,
                ];
            }

            return [
                'data' => $data,
                'current_page' => $paginated->currentPage(),
                'total' => $paginated->total(),
                'per_page' => $paginated->perPage(),
                'last_page' => $paginated->lastPage(),
            ];
        } catch (\Exception $e) {
            Log::error('StructuredMemoryService::paginate failed', ['error' => $e->getMessage()]);
            return [
                'data' => [],
                'current_page' => 1,
                'total' => 0,
                'per_page' => $perPage,
                'last_page' => 1,
            ];
        }
    }

    /**
     * Reinforce confidence of a structured memory
     */
    public function reinforceConfidence(int $id): void
    {
        DB::transaction(function () use ($id) {
            $record = DB::table('structured_memories')->where('id', $id)->lockForUpdate()->first();
            if (!$record) return;

            $newConfidence = min(1.00, round($record->confidence + 0.05, 2));
            DB::table('structured_memories')->where('id', $id)->update([
                'confidence'         => $newConfidence,
                'last_reinforced_at' => now(),
                'status'             => $newConfidence >= 0.20 ? 'active' : $record->status,
                'updated_at'         => now(),
            ]);

            $this->recordVersion($id, $record->confidence, $newConfidence, 'reinforcement');
        });
    }

    /**
     * Apply time-decay to structured memories
     */
    public function applyDecay(int $daysThreshold = 30, float $decayAmount = 0.05): int
    {
        $cutoff = now()->subDays($daysThreshold);
        $affected = 0;

        DB::table('structured_memories')
            ->where('status', 'active')
            ->whereNull('deleted_at')
            ->where(fn($q) => $q->whereNull('last_reinforced_at')->orWhere('last_reinforced_at', '<', $cutoff))
            ->orderBy('id')
            ->chunk(200, function ($records) use ($decayAmount, &$affected) {
                foreach ($records as $record) {
                    $newConf = max(0.00, round($record->confidence - $decayAmount, 2));
                    $status = match(true) {
                        $newConf < 0.05 => 'expired',
                        $newConf < 0.20 => 'low_confidence',
                        default         => 'active',
                    };
                    DB::table('structured_memories')->where('id', $record->id)->update([
                        'confidence' => $newConf,
                        'status'     => $status,
                        'updated_at' => now(),
                    ]);
                    $this->recordVersion($record->id, $record->confidence, $newConf, 'decay');
                    $affected++;
                }
            });

        return $affected;
    }

    /**
     * Record a memory version
     */
    public function recordVersion(int $memoryId, ?float $oldConf, ?float $newConf, string $source, ?array $previousContent = null, ?array $newContent = null): void
    {
        $record = DB::table('structured_memories')->where('id', $memoryId)->first();
        if (!$record) return;

        $lastVersion = DB::table('contact_memory_versions')
            ->where('memory_id', $memoryId)
            ->where('memory_type', 'structured')
            ->orderBy('version', 'desc')
            ->first();

        $versionNumber = $lastVersion ? $lastVersion->version + 1 : 1;

        $diff = null;
        if ($previousContent && $newContent) {
            // Very simple diff calculation
            $diff = array_diff_assoc($newContent, $previousContent);
        }

        DB::table('contact_memory_versions')->insert([
            'memory_id' => $memoryId,
            'memory_type' => 'structured',
            'contact_id' => $record->contact_id,
            'version' => $versionNumber,
            'previous_content' => $previousContent ? json_encode($previousContent) : null,
            'new_content' => $newContent ? json_encode($newContent) : null,
            'diff' => $diff ? json_encode($diff) : null,
            'old_confidence' => $oldConf,
            'new_confidence' => $newConf,
            'source' => $source,
            'actor_id' => auth()->id() ?? null,
            'created_at' => now(),
        ]);
    }
}