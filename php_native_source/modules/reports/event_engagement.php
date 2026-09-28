<?php
/**
 * Event Engagement Report
 * ICOEP - Integrated Campus Operations and Engagement Portal
 */

require_once __DIR__ . '/../../includes/auth_check.php';
requireLogin();
requirePermission('report.view');

$pageTitle = 'Event Engagement Report';
$db = getDB();

$month = $_GET['month'] ?? date('m');
$year = $_GET['year'] ?? date('Y');

try {
    // Basic stats
    $stmt = $db->prepare("
        SELECT e.title, e.event_date, e.max_participants,
               (SELECT COUNT(*) FROM event_registrations WHERE event_id = e.id) as reg_count,
               (SELECT COUNT(*) FROM event_registrations WHERE event_id = e.id AND has_attended = 1) as attended_count
        FROM events e
        WHERE MONTH(e.event_date) = :m AND YEAR(e.event_date) = :y AND e.deleted_at IS NULL
        ORDER BY e.event_date ASC
    ");
    $stmt->execute([':m' => $month, ':y' => $year]);
    $eventsData = $stmt->fetchAll();
    
} catch (PDOException $e) {
    $eventsData = [];
    setFlash('error', 'Error generating report.');
}

$breadcrumb = [
    ['label' => 'Dashboard', 'url' => '/modules/dashboard/index.php'],
    ['label' => 'Reports'],
    ['label' => 'Event Engagement']
];

include __DIR__ . '/../../includes/header.php';
?>

<div class="page-header animate-fade-in-up">
    <div>
        <h1 class="page-title">Event Engagement Report</h1>
        <p class="page-subtitle">Track student and staff participation across campus events.</p>
    </div>
    <div class="btn-group">
        <button class="btn btn-secondary" onclick="window.print()">Print Report</button>
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
        
        <h3 class="card-title text-center" style="margin-bottom: var(--spacing-lg);">Engagement for <?php echo date('F Y', mktime(0, 0, 0, $month, 10, $year)); ?></h3>
        
        <div class="table-responsive">
            <table class="table">
                <thead>
                    <tr>
                        <th>Event Title</th>
                        <th>Date</th>
                        <th>Registered</th>
                        <th>Attended</th>
                        <th>Turnout %</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($eventsData)): ?>
                        <tr><td colspan="5" class="text-center text-muted">No events held in this period.</td></tr>
                    <?php else: ?>
                        <?php foreach ($eventsData as $row): 
                            $turnout = $row['reg_count'] > 0 ? round(($row['attended_count'] / $row['reg_count']) * 100, 1) : 0;
                        ?>
                            <tr>
                                <td style="font-weight: 500;"><?php echo sanitize($row['title']); ?></td>
                                <td><?php echo formatDate($row['event_date']); ?></td>
                                <td><?php echo $row['reg_count']; ?> <?php echo $row['max_participants'] ? '/ '.$row['max_participants'] : ''; ?></td>
                                <td><?php echo $row['attended_count']; ?></td>
                                <td>
                                    <span class="badge <?php echo $turnout >= 75 ? 'badge-success' : ($turnout >= 50 ? 'badge-warning' : 'badge-error'); ?>">
                                        <?php echo $turnout; ?>%
                                    </span>
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
