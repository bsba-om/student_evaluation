<?php
require_once '../../data/config.php';
require_once '../../data/session_security.php';
check_auth('admin', '../login.php');

$error_message = '';
$db_info = [];
$table_stats = [];
$system_info = [];
$critical_alerts = [];
$performance_metrics = [];
$health_checks = [];

// Get database information
if ($pdo) {
    try {
        // Database server info
        $stmt = $pdo->query("SELECT VERSION() as version");
        $db_info['version'] = $stmt->fetchColumn();
        
        $stmt = $pdo->query("SELECT @@hostname as hostname, @@port as port, @@datadir as datadir, @@version_comment as server_type");
        $db_info['server'] = $stmt->fetch(PDO::FETCH_ASSOC);
        
        // Get database size from information_schema (more accurate)
        try {
            $stmt = $pdo->query("
                SELECT 
                    SUM(data_length) as data_size,
                    SUM(index_length) as index_size,
                    SUM(data_length + index_length) as total_size
                FROM information_schema.TABLES 
                WHERE table_schema = DATABASE()
            ");
            $db_sizes = $stmt->fetch(PDO::FETCH_ASSOC);
            $db_info['data_size'] = (int)($db_sizes['data_size'] ?? 0);
            $db_info['index_size'] = (int)($db_sizes['index_size'] ?? 0);
            $db_info['total_size'] = (int)($db_sizes['total_size'] ?? 0);
        } catch (PDOException $e) {
            $db_info['total_size'] = 0;
        }
        
        // Get all tables with detailed stats
        $stmt = $pdo->query("SHOW TABLES");
        $tables = $stmt->fetchAll(PDO::FETCH_COLUMN);
        
        foreach ($tables as $table) {
            try {
                // Get row count
                $stmt = $pdo->query("SELECT COUNT(*) as count FROM `$table`");
                $count = $stmt->fetchColumn();
                
                // Get table size from information_schema
                try {
                    $stmt = $pdo->query("
                        SELECT 
                            data_length,
                            index_length,
                            data_free,
                            update_time
                        FROM information_schema.TABLES 
                        WHERE table_schema = DATABASE() AND table_name = '$table'
                    ");
                    $status = $stmt->fetch(PDO::FETCH_ASSOC);
                    $data_length = $status['data_length'] ?? 0;
                    $index_length = $status['index_length'] ?? 0;
                    $data_free = $status['data_free'] ?? 0;
                    $update_time = $status['update_time'] ?? null;
                } catch (PDOException $e) {
                    $data_length = $index_length = $data_free = 0;
                    $update_time = null;
                }
                
                $total_size = $data_length + $index_length;
                $overhead = $data_free;
                $efficiency = $total_size > 0 ? (1 - ($overhead / $total_size)) * 100 : 100;
                
                // Check for potential issues
                $issues = [];
                if ($overhead > $total_size * 0.1) { // More than 10% overhead
                    $issues[] = 'High fragmentation';
                }
                if ($count == 0) {
                    $issues[] = 'Empty table';
                }
                
                $table_stats[] = [
                    'name' => $table,
                    'rows' => (int)$count,
                    'size' => $total_size,
                    'data_size' => $data_length,
                    'index_size' => $index_length,
                    'overhead' => $overhead,
                    'efficiency' => $efficiency,
                    'size_formatted' => formatBytes($total_size),
                    'data_formatted' => formatBytes($data_length),
                    'index_formatted' => formatBytes($index_length),
                    'last_update' => $update_time,
                    'issues' => $issues
                ];
            } catch (PDOException $e) {
                // Skip table if error
            }
        }
        
        // Sort by size descending
        usort($table_stats, function($a, $b) {
            return $b['size'] - $a['size'];
        });
        
        // Calculate total tables and records
        $total_tables = count($table_stats);
        $total_records = array_sum(array_column($table_stats, 'rows'));
        
        // Check for critical conditions
        try {
            $stmt = $pdo->query("SELECT COUNT(*) as count FROM pending_instructors WHERE status = 'pending'");
            $pending_count = (int)$stmt->fetchColumn();
            if ($pending_count > 20) {
                $critical_alerts[] = [
                    'type' => 'critical',
                    'message' => "Critical: $pending_count pending instructor registrations",
                    'suggestion' => 'Process pending registrations immediately',
                    'priority' => 1
                ];
            } elseif ($pending_count > 10) {
                $critical_alerts[] = [
                    'type' => 'warning',
                    'message' => "High pending registrations: $pending_count",
                    'suggestion' => 'Review pending instructor applications',
                    'priority' => 2
                ];
            }
        } catch (PDOException $e) {}
        
        // Check instructor count
        try {
            $stmt = $pdo->query("SELECT COUNT(*) as count FROM instructors");
            $instructor_count = (int)$stmt->fetchColumn();
            if ($instructor_count == 0) {
                $critical_alerts[] = [
                    'type' => 'warning',
                    'message' => 'No instructors in the system',
                    'suggestion' => 'Add instructors to the system',
                    'priority' => 3
                ];
            }
        } catch (PDOException $e) {}
        
        // Check program head
        try {
            $stmt = $pdo->query("SELECT COUNT(*) as count FROM instructors i INNER JOIN admin_promotions ap ON i.id = ap.instructor_id WHERE ap.promoted_to = 'program_head' AND ap.status = 'active'");
            $ph_count = (int)$stmt->fetchColumn();
            if ($ph_count == 0) {
                $critical_alerts[] = [
                    'type' => 'info',
                    'message' => 'No Program Head assigned',
                    'suggestion' => 'Promote an instructor to Program Head',
                    'priority' => 4
                ];
            }
        } catch (PDOException $e) {}
        
        // Check table sizes for potential issues
        $large_tables = [];
        foreach ($table_stats as $table) {
            if ($table['size'] > 104857600) { // > 100MB
                $large_tables[] = $table['name'] . ' (' . $table['size_formatted'] . ')';
            }
            if ($table['efficiency'] < 90) {
                $critical_alerts[] = [
                    'type' => 'warning',
                    'message' => "Table {$table['name']} has low storage efficiency ({$table['efficiency']}%)",
                    'suggestion' => 'Consider optimizing table (OPTIMIZE TABLE)',
                    'priority' => 5
                ];
            }
        }
        
        if (count($large_tables) > 2) {
            $critical_alerts[] = [
                'type' => 'warning',
                'message' => count($large_tables) . ' tables exceed 100MB',
                'suggestion' => 'Consider archiving old data',
                'priority' => 6
            ];
        }
        
        // Performance metrics
        try {
            // Check slow queries (if available)
            $stmt = $pdo->query("SHOW VARIABLES LIKE 'slow_query_log'");
            $slow_log = $stmt->fetch(PDO::FETCH_ASSOC);
            $performance_metrics['slow_query_log'] = $slow_log['Value'] ?? 'OFF';
        } catch (PDOException $e) {}
        
        try {
            // Get connection count
            $stmt = $pdo->query("SHOW STATUS LIKE 'Threads_connected'");
            $connections = $stmt->fetch(PDO::FETCH_ASSOC);
            $performance_metrics['current_connections'] = (int)($connections['Value'] ?? 0);
        } catch (PDOException $e) {}
        
        try {
            // Get max connections
            $stmt = $pdo->query("SHOW VARIABLES LIKE 'max_connections'");
            $max_conn = $stmt->fetch(PDO::FETCH_ASSOC);
            $performance_metrics['max_connections'] = (int)($max_conn['Value'] ?? 151);
            $performance_metrics['connection_usage'] = $performance_metrics['max_connections'] > 0 ? 
                round(($performance_metrics['current_connections'] / $performance_metrics['max_connections']) * 100, 1) : 0;
        } catch (PDOException $e) {}
        
    } catch (PDOException $e) {
        $error_message = "Database error: " . $e->getMessage();
    }
} else {
    $error_message = "Database connection failed.";
}

// Get system information
$system_info = [
    'php_version' => PHP_VERSION,
    'server_software' => $_SERVER['SERVER_SOFTWARE'] ?? 'Unknown',
    'os' => PHP_OS_FAMILY,
    'server_name' => $_SERVER['SERVER_NAME'] ?? 'localhost',
    'document_root' => $_SERVER['DOCUMENT_ROOT'] ?? 'N/A',
    'memory_usage' => memory_get_usage(true),
    'memory_peak' => memory_get_peak_usage(true),
    'max_memory' => ini_get('memory_limit'),
    'execution_time' => ini_get('max_execution_time'),
    'upload_max' => ini_get('upload_max_filesize'),
    'post_max' => ini_get('post_max_size'),
    'max_input_vars' => ini_get('max_input_vars'),
    'error_reporting' => error_reporting(),
    'display_errors' => ini_get('display_errors') ? 'On' : 'Off',
    'timezone' => date_default_timezone_get(),
    'current_time' => date('Y-m-d H:i:s'),
    'uptime' => function_exists('shell_exec') ? trim(shell_exec('uptime')) : 'N/A'
];

// Calculate memory percentage
$mem_percent = 0;
if (strpos($system_info['max_memory'], 'G') !== false) {
    $max_bytes = (float)rtrim($system_info['max_memory'], 'G') * 1024 * 1024 * 1024;
} elseif (strpos($system_info['max_memory'], 'M') !== false) {
    $max_bytes = (float)rtrim($system_info['max_memory'], 'M') * 1024 * 1024;
} else {
    $max_bytes = (int)$system_info['max_memory'];
}
if ($max_bytes > 0) {
    $mem_percent = ($system_info['memory_usage'] / $max_bytes) * 100;
}

function formatBytes($bytes, $precision = 2) {
    if ($bytes == 0) return '0 B';
    $units = ['B', 'KB', 'MB', 'GB', 'TB'];
    $bytes = max($bytes, 0);
    $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
    $pow = min($pow, count($units) - 1);
    $bytes /= pow(1024, $pow);
    return round($bytes, $precision) . ' ' . $units[$pow];
}

function getSeverityClass($type) {
    switch($type) {
        case 'critical': return 'alert-error';
        case 'warning': return 'alert-warning';
        case 'info': return 'alert-info';
        default: return 'alert-success';
    }
}

function getSeverityIcon($type) {
    switch($type) {
        case 'critical': return 'fa-times-circle';
        case 'warning': return 'fa-exclamation-triangle';
        case 'info': return 'fa-info-circle';
        default: return 'fa-check-circle';
    }
}

function getSeverityBadge($type) {
    switch($type) {
        case 'critical': return '<span class="status-badge" style="background: rgba(239, 68, 68, 0.15); color: #dc2626;">Critical</span>';
        case 'warning': return '<span class="status-badge" style="background: rgba(245, 158, 11, 0.15); color: #f59e0b;">Warning</span>';
        case 'info': return '<span class="status-badge" style="background: rgba(59, 130, 246, 0.15); color: #2563eb;">Info</span>';
        default: return '<span class="status-badge" style="background: rgba(22, 163, 74, 0.15); color: #16a34a;">Healthy</span>';
    }
}
?>

<style>
@keyframes pulse {
    0% { transform: scale(0.95); opacity: 0.7; }
    50% { transform: scale(1.1); opacity: 0; }
    100% { transform: scale(0.95); opacity: 0; }
}

@keyframes spin {
    0% { transform: rotate(0deg); }
    100% { transform: rotate(360deg); }
}

@keyframes heartbeat {
    0%, 100% { transform: scale(1); }
    10% { transform: scale(1.1); }
    20% { transform: scale(1); }
    30% { transform: scale(1.1); }
    40% { transform: scale(1); }
}

.running-process {
    display: inline-block;
    width: 16px;
    height: 16px;
    border: 2px solid rgba(22, 163, 74, 0.3);
    border-top-color: #16a34a;
    border-radius: 50%;
    animation: spin 1s linear infinite;
    margin-left: 8px;
    vertical-align: middle;
}

.pulse-icon {
    position: relative;
    display: inline-block;
}

.pulse-icon::before {
    content: '';
    position: absolute;
    width: 100%;
    height: 100%;
    border-radius: 50%;
    background: rgba(22, 163, 74, 0.3);
    animation: pulse 2s infinite;
}

.table-issue {
    color: #f59e0b;
    font-size: 11px;
    margin-left: 8px;
}

.storage-bar {
    height: 20px;
    background: var(--border-light);
    border-radius: 10px;
    overflow: hidden;
    position: relative;
}

.storage-bar-fill {
    height: 100%;
    background: linear-gradient(90deg, #16a34a, #22c55e);
    border-radius: 10px;
    transition: width 0.5s ease;
    display: flex;
    align-items: center;
    justify-content: flex-end;
    padding-right: 8px;
    color: white;
    font-size: 11px;
    font-weight: 600;
}

.storage-bar-fill.warning {
    background: linear-gradient(90deg, #f59e0b, #fbbf24);
}

.storage-bar-fill.critical {
    background: linear-gradient(90deg, #dc2626, #ef4444);
}

.storage-bar-fill:not(.warning):not(.critical) {
    background: linear-gradient(90deg, #16a34a, #22c55e);
}
</style>

<div class="page-header">
    <div>
        <h1 class="page-title">System Health Monitor</h1>
        <p class="page-subtitle">Real-time database and backend status</p>
    </div>
</div>

<?php if ($error_message): ?>
<div class="alert alert-error">
    <i class="fas fa-exclamation-circle"></i>
    <span><?php echo htmlspecialchars($error_message); ?></span>
</div>
<?php endif; ?>

<!-- System Status Overview -->
<div class="card" style="margin-bottom: 24px; border-left: 4px solid <?php echo empty($critical_alerts) ? '#16a34a' : (($critical_alerts[0]['type'] == 'critical') ? '#dc2626' : '#f59e0b'); ?>;">
    <div class="card-header">
        <h3 class="card-title">
            <i class="fas fa-<?php echo empty($critical_alerts) ? 'check-circle' : 'exclamation-triangle'; ?>" style="color: <?php echo empty($critical_alerts) ? '#16a34a' : (($critical_alerts[0]['type'] == 'critical') ? '#dc2626' : '#f59e0b'); ?>;"></i>
            System Status
            <?php if (empty($critical_alerts)): ?>
            <span class="running-process"></span>
            <?php endif; ?>
        </h3>
        <div style="font-size: 12px; color: var(--light-text);">
            <?php echo htmlspecialchars($system_info['current_time']); ?>
        </div>
    </div>
    <div class="card-body">
        <div class="form-grid" style="grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); display: grid; gap: 16px;">
            <div style="background: var(--cream); padding: 20px; border-radius: 12px; text-align: center; border: 1px solid var(--border-light);">
                <div style="font-size: 28px; font-weight: 700; color: <?php echo empty($critical_alerts) ? '#16a34a' : '#f59e0b'; ?>; margin-bottom: 8px;">
                    <?php echo empty($critical_alerts) ? 'HEALTHY' : 'ATTENTION NEEDED'; ?>
                </div>
                <div style="font-size: 13px; color: var(--light-text);">Overall Status</div>
                <?php if (empty($critical_alerts)): ?>
                <div style="margin-top: 8px; font-size: 12px; color: #16a34a;">
                    <i class="fas fa-check-circle"></i> All systems operational
                </div>
                <?php else: ?>
                <div style="margin-top: 8px; font-size: 12px; color: #f59e0b;">
                    <i class="fas fa-exclamation-triangle"></i> <?php echo count($critical_alerts); ?> alert(s) require attention
                </div>
                <?php endif; ?>
            </div>
            
            <div style="background: var(--cream); padding: 20px; border-radius: 12px; text-align: center; border: 1px solid var(--border-light);">
                <div style="font-size: 28px; font-weight: 700; color: var(--gold); margin-bottom: 8px;">
                    <?php echo number_format($total_records ?? 0); ?>
                </div>
                <div style="font-size: 13px; color: var(--light-text);">Total Records</div>
                <div style="margin-top: 8px; font-size: 12px; color: var(--light-text);">
                    Across <?php echo $total_tables ?? 0; ?> tables
                </div>
            </div>
            
            <div style="background: var(--cream); padding: 20px; border-radius: 12px; text-align: center; border: 1px solid var(--border-light);">
                <div style="font-size: 28px; font-weight: 700; color: var(--purple); margin-bottom: 8px;">
                    <?php echo formatBytes($db_info['total_size'] ?? 0); ?>
                </div>
                <div style="font-size: 13px; color: var(--light-text);">Database Size</div>
                <div style="margin-top: 8px; font-size: 12px; color: var(--light-text);">
                    Data: <?php echo formatBytes($db_info['data_size'] ?? 0); ?> | Index: <?php echo formatBytes($db_info['index_size'] ?? 0); ?>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Alerts Panel -->
<?php if (!empty($critical_alerts)): ?>
<div class="card" style="margin-bottom: 24px;">
    <div class="card-header">
        <h3 class="card-title">
            <i class="fas fa-bell" style="color: #dc2626;"></i>
            Active Alerts (<?php echo count($critical_alerts); ?>)
        </h3>
    </div>
    <div class="card-body">
        <?php 
        // Sort alerts by priority
        usort($critical_alerts, function($a, $b) {
            return ($a['priority'] ?? 99) - ($b['priority'] ?? 99);
        });
        ?>
        <?php foreach ($critical_alerts as $alert): ?>
        <div class="alert <?php echo getSeverityClass($alert['type']); ?>" style="margin-bottom: 16px; padding: 16px;">
            <div style="display: flex; align-items: flex-start; gap: 12px;">
                <i class="fas <?php echo getSeverityIcon($alert['type']); ?>" style="margin-top: 2px; color: <?php echo $alert['type'] == 'critical' ? '#dc2626' : ($alert['type'] == 'warning' ? '#f59e0b' : '#2563ef'); ?>;"></i>
                <div style="flex: 1;">
                    <div style="display: flex; align-items: center; gap: 8px; margin-bottom: 4px;">
                        <?php echo getSeverityBadge($alert['type']); ?>
                        <strong style="font-size: 14px;"><?php echo htmlspecialchars($alert['message']); ?></strong>
                    </div>
                    <?php if (isset($alert['suggestion'])): ?>
                    <div style="font-size: 13px; color: var(--light-text); padding: 8px 12px; background: rgba(0,0,0,0.03); border-radius: 6px; margin-top: 4px;">
                        <i class="fas fa-lightbulb" style="color: #f59e0b; margin-right: 6px;"></i>
                        <?php echo htmlspecialchars($alert['suggestion']); ?>
                    </div>
                    <?php endif; ?>
                </div>
                <div style="font-size: 11px; color: var(--light-text); white-space: nowrap;">
                    Priority <?php echo $alert['priority'] ?? 'N/A'; ?>
                </div>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
</div>
<?php endif; ?>

<!-- Database Storage Analysis -->
<div class="card" style="margin-bottom: 24px;">
    <div class="card-header">
        <h3 class="card-title">
            <i class="fas fa-chart-pie"></i>
            Database Storage Analysis
        </h3>
    </div>
    <div class="card-body">
        <div class="form-grid" style="grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); display: grid; gap: 20px; margin-bottom: 20px;">
            <div style="background: var(--cream); padding: 16px; border-radius: 12px;">
                <h4 style="font-size: 13px; font-weight: 600; color: var(--light-text); margin-bottom: 12px; text-transform: uppercase;">Storage Distribution</h4>
                <div style="display: flex; flex-direction: column; gap: 10px;">
                    <div>
                        <div style="display: flex; justify-content: space-between; font-size: 12px; margin-bottom: 4px;">
                            <span>Data</span>
                            <strong><?php echo formatBytes($db_info['data_size'] ?? 0); ?></strong>
                        </div>
                        <?php 
                        $data_percent = ($db_info['total_size'] ?? 0) > 0 ? (($db_info['data_size'] ?? 0) / ($db_info['total_size'] ?? 0)) * 100 : 0;
                        ?>
                        <div class="storage-bar">
                            <div class="storage-bar-fill" style="width: <?php echo $data_percent; ?>%;">
                                <?php echo round($data_percent, 1); ?>%
                            </div>
                        </div>
                    </div>
                    <div>
                        <div style="display: flex; justify-content: space-between; font-size: 12px; margin-bottom: 4px;">
                            <span>Indexes</span>
                            <strong><?php echo formatBytes($db_info['index_size'] ?? 0); ?></strong>
                        </div>
                        <?php 
                        $index_percent = ($db_info['total_size'] ?? 0) > 0 ? (($db_info['index_size'] ?? 0) / ($db_info['total_size'] ?? 0)) * 100 : 0;
                        ?>
                        <div class="storage-bar">
                            <div class="storage-bar-fill" style="width: <?php echo $index_percent; ?>%;">
                                <?php echo round($index_percent, 1); ?>%
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <div style="background: var(--cream); padding: 16px; border-radius: 12px;">
                <h4 style="font-size: 13px; font-weight: 600; color: var(--light-text); margin-bottom: 12px; text-transform: uppercase;">Server Info</h4>
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 8px; font-size: 12px;">
                    <div>MySQL Version:</div>
                    <div style="text-align: right; font-weight: 600;"><?php echo htmlspecialchars($db_info['version'] ?? 'N/A'); ?></div>
                    
                    <div>Server Type:</div>
                    <div style="text-align: right; font-weight: 600;"><?php echo htmlspecialchars($db_info['server']['server_type'] ?? 'N/A'); ?></div>
                    
                    <div>Host:</div>
                    <div style="text-align: right; font-weight: 600;"><?php echo htmlspecialchars($db_info['server']['hostname'] ?? 'localhost'); ?>:<?php echo htmlspecialchars($db_info['server']['port'] ?? '3306'); ?></div>
                    
                    <div>Slow Log:</div>
                    <div style="text-align: right; font-weight: 600; color: <?php echo ($performance_metrics['slow_query_log'] ?? 'OFF') == 'ON' ? '#f59e0b' : '#16a34a'; ?>;">
                        <?php echo $performance_metrics['slow_query_log'] ?? 'OFF'; ?>
                    </div>
                </div>
            </div>
        </div>
        
        <h4 style="font-size: 14px; margin-bottom: 16px; color: var(--dark-text);">Table Storage Details</h4>
        <?php if (empty($table_stats)): ?>
        <div class="empty-state">
            <i class="fas fa-table" style="font-size: 48px; opacity: 0.3;"></i>
            <h3>No Table Data</h3>
            <p>Could not retrieve table statistics.</p>
        </div>
        <?php else: ?>
        <div class="table-responsive">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Table</th>
                        <th>Records</th>
                        <th>Data Size</th>
                        <th>Index Size</th>
                        <th>Total</th>
                        <th>Efficiency</th>
                        <th>Last Update</th>
                        <th>Issues</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($table_stats as $table): 
                        $efficiency_class = $table['efficiency'] < 90 ? 'color: #f59e0b;' : '';
                        $size_class = $table['size'] > 104857600 ? 'color: #dc2626; font-weight: 600;' : '';
                        $row_class = !empty($table['issues']) ? 'background: rgba(245, 158, 11, 0.05);' : '';
                    ?>
                    <tr style="<?php echo $row_class; ?>">
                        <td><code style="background: var(--cream); padding: 4px 8px; border-radius: 6px; font-size: 12px; font-weight: 600;"><?php echo htmlspecialchars($table['name']); ?></code></td>
                        <td style="font-weight: 600;"><?php echo number_format($table['rows']); ?></td>
                        <td style="color: var(--light-text);"><?php echo $table['data_formatted']; ?></td>
                        <td style="color: var(--light-text);"><?php echo $table['index_formatted']; ?></td>
                        <td style="font-weight: 600; <?php echo $size_class; ?>"><?php echo $table['size_formatted']; ?></td>
                        <td style="<?php echo $efficiency_class; ?>">
                            <?php echo round($table['efficiency'], 1); ?>%
                            <?php if ($table['efficiency'] < 90): ?>
                            <i class="fas fa-exclamation-circle" style="font-size: 10px; margin-left: 4px;"></i>
                            <?php endif; ?>
                        </td>
                        <td style="font-size: 12px; color: var(--light-text);">
                            <?php 
                            if ($table['last_update']) {
                                echo date('M j, H:i', strtotime($table['last_update']));
                            } else {
                                echo '<span style="color: #9ca3af;">Never</span>';
                            }
                            ?>
                        </td>
                        <td>
                            <?php if (!empty($table['issues'])): ?>
                                <?php foreach ($table['issues'] as $issue): ?>
                                <span class="table-issue"><i class="fas fa-exclamation-circle"></i> <?php echo htmlspecialchars($issue); ?></span>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <span style="color: #16a34a; font-size: 12px;"><i class="fas fa-check"></i> OK</span>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php endif; ?>
    </div>
</div>

<!-- Performance Metrics -->
<div class="card" style="margin-bottom: 24px;">
    <div class="card-header">
        <h3 class="card-title">
            <i class="fas fa-tachometer-alt"></i>
            Performance Metrics
        </h3>
    </div>
    <div class="card-body">
        <div class="form-grid" style="grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); display: grid; gap: 16px;">
            <div style="background: var(--cream); padding: 16px; border-radius: 12px;">
                <h4 style="font-size: 13px; font-weight: 600; color: var(--light-text); margin-bottom: 12px;">Database Connections</h4>
                <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 12px;">
                    <span style="font-size: 24px; font-weight: 700; color: var(--dark-text);"><?php echo $performance_metrics['current_connections'] ?? 0; ?></span>
                    <span style="font-size: 12px; color: var(--light-text);">of <?php echo $performance_metrics['max_connections'] ?? 151; ?> max</span>
                </div>
                <?php 
                $conn_percent = $performance_metrics['connection_usage'] ?? 0;
                $conn_color = $conn_percent > 80 ? '#dc2626' : ($conn_percent > 60 ? '#f59e0b' : '#16a34a');
                ?>
                <div class="storage-bar">
                    <div class="storage-bar-fill" style="width: <?php echo $conn_percent; ?>%; background: <?php echo $conn_color; ?>;">
                        <?php echo round($conn_percent, 1); ?>%
                    </div>
                </div>
            </div>
            
            <div style="background: var(--cream); padding: 16px; border-radius: 12px;">
                <h4 style="font-size: 13px; font-weight: 600; color: var(--light-text); margin-bottom: 12px;">PHP Memory Usage</h4>
                <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 12px;">
                    <span style="font-size: 24px; font-weight: 700; color: var(--dark-text);"><?php echo formatBytes($system_info['memory_usage']); ?></span>
                    <span style="font-size: 12px; color: var(--light-text);">Limit: <?php echo $system_info['max_memory']; ?></span>
                </div>
                <?php 
                $mem_color = $mem_percent > 80 ? '#dc2626' : ($mem_percent > 60 ? '#f59e0b' : '#16a34a');
                ?>
                <div class="storage-bar">
                    <div class="storage-bar-fill" style="width: <?php echo $mem_percent; ?>%; background: <?php echo $mem_color; ?>;">
                        <?php echo round($mem_percent, 1); ?>%
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Tables Requiring Attention -->
<?php 
$tables_needing_attention = array_filter($table_stats, function($table) {
    return $table['efficiency'] < 90 || $table['size'] > 104857600 || !empty($table['issues']);
});
?>
<?php if (!empty($tables_needing_attention)): ?>
<div class="card" style="margin-bottom: 24px; border-left: 4px solid #f59e0b;">
    <div class="card-header">
        <h3 class="card-title">
            <i class="fas fa-tools" style="color: #f59e0b;"></i>
            Maintenance Required
        </h3>
    </div>
    <div class="card-body">
        <p style="font-size: 14px; color: var(--light-text); margin-bottom: 16px;">
            The following tables require maintenance attention to ensure optimal performance.
        </p>
        <div style="display: flex; flex-direction: column; gap: 12px;">
            <?php foreach ($tables_needing_attention as $table): ?>
            <div class="alert alert-warning" style="margin-bottom: 0;">
                <i class="fas fa-wrench" style="color: #f59e0b;"></i>
                <div style="flex: 1;">
                    <strong><?php echo htmlspecialchars($table['name']); ?></strong>
                    <div style="font-size: 13px; color: var(--light-text); margin-top: 4px;">
                        Size: <?php echo $table['size_formatted']; ?> | Efficiency: <?php echo round($table['efficiency'], 1); ?>%
                        <?php if (!empty($table['issues'])): ?>
                        <br><em><?php echo htmlspecialchars(implode(', ', $table['issues'])); ?></em>
                        <?php endif; ?>
                    </div>
                </div>
                <button onclick="runOptimize('<?php echo htmlspecialchars($table['name']); ?>')" class="btn btn-sm" style="background: rgba(245, 158, 11, 0.1); color: #f59e0b; border: 1px solid rgba(245, 158, 11, 0.3);">
                    <i class="fas fa-sync-alt"></i> Optimize
                </button>
            </div>
            <?php endforeach; ?>
        </div>
        <div style="margin-top: 16px; padding: 12px; background: rgba(245, 158, 11, 0.05); border-radius: 8px; font-size: 12px; color: var(--light-text);">
            <i class="fas fa-info-circle"></i> Optimizing tables improves performance and reclaims unused space. Schedule during low-traffic periods.
        </div>
    </div>
</div>
<?php endif; ?>

<!-- System Details -->
<div class="card">
    <div class="card-header">
        <h3 class="card-title">
            <i class="fas fa-info-circle"></i>
            System Configuration Details
        </h3>
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Setting</th>
                        <th>Value</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    $config_checks = [
                        ['PHP Version', $system_info['php_version'], 'info'],
                        ['Server', $system_info['server_software'], 'info'],
                        ['OS', $system_info['os'], 'info'],
                        ['Timezone', $system_info['timezone'], 'info'],
                        ['Memory Limit', $system_info['max_memory'], ($mem_percent > 80) ? 'warning' : 'healthy'],
                        ['Execution Time', $system_info['execution_time'] . 's', 'info'],
                        ['Upload Max', $system_info['upload_max'], 'info'],
                        ['Display Errors', $system_info['display_errors'], $system_info['display_errors'] == 'On' ? 'warning' : 'healthy'],
                        ['Error Reporting', $system_info['error_reporting'], 'info'],
                        ['DB Connection', $pdo ? 'Connected' : 'Failed', $pdo ? 'healthy' : 'critical'],
                        ['DB Version', $db_info['version'] ?? 'N/A', 'info'],
                        ['Slow Query Log', $performance_metrics['slow_query_log'] ?? 'OFF', ($performance_metrics['slow_query_log'] ?? 'OFF') == 'ON' ? 'warning' : 'info'],
                    ];
                    
                    foreach ($config_checks as $check):
                        $status_class = $check[2] == 'critical' ? 'alert-error' : ($check[2] == 'warning' ? 'alert-warning' : 'alert-success');
                        $badge = getSeverityBadge($check[2]);
                    ?>
                    <tr>
                        <td><strong><?php echo htmlspecialchars($check[0]); ?></strong></td>
                        <td><code style="background: var(--cream); padding: 2px 6px; border-radius: 4px; font-size: 12px;"><?php echo htmlspecialchars($check[1]); ?></code></td>
                        <td><?php echo $badge; ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<script>
// Auto-refresh every 60 seconds
setTimeout(function() {
    window.location.reload();
}, 60000);

function runOptimize(tableName) {
    if (confirm('Optimize table ' + tableName + '? This may temporarily lock the table.')) {
        fetch('../../data/admin_optimize_table.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: 'table=' + encodeURIComponent(tableName)
        })
        .then(r => r.json())
        .then(data => {
            if (data.success) {
                alert('Table optimized successfully. Refreshing...');
                location.reload();
            } else {
                alert('Error: ' + data.message);
            }
        })
        .catch(err => {
            alert('An error occurred. Please try again.');
            console.error(err);
        });
    }
}
</script>
