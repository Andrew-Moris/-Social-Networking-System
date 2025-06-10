<?php

header("Location: chat_fixed_final.php" . (isset($_GET['user']) ? '?user=' . $_GET['user'] : ''));
exit;
?> 