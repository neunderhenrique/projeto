(function( $ ) {
    'use strict';

    /**
     * All of the code for your public-facing JavaScript source
     * should reside in this file.
     *
     * Note: It has been assumed you will write jQuery code here, so the
     * $ function reference has been prepared for usage within the scope
     * of this function.
     *
     * This enables you to define handlers, for when the DOM is ready:
     *
     * $(function() {
     *
     * });
     *
     * When the window is loaded:
     *
     * $( window ).load(function() {
     *
     * });
     *
     * ...and/or other possibilities.
     *
     * Ideally, it is not considered best practise to attach more than a
     * single DOM-ready or window-load handler for a particular page.
     * Although scripts in the WordPress core, Plugins and Themes may be
     * practising this, we should strive to set a better example in our own work.
     */

    // Function to update live view data
    function updateLiveViewData() {
        $.ajax({
            url: ava_ajax_obj.ajax_url,
            type: 'POST',
            data: {
                action: 'ava_get_live_view_data'
            },
            success: function(response) {
                if (response.success) {
                    $('#active-visitors-count').text(response.data.active_visitors);
                    $('#total-sessions').text(response.data.total_sessions);
                    $('#total-sales').text(response.data.total_sales);
                    $('#top-products').empty();
                    $.each(response.data.top_products, function(product_id, quantity) {
                        var productName = response.data.product_names[product_id]; // Get the product name from the response
                        $('#top-products').append('<li>' + productName + ': ' + quantity + '</li>');
                    });
                    $('#first-time-orders').text(response.data.first_time_orders);
                    $('#return-orders').text(response.data.return_orders);
                } else {
                    console.log('Error in response:', response.data);
                }
            },
            error: function(jqXHR, textStatus, errorThrown) {
                console.log('AJAX error:', textStatus, errorThrown);
            }
        });
    }

    // Set interval to update every 30 seconds
    setInterval(updateLiveViewData, 30000);
    updateLiveViewData(); // Initial call

})( jQuery );
