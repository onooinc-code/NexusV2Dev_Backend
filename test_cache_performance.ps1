$uri = "http://127.0.0.1:8000/api/v1/admin/system/status"

Write-Host "Testing Redis Caching (3 requests):"
Write-Host "===================================="

for ($i = 1; $i -le 3; $i++) {
    Write-Host ""
    Write-Host "Request $($i):"

    $time = Measure-Command {
        $resp = Invoke-WebRequest -Uri $uri -UseBasicParsing -TimeoutSec 30 -ErrorAction Stop
        $json = $resp.Content | ConvertFrom-Json
        Write-Host "   API Status: $($json.services.api.status)"
        Write-Host "   Cached: $($json.cached)"
    }

    Write-Host "   Response Time: $($time.TotalMilliseconds) ms"
}

Write-Host ""
Write-Host "✅ Caching Performance Test Complete"
