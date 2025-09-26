<?php
// Set execution time limit to 15 seconds
set_time_limit(15);

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET');
header('Access-Control-Allow-Headers: Content-Type');

// Add cache headers to reduce server load
header('Cache-Control: no-cache, must-revalidate');
header('Expires: ' . gmdate('D, d M Y H:i:s', time() + 2) . ' GMT'); // Cache for 2 seconds

class SystemMonitor {

    public function getCpuUsage() {
        try {
            $load = sys_getloadavg();
            $cores = $this->getCpuCores();

            // Calculate CPU usage percentage (approximate)
            $usage = round(($load[0] / $cores) * 100, 1);
            $usage = min($usage, 100); // Cap at 100%

            return [
                'usage' => $usage,
                'cores' => $cores,
                'load' => implode(', ', array_map(function($l) { return round($l, 2); }, $load))
            ];
        } catch (Exception $e) {
            return ['usage' => 0, 'cores' => 1, 'load' => '0, 0, 0'];
        }
    }

    public function getMemoryUsage() {
        $meminfo = $this->parseMeminfo();

        $total = $meminfo['MemTotal'] ?? 0;
        $free = $meminfo['MemFree'] ?? 0;
        $available = $meminfo['MemAvailable'] ?? $free;
        $buffers = $meminfo['Buffers'] ?? 0;
        $cached = $meminfo['Cached'] ?? 0;

        $used = $total - $available;
        $usage = $total > 0 ? round(($used / $total) * 100, 1) : 0;

        return [
            'usage' => $usage,
            'used' => $used * 1024, // Convert to bytes
            'total' => $total * 1024,
            'free' => $available * 1024
        ];
    }

    public function getDiskUsage() {
        $rootPath = '/';
        $totalBytes = disk_total_space($rootPath);
        $freeBytes = disk_free_space($rootPath);
        $usedBytes = $totalBytes - $freeBytes;

        $usage = $totalBytes > 0 ? round(($usedBytes / $totalBytes) * 100, 1) : 0;

        return [
            'usage' => $usage,
            'used' => $usedBytes,
            'total' => $totalBytes,
            'free' => $freeBytes
        ];
    }

    public function getNetworkUsage() {
        static $lastRx = null;
        static $lastTx = null;
        static $lastTime = null;

        $currentTime = microtime(true);
        $stats = $this->getNetworkStats();

        $rx = $stats['rx'] ?? 0;
        $tx = $stats['tx'] ?? 0;

        $rxSpeed = 0;
        $txSpeed = 0;
        $totalSpeed = 0;

        if ($lastRx !== null && $lastTx !== null && $lastTime !== null) {
            $timeDiff = $currentTime - $lastTime;
            if ($timeDiff > 0) {
                $rxSpeed = max(0, ($rx - $lastRx) / $timeDiff);
                $txSpeed = max(0, ($tx - $lastTx) / $timeDiff);
                $totalSpeed = $rxSpeed + $txSpeed;
            }
        }

        $lastRx = $rx;
        $lastTx = $tx;
        $lastTime = $currentTime;

        return [
            'rx' => $rxSpeed,
            'tx' => $txSpeed,
            'total' => $totalSpeed
        ];
    }

    public function getSystemInfo() {
        $uptime = $this->getUptime();
        $load = sys_getloadavg();

        return [
            'hostname' => gethostname(),
            'os' => php_uname('s') . ' ' . php_uname('r'),
            'kernel' => php_uname('v'),
            'uptime' => $uptime,
            'load_avg' => implode(', ', array_map(function($l) { return round($l, 2); }, $load)),
            'processes' => $this->getProcessCount()
        ];
    }

    private function getCpuCores() {
        static $cores = null;

        if ($cores === null) {
            $cores = 1;
            try {
                if (is_readable('/proc/cpuinfo')) {
                    $cpuinfo = file_get_contents('/proc/cpuinfo');
                    $cores = substr_count($cpuinfo, 'processor');
                }
            } catch (Exception $e) {
                $cores = 1;
            }
            $cores = max($cores, 1);
        }

        return $cores;
    }

    private function parseMeminfo() {
        static $cache = null;
        static $lastUpdate = 0;

        // Cache for 1 second to reduce I/O
        if ($cache === null || (time() - $lastUpdate) > 1) {
            $meminfo = [];
            try {
                if (is_readable('/proc/meminfo')) {
                    $lines = file('/proc/meminfo', FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);

                    foreach ($lines as $line) {
                        if (preg_match('/^(\w+):\s*(\d+)\s*kB/', $line, $matches)) {
                            $meminfo[$matches[1]] = (int)$matches[2];
                        }
                    }
                }
            } catch (Exception $e) {
                $meminfo = ['MemTotal' => 0, 'MemFree' => 0, 'MemAvailable' => 0, 'Buffers' => 0, 'Cached' => 0];
            }
            $cache = $meminfo;
            $lastUpdate = time();
        }

        return $cache;
    }

    private function getNetworkStats() {
        $stats = ['rx' => 0, 'tx' => 0];

        if (is_readable('/proc/net/dev')) {
            $lines = file('/proc/net/dev', FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);

            foreach ($lines as $line) {
                if (preg_match('/^\s*(\w+):\s*(\d+)\s+\d+\s+\d+\s+\d+\s+\d+\s+\d+\s+\d+\s+\d+\s+(\d+)/', $line, $matches)) {
                    $interface = $matches[1];

                    // Skip loopback and virtual interfaces
                    if ($interface !== 'lo' && !preg_match('/^(docker|veth|br-)/', $interface)) {
                        $stats['rx'] += (int)$matches[2];
                        $stats['tx'] += (int)$matches[3];
                    }
                }
            }
        }

        return $stats;
    }

    private function getUptime() {
        $uptime = 0;

        if (is_readable('/proc/uptime')) {
            $uptimeData = file_get_contents('/proc/uptime');
            $uptime = (float)strtok($uptimeData, ' ');
        }

        return (int)$uptime;
    }

    private function getProcessCount() {
        static $cache = null;
        static $lastUpdate = 0;

        // Cache for 2 seconds to reduce directory scanning load
        if ($cache === null || (time() - $lastUpdate) > 2) {
            $count = 0;
            try {
                if (is_readable('/proc')) {
                    // Use a faster method with limited results to prevent timeout
                    $processes = glob('/proc/[0-9]*', GLOB_ONLYDIR);
                    $count = count($processes);
                }
            } catch (Exception $e) {
                $count = 0;
            }
            $cache = max($count, 0);
            $lastUpdate = time();
        }

        return $cache;
    }
}

try {
    $monitor = new SystemMonitor();

    $data = [
        'timestamp' => time(),
        'cpu' => $monitor->getCpuUsage(),
        'memory' => $monitor->getMemoryUsage(),
        'disk' => $monitor->getDiskUsage(),
        'network' => $monitor->getNetworkUsage(),
        'system' => $monitor->getSystemInfo()
    ];

    echo json_encode($data, JSON_PRETTY_PRINT);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'error' => 'Failed to fetch system stats',
        'message' => $e->getMessage()
    ]);
}
?>