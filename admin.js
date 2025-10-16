jQuery(document).ready(function($) {
    $('#test-rates-form').on('submit', function(e) {
        e.preventDefault();
        var $form = $(this);
        var $result = $('#rates-result');
        $result.html('<p>Loading...</p>');

        $.ajax({
            url: lsrwc.ajax_url,
            type: 'POST',
            data: {
                action: 'lsrwc_test_rates',
                nonce: lsrwc.nonce,
                city: $form.find('#city').val(),
                state: $form.find('#state').val(),
                zip: $form.find('#zip').val(),
                country: $form.find('#country').val(),
                weight: $form.find('#weight').val(),
                length: $form.find('#length').val(),
                width: $form.find('#width').val(),
                height: $form.find('#height').val(),
            },
            success: function(response) {
                if (response.success) {
                    $result.html(response.data);
                } else {
                    $result.html('<p class="lsrwc-error">' + response.data + '</p>');
                }
            },
            error: function(xhr, status, error) {
                $result.html('<p class="lsrwc-error">Error fetching rates: ' + error + '. Check console for details.</p>');
                console.log('AJAX Error:', xhr, status, error);
            }
        });
    });

    $('#lsrwc-clear-debug').on('click', function() {
        var $button = $(this);
        $button.prop('disabled', true).text('Clearing...');

        $.ajax({
            url: lsrwc.ajax_url,
            type: 'POST',
            data: {
                action: 'lsrwc_clear_debug',
                nonce: lsrwc.nonce
            },
            success: function(response) {
                if (response.success) {
                    $('#lsrwc-debug').html('<p>' + response.data + '</p>');
                } else {
                    alert('Error clearing debug information: ' + response.data);
                }
            },
            error: function() {
                alert('Error clearing debug information. Please try again.');
            },
            complete: function() {
                $button.prop('disabled', false).text('Clear Debug');
            }
        });
    });

    $('#lsrwc-clear-log-file').on('click', function() {
        var $button = $(this);
        $button.prop('disabled', true).text('Clearing...');

        $.ajax({
            url: lsrwc.ajax_url,
            type: 'POST',
            data: {
                action: 'lsrwc_clear_debug',
                nonce: lsrwc.nonce
            },
            success: function(response) {
                if (response.success) {
                    $('#debug-log-tab').html('<h2>Debug Log File</h2><p>' + response.data + '</p>');
                } else {
                    alert('Error clearing debug log file: ' + response.data);
                }
            },
            error: function() {
                alert('Error clearing debug log file. Please try again.');
            },
            complete: function() {
                $button.prop('disabled', false).text('Clear Log File');
            }
        });
    });
});