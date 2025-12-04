jQuery(document).ready(function($) {

    // Function to collect all filters and send AJAX request
    function fetchResults() {
        var data = {
            action: 'apaf_filter_pods',
            // Basic Filters
            cidade: $('.city-select').val(),
            bairro: $('.neighborhood-select').val(),
            tipo_negocio: $('input[name="tipo_negocio"]').is(':checked') ? 'venda' : 'aluguel', // Example logic

            // Modal Filters
            quartos: $('.apaf-numeric-buttons[data-key="quartos"] button.active').data('value'),
            aceita_financiamento: $('.apaf-numeric-buttons[data-key="aceita_financiamento"] button.active').data('value'),
            rua: $('input[name="rua"]').val(),
            preco_min: $('input[name="preco_min"]').val(),
            preco_max: $('input[name="preco_max"]').val(),

            // Nonce
            nonce: apaf_vars.nonce
        };

        // UI Loading State (Optional)
        $('#apaf-results').css('opacity', '0.5');

        $.post(apaf_vars.ajax_url, data, function(response) {
            $('#apaf-results').html(response).css('opacity', '1');
        });
    }

    // Trigger on change of Main Bar inputs
    $('#apaf-search-bar select').change(function() {
        fetchResults();
    });

    // Trigger on Toggle change
    $('input[name="tipo_negocio"]').change(function() {
         fetchResults();
    });

    // Handle Numeric Buttons (Single Selection logic)
    $('.apaf-numeric-buttons button').click(function() {
        // Deselect siblings
        $(this).siblings().removeClass('active');
        // Select clicked
        $(this).addClass('active');
    });

    // Modal Interaction
    $('#apaf-more-filters-btn').click(function(e) {
        e.preventDefault();
        $('#apaf-modal').fadeIn(200);
        $('body').css('overflow', 'hidden'); // Prevent background scrolling
    });

    $('.close-modal, .apaf-modal-backdrop').click(function() {
        $('#apaf-modal').fadeOut(200);
        $('body').css('overflow', '');
    });

    // Apply Filters from Modal
    $('#apaf-apply-filters').click(function() {
        fetchResults();
        $('#apaf-modal').fadeOut(200);
        $('body').css('overflow', '');
    });

    // Initial Load
    fetchResults();

});
