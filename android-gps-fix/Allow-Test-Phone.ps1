$ErrorActionPreference = 'Stop'
$name = 'TG1-GPS-Test-Phone-9000'
if (Get-NetFirewallRule -Name $name -ErrorAction SilentlyContinue) {
    Write-Output 'Rule already exists; inspect its addresses before changing it.'
    exit 1
}
New-NetFirewallRule -Name $name -DisplayName 'TG1 GPS TLS - test phone only' -Direction Inbound -Action Allow -Protocol TCP -LocalAddress 192.168.1.60 -LocalPort 9000 -RemoteAddress 192.168.1.61 -Profile Any | Out-Null
exit 0
