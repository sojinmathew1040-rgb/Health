<?php
/**
 * HealthJobsGlobal - Unified Mail Dispatcher & Outbox System
 * Supports Direct SMTP (Gmail, Outlook, Custom Relay), PHP mail(), and Persistent Outbox Logging.
 * Guarantees zero lost verification emails and full visibility in Windows XAMPP environments.
 */

if (!defined('BASE_URL')) {
    require_once __DIR__ . '/config.php';
}
require_once __DIR__ . '/db.php';

/**
 * Ensure email_outbox table exists
 */
function initMailOutboxTable(?mysqli $con): void {
    if (!$con || $con->connect_error) {
        return;
    }
    $con->query("CREATE TABLE IF NOT EXISTS `email_outbox` (
        `id` INT AUTO_INCREMENT PRIMARY KEY,
        `recipient` VARCHAR(191) NOT NULL,
        `recipient_name` VARCHAR(191) DEFAULT '',
        `sender` VARCHAR(191) NOT NULL,
        `sender_name` VARCHAR(191) DEFAULT '',
        `subject` VARCHAR(255) NOT NULL,
        `body` TEXT NOT NULL,
        `temp_password` VARCHAR(100) DEFAULT '',
        `type` VARCHAR(50) DEFAULT 'activation',
        `status` VARCHAR(50) DEFAULT 'Dispatched',
        `delivery_note` VARCHAR(255) DEFAULT '',
        `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
}

// Auto-run table creation
if (!empty($con) && !$con->connect_error) {
    initMailOutboxTable($con);
}

/**
 * Direct Socket SMTP Mail Dispatcher
 * Works across all PHP installations without third-party dependencies
 */
function sendDirectSmtp(
    string $host,
    int $port,
    string $user,
    string $pass,
    string $secure,
    string $fromEmail,
    string $fromName,
    string $toEmail,
    string $toName,
    string $subject,
    string $body
): array {
    $timeout = 10;
    $errno = 0;
    $errstr = '';

    $ctx = stream_context_create([
        'ssl' => [
            'verify_peer' => false,
            'verify_peer_name' => false,
            'allow_self_signed' => true
        ]
    ]);

    // Try primary protocol/port
    $remoteUri = ($secure === 'ssl' || $port === 465) ? ("ssl://" . $host . ":" . $port) : ("tcp://" . $host . ":" . $port);
    $socket = @stream_socket_client($remoteUri, $errno, $errstr, $timeout, STREAM_CLIENT_CONNECT, $ctx);

    // If port 587 fails to connect (common on residential ISPs blocking 587), fallback to SSL port 465
    if (!$socket && $port === 587) {
        $fallbackUri = "ssl://" . $host . ":465";
        $socket = @stream_socket_client($fallbackUri, $errno, $errstr, $timeout, STREAM_CLIENT_CONNECT, $ctx);
        if ($socket) {
            $secure = 'ssl';
            $port = 465;
        }
    }

    if (!$socket) {
        return ['success' => false, 'error' => "Cannot connect to SMTP server {$host}:{$port} ($errstr). Check your network or ISP port access."];
    }

    stream_set_timeout($socket, $timeout);

    $readResponse = function() use ($socket) {
        $data = '';
        while ($str = fgets($socket, 515)) {
            $data .= $str;
            if (substr($str, 3, 1) === ' ') {
                break;
            }
        }
        return $data;
    };

    $sendCommand = function($cmd, $expectedCode = 250) use ($socket, $readResponse) {
        fputs($socket, $cmd . "\r\n");
        $res = $readResponse();
        $code = (int)substr($res, 0, 3);
        if ($expectedCode && $code !== $expectedCode) {
            return ['ok' => false, 'code' => $code, 'response' => trim($res)];
        }
        return ['ok' => true, 'code' => $code, 'response' => trim($res)];
    };

    // Initial greeting
    $greet = $readResponse();
    if ((int)substr($greet, 0, 3) !== 220) {
        fclose($socket);
        return ['success' => false, 'error' => "SMTP greeting failed: " . trim($greet)];
    }

    $cRes = $sendCommand("EHLO " . gethostname());
    if (!$cRes['ok']) {
        $cRes = $sendCommand("HELO " . gethostname());
    }

    // STARTTLS if TLS port (usually 587)
    if (($secure === 'tls' || $port === 587) && strpos($targetHost, 'ssl://') === false) {
        $tlsRes = $sendCommand("STARTTLS", 220);
        if ($tlsRes['ok']) {
            $crypto = @stream_socket_enable_crypto($socket, true, STREAM_CRYPTO_METHOD_TLSv1_2_CLIENT | STREAM_CRYPTO_METHOD_TLSv1_3_CLIENT);
            if ($crypto) {
                // Re-send EHLO after TLS handshake
                $sendCommand("EHLO " . gethostname());
            }
        }
    }

    // Authentication if user provided
    if (!empty($user) && !empty($pass)) {
        $authRes = $sendCommand("AUTH LOGIN", 334);
        if (!$authRes['ok']) {
            fclose($socket);
            return ['success' => false, 'error' => "AUTH LOGIN rejected: " . $authRes['response']];
        }

        $uRes = $sendCommand(base64_encode($user), 334);
        if (!$uRes['ok']) {
            fclose($socket);
            return ['success' => false, 'error' => "Username rejected: " . $uRes['response']];
        }

        $pRes = $sendCommand(base64_encode($pass), 235);
        if (!$pRes['ok']) {
            fclose($socket);
            return ['success' => false, 'error' => "Password rejected: " . $pRes['response']];
        }
    }

    // Mail envelope
    $fRes = $sendCommand("MAIL FROM: <{$fromEmail}>", 250);
    if (!$fRes['ok']) {
        fclose($socket);
        return ['success' => false, 'error' => "MAIL FROM failed: " . $fRes['response']];
    }

    $tRes = $sendCommand("RCPT TO: <{$toEmail}>", 250);
    if (!$tRes['ok']) {
        fclose($socket);
        return ['success' => false, 'error' => "RCPT TO failed: " . $tRes['response']];
    }

    $dRes = $sendCommand("DATA", 354);
    if (!$dRes['ok']) {
        fclose($socket);
        return ['success' => false, 'error' => "DATA start failed: " . $dRes['response']];
    }

    // Prepare MIME content
    $headers = [];
    $headers[] = "Date: " . date('r');
    $headers[] = "From: =?UTF-8?B?" . base64_encode($fromName) . "?= <{$fromEmail}>";
    $headers[] = "To: =?UTF-8?B?" . base64_encode($toName) . "?= <{$toEmail}>";
    $headers[] = "Subject: =?UTF-8?B?" . base64_encode($subject) . "?=";
    $headers[] = "MIME-Version: 1.0";
    $headers[] = "Content-Type: text/plain; charset=UTF-8";
    $headers[] = "Content-Transfer-Encoding: 8bit";
    $headers[] = "X-Mailer: HealthJobsGlobal Core Mailer";

    $messageData = implode("\r\n", $headers) . "\r\n\r\n" . $body . "\r\n.";
    $mRes = $sendCommand($messageData, 250);

    $sendCommand("QUIT", 221);
    fclose($socket);

    if (!$mRes['ok']) {
        return ['success' => false, 'error' => "Sending message data failed: " . $mRes['response']];
    }

    return ['success' => true, 'response' => $mRes['response']];
}

/**
 * Universal System Mail Dispatcher
 * Logs every email to database & HTML Outbox file and attempts SMTP/mail()
 */
function sendSystemMail(
    ?mysqli $con,
    string $toEmail,
    string $subject,
    string $body,
    string $tempPassword = '',
    string $type = 'activation',
    string $toName = ''
): array {
    $toEmail = trim($toEmail);
    if (empty($toEmail)) {
        return ['success' => false, 'message' => 'Empty recipient email address.'];
    }

    $fromEmail = getCompanySenderEmail($con);
    $fromName = getCompanyDisplayName($con);
    if (empty($toName)) {
        $toName = $toEmail;
    }

    // Retrieve custom SMTP settings if configured
    $smtpHost = getSystemSetting($con, 'smtp_host', '');
    $smtpPort = (int)getSystemSetting($con, 'smtp_port', '587');
    $smtpUser = getSystemSetting($con, 'smtp_user', '');
    $smtpPass = getSystemSetting($con, 'smtp_pass', '');
    $smtpSecure = getSystemSetting($con, 'smtp_secure', 'tls');

    $isSent = false;
    $status = 'Pending Dispatch';
    $deliveryNote = 'Pending Dispatch';

    // 1. If SMTP configured, try direct socket delivery
    if (!empty($smtpHost) && !empty($smtpUser)) {
        $smtpResult = sendDirectSmtp(
            $smtpHost,
            $smtpPort,
            $smtpUser,
            $smtpPass,
            $smtpSecure,
            $fromEmail,
            $fromName,
            $toEmail,
            $toName,
            $subject,
            $body
        );

        if ($smtpResult['success']) {
            $isSent = true;
            $status = 'Delivered (SMTP)';
            $deliveryNote = 'Transmitted via ' . $smtpHost . ':' . $smtpPort;
        } else {
            $status = 'SMTP Delivery Error';
            $deliveryNote = 'SMTP dispatch: ' . substr($smtpResult['error'] ?? 'Check credentials', 0, 180);
        }
    }

    // 2. Fallback to PHP native mail() if SMTP not configured
    if (!$isSent && empty($smtpHost)) {
        @ini_set('sendmail_from', $fromEmail);
        $headers = "From: =?UTF-8?B?" . base64_encode($fromName) . "?= <{$fromEmail}>\r\n";
        $headers .= "Reply-To: {$fromEmail}\r\n";
        $headers .= "MIME-Version: 1.0\r\n";
        $headers .= "Content-Type: text/plain; charset=UTF-8\r\n";
        $headers .= "X-Mailer: HealthJobsGlobal Admin\r\n";

        $mailSent = @mail($toEmail, $subject, $body, $headers);
        if ($mailSent) {
            $status = 'Delivered (PHP mail)';
            $deliveryNote = 'Handed off to local MTA';
            $isSent = true;
        } else {
            $status = 'SMTP Relay Required';
            $deliveryNote = 'To send real emails to ' . $toEmail . ', enter your SMTP / Gmail credentials in the Company Email settings.';
        }
    }

    // 3. Save into email_outbox table
    $outboxId = 0;
    if ($con && !$con->connect_error) {
        initMailOutboxTable($con);
        $stmt = $con->prepare("INSERT INTO `email_outbox` (
            `recipient`, `recipient_name`, `sender`, `sender_name`, 
            `subject`, `body`, `temp_password`, `type`, `status`, `delivery_note`, `created_at`
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())");
        if ($stmt) {
            $stmt->bind_param(
                "ssssssssss",
                $toEmail,
                $toName,
                $fromEmail,
                $fromName,
                $subject,
                $body,
                $tempPassword,
                $type,
                $status,
                $deliveryNote
            );
            $stmt->execute();
            $outboxId = $stmt->insert_id;
            $stmt->close();
        }
    }

    // 4. Append to interactive HTML Outbox log file for visual browser access
    writeHtmlOutboxLog($outboxId, $toEmail, $toName, $fromEmail, $fromName, $subject, $body, $tempPassword, $status, $deliveryNote);

    return [
        'success' => true,
        'outbox_id' => $outboxId,
        'status' => $status,
        'delivery_note' => $deliveryNote,
        'temp_password' => $tempPassword,
        'recipient' => $toEmail
    ];
}

/**
 * Writes an Apple Level Pro HTML Outbox Viewer log
 * Located at: c:\xampp\htdocs\Health\storage\email_outbox.html
 */
function writeHtmlOutboxLog(
    int $id,
    string $toEmail,
    string $toName,
    string $fromEmail,
    string $fromName,
    string $subject,
    string $body,
    string $tempPassword,
    string $status,
    string $deliveryNote
): void {
    $storageDir = __DIR__ . '/../storage';
    if (!is_dir($storageDir)) {
        @mkdir($storageDir, 0777, true);
    }

    $filePath = $storageDir . '/email_outbox.html';
    $now = date('d M Y, h:i A');

    $entryHtml = '<div class="mail-card" id="mail-' . $id . '">
      <div class="mail-header">
        <div class="mail-to">
          <span class="badge-tag">' . htmlspecialchars($status) . '</span>
          <strong>To: ' . htmlspecialchars($toName) . ' &lt;' . htmlspecialchars($toEmail) . '&gt;</strong>
        </div>
        <div class="mail-time">' . $now . ' (ID: #' . $id . ')</div>
      </div>
      <div class="mail-subject"><strong>Subject:</strong> ' . htmlspecialchars($subject) . '</div>
      <div class="mail-from"><strong>From:</strong> ' . htmlspecialchars($fromName) . ' &lt;' . htmlspecialchars($fromEmail) . '&gt;</div>';

    if (!empty($tempPassword)) {
        $entryHtml .= '<div class="password-box">
          <span class="pw-label">⚡ SYSTEM GENERATED PASSWORD:</span>
          <span class="pw-val">' . htmlspecialchars($tempPassword) . '</span>
          <span class="pw-hint">(Must be replaced by employer on first login)</span>
        </div>';
    }

    $entryHtml .= '<pre class="mail-body">' . htmlspecialchars($body) . '</pre>
      <div class="mail-footer"><small>' . htmlspecialchars($deliveryNote) . '</small></div>
    </div>';

    if (!file_exists($filePath)) {
        $fullHtml = '<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>HealthJobsGlobal — Outbox Mail Viewer</title>
<style>
  body { font-family: -apple-system, BlinkMacSystemFont, "SF Pro Text", "Segoe UI", Roboto, sans-serif; background: #F5F7FA; margin: 0; padding: 24px; color: #1E293B; }
  .container { max-width: 900px; margin: 0 auto; }
  .header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 24px; padding-bottom: 16px; border-bottom: 1px solid #E2E8F0; }
  .header h1 { margin: 0; font-size: 1.4rem; color: #0F172A; }
  .mail-card { background: #FFFFFF; border-radius: 14px; border: 1px solid #E2E8F0; padding: 20px; margin-bottom: 18px; box-shadow: 0 4px 12px rgba(0,0,0,0.03); }
  .mail-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 12px; }
  .badge-tag { background: #ECFDF5; color: #065F46; border: 1px solid #A7F3D0; font-size: 0.72rem; font-weight: 700; padding: 3px 8px; border-radius: 9999px; margin-right: 8px; }
  .mail-time { font-size: 0.8rem; color: #64748B; }
  .mail-subject { font-size: 0.98rem; color: #0F172A; margin-bottom: 4px; }
  .mail-from { font-size: 0.82rem; color: #475569; margin-bottom: 12px; }
  .password-box { background: #EFF6FF; border: 1.5px dashed #0284C7; border-radius: 10px; padding: 12px 16px; margin: 12px 0; display: flex; align-items: center; gap: 12px; flex-wrap: wrap; }
  .pw-label { font-size: 0.78rem; font-weight: 800; color: #0369A1; }
  .pw-val { font-family: monospace; font-size: 1.25rem; font-weight: 800; color: #0F172A; background: #FFF; padding: 4px 10px; border-radius: 6px; border: 1px solid #BAE6FD; }
  .pw-hint { font-size: 0.78rem; color: #64748B; }
  .mail-body { background: #F8FAFC; border: 1px solid #EDF2F7; border-radius: 8px; padding: 14px; white-space: pre-wrap; font-family: monospace; font-size: 0.82rem; color: #334155; line-height: 1.6; margin: 12px 0 0 0; }
  .mail-footer { margin-top: 8px; font-size: 0.74rem; color: #94A3B8; }
</style>
</head>
<body>
<div class="container">
  <div class="header">
    <div>
      <h1>HealthJobsGlobal — Dispatched Outbox</h1>
      <div style="font-size: 0.82rem; color: #64748B;">Live local audit trail of all verification passwords and notifications</div>
    </div>
    <a href="../admin/employer-verifications.php" style="background: #0071E3; color: #FFF; text-decoration: none; padding: 8px 16px; border-radius: 8px; font-size: 0.84rem; font-weight: 600;">&larr; Back to Admin</a>
  </div>
  <!-- MAIL_ENTRIES_START -->
  ' . $entryHtml . '
</div>
</body>
</html>';
        @file_put_contents($filePath, $fullHtml);
    } else {
        $content = @file_get_contents($filePath);
        if ($content && strpos($content, '<!-- MAIL_ENTRIES_START -->') !== false) {
            $updated = str_replace('<!-- MAIL_ENTRIES_START -->', "<!-- MAIL_ENTRIES_START -->\n  " . $entryHtml, $content);
            @file_put_contents($filePath, $updated);
        } else {
            @file_put_contents($filePath, "\n" . $entryHtml, FILE_APPEND);
        }
    }
}

