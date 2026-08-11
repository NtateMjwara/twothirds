<?php
// Run once daily via cPanel Cron Jobs:
// php /home/youraccount/cron/expire_commitments.php

require __DIR__ . '/../app/bootstrap.php';

use app\services\CommitmentExpiryService;

$count = CommitmentExpiryService::expireOverdue();
echo "{$count} commitment(s) expired.\n";
