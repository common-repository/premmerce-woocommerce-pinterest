<?php if ( ! defined( 'WPINC' ) ) die; ?>

<div>
	<input type="checkbox" id="is_pinned_post" name="is_pinned_post" <?php checked( !empty( $dbPins ), true, true )?> value="1">
	<label for="is_pinned_post">
		<h2 style="display: inline-block;"><?php _e( 'Pin post image', 'premmerce-pinterest' )?></h2>
	</label>
</div>
<div id="image-pinterest-container" style="display: none">
    <?php if( ! empty( $images ) ): ?>
        <?php foreach ($images as $image): ?>
            <div class="image-pinterest-wrapper">
                <label for="premmerce_pinterest_image_<?php echo $image; ?>"><?php echo wp_get_attachment_image( $image ); ?></label><input type="checkbox" <?php checked(true, in_array( $image, $dbPins) );?> name="premmerce_pinterest_images[]" id="premmerce_pinterest_image_<?php echo $image?>" value="<?php echo $image; ?>">
            </div>
        <?php endforeach;?>
    <?php endif; ?>
    <p><?php _e( 'Pins will add to queue after save post', 'premmerce-pinterest' ); ?></p>

    <div style="margin-top: 10px;" id="pinterest-controll-buttons">
		<button class="button button-primary" onclick="toggleAllPin(event, true)"><?php _e( 'Select all', 'premmerce-pinterest' ) ?></button>
		<button class="button button-primary" onclick="toggleAllPin(event, false)"><?php _e( 'Unselect all', 'premmerce-pinterest' ) ?></button>
	</div>
</div>