<ul class="nav-menu">
    <li><a href="index.php" class="<?php echo basename($_SERVER['PHP_SELF']) === 'index.php' ? 'active' : ''; ?>">Home</a></li>
    <li><a href="game/info.php" class="<?php echo strpos(basename($_SERVER['PHP_SELF']), 'info') !== false ? 'active' : ''; ?>">Game Info</a></li>
    <li><a href="leaderboard.php" class="<?php echo basename($_SERVER['PHP_SELF']) === 'leaderboard.php' ? 'active' : ''; ?>">Leaderboards</a></li>
    <li><a href="wiki.php" class="<?php echo basename($_SERVER['PHP_SELF']) === 'wiki.php' ? 'active' : ''; ?>">Wiki</a></li>
    <li><a href="collection.php" class="<?php echo basename($_SERVER['PHP_SELF']) === 'collection.php' ? 'active' : ''; ?>">Collection</a></li>
    <li><a href="news.php" class="<?php echo basename($_SERVER['PHP_SELF']) === 'news.php' ? 'active' : ''; ?>">News</a></li>
    <li><a href="minigames/" class="<?php echo strpos(dirname($_SERVER['PHP_SELF']), 'minigames') !== false ? 'active' : ''; ?>">Minigames</a></li>
</ul>