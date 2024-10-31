let galleryFrame, featureFrame, variableFrame, pinContainer, isPinCheckbox;

jQuery(document).ready(function ($) {
    isPinCheckbox =  jQuery('#is_pinned_post');
    pinContainer =   jQuery('#image-pinterest-container');

    isPinCheckbox.change(checkIsPin);

    // Add event listener for featuredImage frame
    jQuery('#set-post-thumbnail').click(function () {
        featureFrame = wp.media.featuredImage.frame();
        featureFrame.on('select', function () {
            addSelectEvent( featureFrame );
        });
    });

    checkIsPin();
});
/**
 * Add new item to pinContainer
 *
 */
function newAttachment( attachment ) {

    if( jQuery("#premmerce_pinterest_image_" + attachment.id ).length ) return;

    let wrapper = jQuery('<div class="image-pinterest-wrapper"></div>');
    let label = jQuery('<label for="premmerce_pinterest_image_'+attachment.id+'"></label>\n');
    let image = jQuery('<img>');
    let input = jQuery('<input type="checkbox" name="premmerce_pinterest_images[]" id="premmerce_pinterest_image_'+attachment.id+'" value="'+attachment.id+'">');

    image.attr('width', 150);
    image.attr('height', 150);
    image.attr('src', attachment.url);

    label.append(image);
    wrapper.append(label);
    wrapper.append(input);
    wrapper.hide();
    pinContainer.prepend(wrapper);
    wrapper.fadeIn(500);
}

/**
 * Call newAttachment function when user select attachment in frame
 */
function addSelectEvent( frame ) {
    frame.state().get('selection').forEach( function( attachment ) {
        newAttachment( attachment.toJSON() );
    });
}

/**
 * hide/show pinContainer
 */
function checkIsPin(withoutAnimate = false) {
    let isPinned = isPinCheckbox.attr('checked');
    let animateTime = withoutAnimate ? 0 : 300;

    if( isPinned ){
        pinContainer.fadeIn(animateTime);
    }else{
        pinContainer.fadeOut(animateTime);
    }
}
/**
 * toggle all pins
 */
function toggleAllPin(event, isSelect = true) {

    event.preventDefault();

    jQuery.each($('.image-pinterest-wrapper'),function (index, element) {
        jQuery(element).find('input[type=checkbox]')[0].checked = isSelect;
    });
}
