<?php if ( ! defined( 'WPINC' ) ) die; ?>

<?php foreach ( $messages as $message ): ?>
    <div class="notice <?php echo $message[ 'type' ] ?>" >
        <p>
            <b><?php echo $message[ 'text' ]; ?></b>
        </p>
    </div>
<?php endforeach;?>
