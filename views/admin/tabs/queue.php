<?php if ( ! defined( 'WPINC' ) ) die; ?>

<?php $queueTable->views(); ?>

<form method="GET" action="<?php echo admin_url('admin-post.php'); ?>" >
    <input type="hidden" value="queue" name="tab">
    <input type="hidden" value="premmerce-pinterest-page" name="page">
    <?php
        $queueTable->prepare_items();
        $queueTable->display();
    ?>
</form>

