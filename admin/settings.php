<?php
/**
 * Admin Panel - System & Database Settings
 */
$pageTitle = "System & Database — HealthJobsGlobal Admin";
$pageHeading = "System Health & Database Configuration";

require_once __DIR__ . '/includes/header.php';

// Handle Table Optimization, Re-seed, or Company Sender Email Update
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'update_company_sender_email') {
        $newSenderEmail = trim($_POST['company_sender_email'] ?? '');
        $newCompanyName = trim($_POST['company_name'] ?? '');
        if (!empty($newSenderEmail) && filter_var($newSenderEmail, FILTER_VALIDATE_EMAIL)) {
            setSystemSetting($con, 'company_sender_email', $newSenderEmail);
            if (!empty($newCompanyName)) {
                setSystemSetting($con, 'company_name', $newCompanyName);
            }
            header('Location: settings.php?success=' . urlencode("Company dispatch email successfully set to: {$newSenderEmail}"));
            exit;
        }
    }

    if ($action === 'reseed_data') {
        initAdminDatabase($con);
        header('Location: settings.php?success=' . urlencode('Database seed verification executed successfully!'));
        exit;
    }

    if ($action === 'optimize_tables') {
        if (!empty($con) && !$con->connect_error) {
            $tables = ['jobs', 'hospitals', 'candidates', 'applications', 'departments', 'admins'];
            foreach ($tables as $t) {
                $con->query("OPTIMIZE TABLE `{$t}`");
            }
        }
        header('Location: settings.php?success=' . urlencode('All MySQL tables optimized successfully!'));
        exit;
    }
}

// Fetch table statistics
$tableStats = [];
$totalRows = 0;
if (!empty($con) && !$con->connect_error) {
    $tables = ['jobs', 'hospitals', 'candidates', 'applications', 'departments', 'admins'];
    foreach ($tables as $tbl) {
        $r = $con->query("SELECT COUNT(*) as c FROM `{$tbl}`");
        $count = $r ? (int)$r->fetch_assoc()['c'] : 0;
        $totalRows += $count;
        $tableStats[$tbl] = $count;
    }
}

require_once __DIR__ . '/includes/topbar.php';
?>

<div style="display: grid; grid-template-columns: 1fr 1fr; gap: 24px;">
  <!-- Database Connection Card -->
  <div class="card">
    <div class="card-header">
      <h3 class="card-title">MySQL Database Connection</h3>
      <span class="badge badge-active">Active</span>
    </div>
    <div class="card-body">
      <div style="display: flex; flex-direction: column; gap: 14px; font-size: 0.88rem;">
        <div style="display: flex; justify-content: space-between; padding-bottom: 8px; border-bottom: 1px solid #F1F5F9;">
          <span style="color: var(--text-muted);">Host Server</span>
          <strong>localhost</strong>
        </div>
        <div style="display: flex; justify-content: space-between; padding-bottom: 8px; border-bottom: 1px solid #F1F5F9;">
          <span style="color: var(--text-muted);">Database Name</span>
          <strong><code><?php echo htmlspecialchars($dbname ?? 'healthjobs'); ?></code></strong>
        </div>
        <div style="display: flex; justify-content: space-between; padding-bottom: 8px; border-bottom: 1px solid #F1F5F9;">
          <span style="color: var(--text-muted);">Database User</span>
          <strong>root</strong>
        </div>
        <div style="display: flex; justify-content: space-between; padding-bottom: 8px; border-bottom: 1px solid #F1F5F9;">
          <span style="color: var(--text-muted);">Connection Status</span>
          <span style="color: #10B981; font-weight: 700; display: inline-flex; align-items: center; gap: 6px;">
            <span class="db-dot"></span> Connected
          </span>
        </div>
        <div style="display: flex; justify-content: space-between; padding-bottom: 8px; border-bottom: 1px solid #F1F5F9;">
          <span style="color: var(--text-muted);">Character Set</span>
          <strong>utf8 / utf8mb4</strong>
        </div>
        <div style="display: flex; justify-content: space-between;">
          <span style="color: var(--text-muted);">Server Version</span>
          <strong><?php echo (!empty($con) && !$con->connect_error) ? $con->server_info : 'N/A'; ?></strong>
        </div>
      </div>

      <div style="margin-top: 24px; padding-top: 20px; border-top: 1px solid var(--border-light); display: flex; gap: 12px;">
        <form method="POST" action="settings.php">
          <input type="hidden" name="action" value="optimize_tables">
          <button type="submit" class="btn btn-secondary btn-sm">
            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 2v20M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"/></svg>
            Optimize MySQL Tables
          </button>
        </form>
        <form method="POST" action="settings.php" onsubmit="return confirm('Re-verify and populate initial seed data?');">
          <input type="hidden" name="action" value="reseed_data">
          <button type="submit" class="btn btn-primary btn-sm">
            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 12a9 9 0 0 0-9-9 9.75 9.75 0 0 0-6.74 2.74L3 8"/><path d="M3 3v5h5"/><path d="M3 12a9 9 0 0 0 9 9 9.75 9.75 0 0 0 6.74-2.74L21 16"/><path d="M16 21h5v-5"/></svg>
            Verify Schema & Tables
          </button>
        </form>
      </div>
    </div>
  </div>

  <!-- Configured Connection Code & Table Counts -->
  <div class="card">
    <div class="card-header">
      <h3 class="card-title">Configured Connection Snippet</h3>
      <span style="font-size: 0.8rem; color: var(--text-muted);">File: <code>includes/db.php</code></span>
    </div>
    <div class="card-body">
      <div style="background: #0B132B; color: #E2E8F0; padding: 16px; border-radius: var(--radius-md); font-family: monospace; font-size: 0.84rem; line-height: 1.5; overflow-x: auto; margin-bottom: 20px;">
<span style="color: #64748B;">//local server</span>

<span style="color: #38BDF8;">$servername</span> = <span style="color: #34D399;">"localhost"</span>;
<span style="color: #38BDF8;">$username</span> = <span style="color: #34D399;">"root"</span>;
<span style="color: #38BDF8;">$password</span> = <span style="color: #34D399;">""</span>;
<span style="color: #38BDF8;">$dbname</span> = <span style="color: #34D399;">"healthjobs"</span>;


<span style="color: #64748B;">// Create connection</span>
<span style="color: #38BDF8;">$con</span> = <span style="color: #F472B6;">new</span> <span style="color: #FBBF24;">mysqli</span>(<span style="color: #38BDF8;">$servername</span>, <span style="color: #38BDF8;">$username</span>, <span style="color: #38BDF8;">$password</span>, <span style="color: #38BDF8;">$dbname</span>);
<span style="color: #38BDF8;">$con</span>-><span style="color: #FBBF24;">set_charset</span>(<span style="color: #34D399;">"utf8"</span>);
      </div>

      <h4 style="font-size: 0.92rem; margin-bottom: 12px;">Active Database Tables & Record Counts:</h4>
      <div style="display: grid; grid-template-columns: repeat(3, 1fr); gap: 10px;">
        <?php foreach ($tableStats as $tbl => $cnt): ?>
          <div style="padding: 10px; background: #F8FAFC; border: 1px solid var(--border-light); border-radius: var(--radius-md); text-align: center;">
            <div style="font-size: 0.76rem; color: var(--text-muted); text-transform: uppercase;"><code><?php echo $tbl; ?></code></div>
            <div style="font-size: 1.25rem; font-weight: 700; color: var(--primary); margin-top: 2px;"><?php echo $cnt; ?></div>
          </div>
        <?php endforeach; ?>
      </div>
    </div>
  </div>
</div>

<!-- Central Outgoing Company Email Gateway Card -->
<?php
$curCompanyEmail = getCompanySenderEmail($con);
$curCompanyName = getCompanyDisplayName($con);
?>
<div class="card" style="margin-top: 24px;">
  <div class="card-header" style="background: linear-gradient(135deg, #F0F9FF, #E0F2FE); border-bottom: 1px solid #BAE6FD;">
    <div style="display: flex; align-items: center; gap: 10px;">
      <span style="font-size: 1.3rem;">✉️</span>
      <div>
        <h3 class="card-title" style="color: #0369A1;">Central Outgoing Company Email Dispatch Gateway</h3>
        <p style="font-size: 0.8rem; color: #0284C7; margin: 0;">Configure the primary company email ID used for sending employer activation passwords and rejection advisories</p>
      </div>
    </div>
    <span class="badge badge-active" style="background: #0284C7;">Active Gateway</span>
  </div>
  <div class="card-body">
    <form method="POST" action="settings.php" style="max-width: 680px;">
      <input type="hidden" name="action" value="update_company_sender_email">
      
      <div style="background: #F8FAFC; border: 1px solid #E2E8F0; border-radius: var(--radius-md); padding: 16px 20px; margin-bottom: 20px;">
        <div style="font-size: 0.85rem; color: #334155; line-height: 1.5;">
          When administrators verify an employer or send a non-approval explanation notice, all outgoing emails will originate from the email address specified below as the official <strong>From:</strong> and <strong>Reply-To:</strong> headers.
        </div>
      </div>

      <div class="form-group" style="margin-bottom: 16px;">
        <label class="form-label" style="font-weight: 700;">Company Common Email Address *</label>
        <input type="email" name="company_sender_email" class="form-control" value="<?php echo htmlspecialchars($curCompanyEmail); ?>" placeholder="e.g. careers@healthjobsglobal.com or admin@yourhospital.com" required style="font-size: 0.95rem; font-weight: 700; color: var(--primary);">
        <small style="color: var(--text-muted); display: block; margin-top: 4px;">Recipients will see this email address and can reply directly to it.</small>
      </div>

      <div class="form-group" style="margin-bottom: 20px;">
        <label class="form-label" style="font-weight: 700;">Company / Sender Display Name *</label>
        <input type="text" name="company_name" class="form-control" value="<?php echo htmlspecialchars($curCompanyName); ?>" placeholder="e.g. HealthJobsGlobal Administration" required>
        <small style="color: var(--text-muted); display: block; margin-top: 4px;">Name displayed beside the email in recipient inboxes.</small>
      </div>

      <button type="submit" class="btn btn-primary">
        <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="20 6 9 17 4 12"/></svg>
        Save & Apply Outgoing Email
      </button>
    </form>
  </div>
</div>


<?php require_once __DIR__ . '/includes/footer.php'; ?>
