<?php
/**
 * logout.php — Session termination endpoint for signed-in users.
 *
 * Dependencies: Session support.
 * Data sources: None.
 * Last updated: 2026-05-03
 * Authors: Owen Sim, Kylie Mugrace, Keady Van Zandt
 */

// Start and immediately clear the current authenticated session.
session_start();
session_destroy();
header("Location: index.php");
exit;
?>
