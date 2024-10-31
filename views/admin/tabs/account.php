<?php
if ( ! defined('WPINC')) {
    die;
}
?>

<?php if ($account): ?>
    <div class="postbox premmerce-postbox">
        <?php if ($account->isLoggedIn()): ?>
            <?php $fileManager->includeTemplate('admin/logout_form.php', ['account' => $account]); ?>
        <?php else: ?>
            <?php $fileManager->includeTemplate('admin/login_form.php', ['account' => $account]); ?>
        <?php endif; ?>
    </div>
<?php endif; ?>
