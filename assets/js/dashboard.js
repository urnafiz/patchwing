(function($) {
    'use strict';

    $(function() {
        const $container = $('#system-info-container');
        const nonce = $container.data('nonce');

        /**
         * Refresh System Info Grid
         */
        $('#refresh-system-info').on('click', function(e) {
            e.preventDefault();
            const $btn = $(this);

            // Visual feedback
            $btn.prop('disabled', true).text('Refreshing...');
            $container.css('opacity', '0.5');

            $.ajax({
                url: ajaxurl,
                type: 'POST',
                data: {
                    action: 'patchwing_refresh_system_info',
                    _ajax_nonce: nonce
                },
                success: function(response) {
                    $container.html(response);
                },
                error: function() {
                    alert('Failed to refresh system information. Please try again.');
                },
                complete: function() {
                    $btn.prop('disabled', false).text('Refresh');
                    $container.css('opacity', '1');
                }
            });
        });

        /**
         * Copy Report to Clipboard
         */
        $('#copy-system-info').on('click', function(e) {
            e.preventDefault();
            const $btn = $(this);
            const originalText = $btn.text();

            $btn.prop('disabled', true).text('Generating...');

            $.ajax({
                url: ajaxurl,
                type: 'POST',
                data: {
                    action: 'patchwing_get_system_info_report',
                    _ajax_nonce: nonce
                },
                success: function(response) {
                    // Create a temporary textarea to copy the text
                    const $temp = $('<textarea>');
                    $('body').append($temp);
                    $temp.val(response).select();

                    try {
                        document.execCommand('copy');
                        $btn.text('Copied!');
                    } catch (err) {
                        alert('Unable to copy report automatically.');
                    }

                    $temp.remove();
                },
                error: function() {
                    alert('Failed to fetch the report.');
                },
                complete: function() {
                    setTimeout(() => {
                        $btn.prop('disabled', false).text(originalText);
                    }, 2000);
                }
            });
        });
    });

})(jQuery);