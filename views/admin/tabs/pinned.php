<?php if ( ! defined( 'WPINC' ) ) die; ?>

<form method="GET" action="<?php echo admin_url('admin-post.php'); ?>" >
    <input type="hidden" value="pinned" name="tab">
    <input type="hidden" value="premmerce-pinterest-page" name="page">
    <input type="hidden" value="<?php echo $account->getUserName(); ?>" name="pin_username">
    <?php
         $pinnedTable->prepare_items();
         $pinnedTable->display();
    ?>
</form>

