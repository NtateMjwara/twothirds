<?php
use app\models\Notification;

$unreadCount = !empty($_SESSION['user_id']) ? Notification::unreadCount((int) $_SESSION['user_id']) : 0;
?>
<nav class="account-nav">
    <a href="/account/portfolio">Portfolio</a>
    <a href="/account/watchlist">Watchlist</a>
    <a href="/account/profile">Profile</a>
    <a href="/account/address">Address</a>
    <a href="/account/kyc">KYC</a>
    <a href="/account/bank">Bank account</a>
    <a href="/account/notifications">Notifications<?= $unreadCount > 0 ? " ({$unreadCount})" : '' ?></a>
    <a href="/logout">Log out</a>
</nav>
