<?php
require_once dirname(__DIR__) . '/config/config.php';
require_once BASE_PATH . '/includes/functions.php';
secure_session_start();
if (!isLoggedIn()) redirect(BASE_URL . '/auth/login.php');
if (!isAdmin())    { setFlash('Access denied.','error'); redirect(BASE_URL . '/index.php'); }

$db   = (new \Config\Database())->getConnection();
$base = BASE_URL;

// Handle delete action
if ($_SERVER['REQUEST_METHOD']==='POST' && isset($_POST['action'])) {
    if ($_POST['action']==='delete' && isset($_POST['user_id'])) {
        $delId = (int)$_POST['user_id'];
        if ($delId !== getCurrentUserId()) {
            try { $db->prepare("DELETE FROM users WHERE id=?")->execute([$delId]); setFlash('User deleted.','success'); }
            catch(Exception $e){ setFlash('Delete failed.','error'); }
        } else { setFlash("You can't delete yourself.",'error'); }
        redirect(BASE_URL . '/admin/users.php');
    }
    if ($_POST['action']==='role' && isset($_POST['user_id'], $_POST['role'])) {
        $newRole = in_array($_POST['role'],['user','admin']) ? $_POST['role'] : 'user';
        try { $db->prepare("UPDATE users SET role=? WHERE id=?")->execute([$newRole, (int)$_POST['user_id']]); setFlash('Role updated.','success'); }
        catch(Exception $e){ setFlash('Update failed.','error'); }
        redirect(BASE_URL . '/admin/users.php');
    }
}

$users = [];
try { $users = $db->query("SELECT id,username,email,role,created_at FROM users ORDER BY created_at DESC")->fetchAll(PDO::FETCH_ASSOC); } catch(Exception $e){}
?>
<?php include BASE_PATH . '/includes/header.php'; ?>
<main class="container">
<?php $flash = getFlash(); if ($flash): ?>
<div class="alert alert-<?= $flash['type'] ?>"><?= sanitizeOutput($flash['message']) ?></div>
<?php endif; ?>
<section class="section">
    <div class="page-header">
        <h1 class="page-title">👥 Manage Users</h1>
        <a href="<?= $base ?>/admin/index.php" class="btn btn-outline btn-sm">← Admin Panel</a>
    </div>
    <div class="table-wrap">
        <input type="text" id="user-search" class="form-input" placeholder="Search users…" style="max-width:300px;margin-bottom:1rem">
        <table class="data-table" id="users-table">
            <thead><tr><th>ID</th><th>Username</th><th>Email</th><th>Role</th><th>Joined</th><th>Actions</th></tr></thead>
            <tbody>
            <?php foreach ($users as $u): ?>
            <tr>
                <td><?= (int)$u['id'] ?></td>
                <td><?= sanitizeOutput($u['username']) ?></td>
                <td><?= sanitizeOutput($u['email']) ?></td>
                <td>
                    <form method="POST" action="" style="display:inline">
                        <input type="hidden" name="action" value="role">
                        <input type="hidden" name="user_id" value="<?= (int)$u['id'] ?>">
                        <select name="role" onchange="this.form.submit()" class="form-input" style="padding:4px 8px;font-size:12px">
                            <option value="user"  <?= $u['role']==='user'?'selected':'' ?>>User</option>
                            <option value="admin" <?= $u['role']==='admin'?'selected':'' ?>>Admin</option>
                        </select>
                    </form>
                </td>
                <td><?= formatTimestamp($u['created_at']) ?></td>
                <td>
                    <?php if ((int)$u['id'] !== getCurrentUserId()): ?>
                    <form method="POST" action="" onsubmit="return confirm('Delete <?= sanitizeOutput($u['username']) ?>?')">
                        <input type="hidden" name="action" value="delete">
                        <input type="hidden" name="user_id" value="<?= (int)$u['id'] ?>">
                        <button type="submit" class="btn btn-sm" style="background:#f44336;color:#fff">Delete</button>
                    </form>
                    <?php else: ?><span style="color:#888">Current user</span><?php endif; ?>
                </td>
            </tr>
            <?php endforeach; ?>
            <?php if (empty($users)): ?><tr><td colspan="6" class="empty-cell">No users found.</td></tr><?php endif; ?>
            </tbody>
        </table>
    </div>
</section>
</main>
<?php include BASE_PATH . '/includes/footer.php'; ?>
<script>
document.getElementById('user-search').addEventListener('input', function(){
    var q = this.value.toLowerCase();
    document.querySelectorAll('#users-table tbody tr').forEach(function(row){
        row.style.display = row.textContent.toLowerCase().includes(q) ? '' : 'none';
    });
});
</script>
