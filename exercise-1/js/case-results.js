(function ($) {
    $(function () {
        var $filter = $('#nxs_case_filter_select');
        var $container = $('#nxs_case_results_container');

        if (!$filter.length || !$container.length || typeof nxsCaseResults === 'undefined') {
            return;
        }

        function loadCases() {
            var caseType = $filter.val();

            $.ajax({
                url: nxsCaseResults.ajaxUrl,
                method: 'POST',
                dataType: 'json',
                data: {
                    action: 'nxs_filter_case_results',
                    nonce: nxsCaseResults.nonce,
                    case_type: caseType
                },
                beforeSend: function () {
                    $container.css('opacity', 0.6);
                },
                success: function (res) {
                    if (res && res.success && res.data && res.data.html) {
                        $container.html(res.data.html);
                    } else {
                        $container.html('<p>No results found.</p>');
                    }
                },
                error: function () {
                    $container.html('<p>There was an error loading case results. Please try again.</p>');
                },
                complete: function () {
                    $container.css('opacity', 1);
                }
            });
        }

        $filter.on('change', loadCases);

        /**
         * GTM tracking: user clicks a case result.
         */
        $(document).on('click', '.nxs-case-card .nxs-case-link', function () {
            var $card = $(this).closest('.nxs-case-card');
            var caseType = $card.data('case-type') || '';
            var settlementAmount = $card.data('settlement-amount') || '';

            window.dataLayer = window.dataLayer || [];
            window.dataLayer.push({
                event: 'case_result_view',
                case_type: caseType,
                settlement_amount: settlementAmount
            });
        });
    });
})(jQuery);
