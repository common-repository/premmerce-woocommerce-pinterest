<?php if ( ! defined( 'WPINC' ) ) die; ?>

<!-- Premmerce Pinterest Pixel Trigger <?php echo $event; ?> Event -->
<script>
   pintrk('track', '<?php echo $event; ?>', <?php echo $data ?>);
</script>
<?php echo "\n"; ?>