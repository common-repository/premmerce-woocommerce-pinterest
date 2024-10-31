jQuery(document).ready(function($){
    /**
     * Track AddToCart single product
     */
    $(document).on('click', '.add_to_cart_button', function(){
        var btn = $(this);
        var wrapper = btn.closest('li');
        var price = wrapper.find('.woocommerce-Price-amount').text();
        var currencySymbol = price[0];
        price = price.slice(1);

        var addToCart = {
            value: parseFloat(price),
            order_quantity: 1,
            currency: currencySymbol,
        };
        console.log(addToCart);
        pintrk('track', 'AddToCart', addToCart);
    });
});