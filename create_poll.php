<?php
// Redirect to admin login since only admins can create polls
header('Location: admin/login.php?message=You must be an admin to create polls');
exit;
?> 