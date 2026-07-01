# Admin Hub Data Flow

## 1. Overview
Data flow in the Admin Hub is primarily read-heavy (polling system metrics) with occasional write-heavy administrative commands (restarting services, retrying jobs). The flow is strictly unidirectional from the Controller down to the OS layer, with caching implemented at the Controller layer to protect the OS.

## 2. System Status Polling Data Flow

This diagram illustrates how the frontend retrieves the system metrics every 5 seconds without crashing the server.

```mermaid
sequenceDiagram
    participant Browser as Client (admin.blade.php)
    participant SC as SystemController
    participant Cache as Laravel Cache (Redis/File)
    participant PM as ProcessManager
    participant OS as Operating System

    loop Every 5 seconds (Auto-Refresh)
        Browser->>SC: GET /api/admin/system/status
        
        SC->>Cache: Check 'admin:system:status'
        
        alt Cache Hit
            Cache-->>SC: Return cached data
            SC-->>Browser: 200 OK (cached=true)
        else Cache Miss
            SC->>OS: memory_get_usage(), disk_free_space()
            OS-->>SC: Return raw metrics
            
            SC->>PM: getServicesStatus()
            PM->>OS: exec(tasklist / posix_kill)
            OS-->>PM: return PID status
            PM-->>SC: Return formatted service data
            
            SC->>Cache: Store payload for 30s
            SC-->>Browser: 200 OK (cached=false)
        end
    end
```

## 3. Background Build Execution Flow

When an administrator clicks "Trigger Build", the process must be offloaded to the background so the HTTP request doesn't timeout.

```mermaid
sequenceDiagram
    participant Admin as Admin User
    participant Browser as UI (admin.blade.php)
    participant SC as SystemController
    participant OS as Operating System Shell
    participant FS as File System (logs)

    Admin->>Browser: Clicks "Trigger Build"
    Browser->>SC: POST /api/admin/build {type: 'all'}
    
    SC->>SC: Verify Auth & Permissions
    SC->>FS: Ensure logs/ directory exists
    
    SC->>OS: shell_exec("bash build.sh > build.log &")
    Note right of SC: Appends '&' to detach process
    
    SC-->>Browser: 202 Accepted (Log path returned)
    Browser->>Admin: Shows "Build Started" toast
    
    loop Background Execution
        OS->>FS: Writes stdout/stderr to build.log
    end
```

## 4. Dead Letter Queue Data Flow

The flow for retrying a failed job involves the DLQ Controller and Laravel's Queue worker.

```mermaid
sequenceDiagram
    participant Admin as Admin User
    participant Browser as UI
    participant DC as DlqController
    participant DS as DeadLetterQueueService
    participant DB as failed_jobs Table
    participant Queue as Laravel Queue

    Admin->>Browser: Clicks "Retry" on Job #4921
    Browser->>DC: POST /api/admin/dlq/4921/retry
    
    DC->>DS: retry(4921)
    DS->>DB: Fetch job payload where id=4921
    DB-->>DS: Return serialized job data
    
    DS->>Queue: Push payload back to active queue
    Queue-->>DS: Confirmed
    
    DS->>DB: Delete record from failed_jobs
    DB-->>DS: Deleted
    
    DS-->>DC: Success boolean
    DC-->>Browser: 200 OK "Dispatched"
    Browser->>Admin: Updates UI, removes row
```
