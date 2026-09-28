<?php
/**
 * Facility Usage Report
 * ICOEP - Integrated Campus Operations and Engagement Portal
 */

require_once __DIR__ . '/../../includes/auth_check.php';
requireLogin();
requirePermission('report.view');

$pageTitle = 'Facility Usage Report';
$db = getDB();

$month = $_GET['month'] ?? date('m');
$year = $_GET['year'] ?? date('Y');

try {
    // Basic stats
    $stmt = $db->prepare("
        SELECT f.name, COUNT(r.id) as total_bookings, 
               SUM(CASE WHEN r.status = 'approved' THEN 1 ELSE 0 END) as approved_bookings
        FROM facilities f
        LEFT JOIN facility_reservations r ON f.id = r.facility_id 
             AND MONTH(r.reservation_date) = :m AND YEAR(r.reservation_date) = :y
        GROUP BY f.id
        ORDER BY total_bookings DESC
    ");
    $stmt->execute([':m' => $month, ':y' => $year]);
    $usageData = $stmt->fetchAll();
    
} catch (PDOException $e) {
    $usageData = [];
    setFlash('error', 'Error generating report.');
}

$breadcrumb = [
    ['label' => 'Dashboard', 'url' => '/modules/dashboard/index.php'],
    ['label' => 'Reports'],
    ['label' => 'Facility Usage']
];

include __DIR__ . '/../../includes/header.php';
?>

<div class="page-header animate-fade-in-up">
    <div>
        <h1 class="page-title">Facility Usage Report</h1>
        <p class="page-subtitle">Analyze reservation trends across campus facilities.</p>
    </div>
    <div class="btn-group">
        <button class="btn btn-secondary" onclick="window.print()">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="6 9 6 2 18 2 18 9"></polyline><path d="M6 18H4a2 2 0 0 1-2-2v-5a2 2 0 0 1 2-2h16a2 2 0 0 1 2 2v5a2 2 0 0 1-2 2h-2"></path><rect x="6" y="14" width="12" height="8"></rect></svg>
            Print Report
        </button>
    </div>
</div>

<div class="card animate-fade-in-up" style="animation-delay: 100ms;">
    <div class="card-body">
        <form method="GET" action="" class="search-bar" style="margin-bottom: var(--spacing-lg);">
            <div class="filter-group no-print">
                <select name="month" class="form-control" style="width: auto;">
                    <?php for($m=1; $m<=12; $m++): ?>
                        <option value="<?php echo sprintf("%02d", $m); ?>" <?php echo $month == sprintf("%02d", $m) ? 'selected' : ''; ?>>
                            <?php echo date('F', mktime(0, 0, 0, $m, 10)); ?>
                        </option>
                    <?php endfor; ?>
                </select>
                <select name="year" class="form-control" style="width: auto;">
                    <?php for($y=date('Y')-2; $y<=date('Y')+1; $y++): ?>
                        <option value="<?php echo $y; ?>" <?php echo $year == $y ? 'selected' : ''; ?>><?php echo $y; ?></option>
                    <?php endfor; ?>
                </select>
                <button type="submit" class="btn btn-primary">Generate</button>
            </div>
        </form>
        
        <h3 class="card-title text-center" style="margin-bottom: var(--spacing-lg);">Usage for <?php echo date('F Y', mktime(0, 0, 0, $month, 10, $year)); ?></h3>
        
        <div class="table-responsive">
            <table class="table">
                <thead>
                    <tr>
                        <th>Facility Name</th>
                        <th>Total Bookings (All Statuses)</th>
                        <th>Approved Bookings</th>
                        <th>Utilization Indicator</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($usageData)): ?>
                        <tr><td colspan="4" class="text-center text-muted">No data available for this period.</td></tr>
                    <?php else: ?>
                        <?php 
                        $maxBookings = max(array_column($usageData, 'total_bookings'));
                        if ($maxBookings == 0) $maxBookings = 1; // Prevent div by zero
                        foreach ($usageData as $row): 
                            $pct = ($row['total_bookings'] / $maxBookings) * 100;
                        ?>
                            <tr>
                                <td style="font-weight: 500;"><?php echo sanitize($row['name']); ?></td>
                                <td><?php echo $row['total_bookings']; ?></td>
                                <td><?php echo $row['approved_bookings']; ?></td>
                                <td>
                                    <div style="width: 100%; height: 8px; background: var(--color-border); border-radius: 4px; overflow: hidden; margin-top: 6px;">
                                        <div style="height: 100%; width: <?php echo $pct; ?>%; background: var(--color-accent-primary);"></div>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<style>
@media print {
    .no-print, .sidebar, .page-header .btn-group { display: none !important; }
    .main-content { margin-left: 0 !important; padding: 0 !important; }
    .card { box-shadow: none; border: 1px solid #ccc; }
}
</style>

<?php include __DIR__ . '/../../includes/footer.php'; ?>
