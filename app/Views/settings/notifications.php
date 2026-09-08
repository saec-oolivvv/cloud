<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($pageTitle ?? 'Notifications') ?> — SAEC Cloud</title>
    <link rel="stylesheet" href="/assets/css/saec.css">
    <style>
        .notif-list { list-style: none; padding: 0; margin: 0; }
        .notif-item {
            background: #111; border: 1px solid #222; border-radius: 8px;
            padding: 16px; margin-bottom: 12px; cursor: pointer;
            transition: border-color .15s;
        }
        .notif-item:hover { border-color: #00ff88; }
        .notif-item.unread { border-left: 3px solid #00ff88; }
        .notif-item .type {
            font-size: 11px; text-transform: uppercase; letter-spacing: .5px;
            color: #00ff88; margin-bottom: 4px;
        }
        .notif-item .title { font-weight: 600; margin-bottom: 4px; }
        .notif-item .message { color: #aaa; font-size: 14px; }
        .notif-item .time { color: #666; font-size: 12px; margin-top: 8px; }
        .empty { text-align: center; color: #666; padding: 60px 20px; }
        .mark-all { float: right; font-size: 13px; color: #00ff88; cursor: pointer; border: none; background: none; }
        .mark-all:hover { text-decoration: underline; }
    </style>
</head>
<body>
    <?php require __DIR__ . '/../../Views/layout/nav.php'; ?>
    <div class="container" style="max-width: 700px; margin: 40px auto; padding: 0 20px;">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 24px;">
            <h1 style="margin: 0; font-size: 24px;">Notifications</h1>
            <?php if (($unreadCount['count'] ?? 0) > 0): ?>
            <button class="mark-all" onclick="markAllRead()">Tout marquer lu</button>
            <?php endif; ?>
        </div>

        <?php if (empty($notifications)): ?>
        <div class="empty">
            <p>Aucune notification.</p>
        </div>
        <?php else: ?>
        <ul class="notif-list" id="notifList">
            <?php foreach ($notifications as $notif): ?>
            <li class="notif-item <?= $notif['is_read'] ? '' : 'unread' ?>" data-id="<?= $notif['id'] ?>" onclick="markRead(this)">
                <div class="type"><?= htmlspecialchars($notif['type']) ?></div>
                <div class="title"><?= htmlspecialchars($notif['title']) ?></div>
                <div class="message"><?= htmlspecialchars($notif['message']) ?></div>
                <div class="time"><?= htmlspecialchars($notif['created_at']) ?></div>
            </li>
            <?php endforeach; ?>
        </ul>
        <?php endif; ?>
    </div>
    <script>
    async function markRead(el) {
        if (el.classList.contains('unread')) {
            await fetch('/notifications/' + el.dataset.id + '/read', {method:'POST'});
            el.classList.remove('unread');
        }
    }
    async function markAllRead() {
        await fetch('/notifications/read-all', {method:'POST'});
        document.querySelectorAll('.notif-item.unread').forEach(el => el.classList.remove('unread'));
    }
    </script>
</body>
</html>
