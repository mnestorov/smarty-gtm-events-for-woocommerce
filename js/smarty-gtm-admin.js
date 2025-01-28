jQuery(document).ready(function($) {
    // Handle tab switching
    $(".smarty-gtm-nav-tab").click(function (e) {
        e.preventDefault();
        $(".smarty-gtm-nav-tab").removeClass("smarty-gtm-nav-tab-active");
        $(this).addClass("smarty-gtm-nav-tab-active");

        $(".smarty-gtm-tab-content").removeClass("active");
        $($(this).attr("href")).addClass("active");
    });

    // Load README.md
    $("#smarty-gtm-load-readme-btn").click(function () {
        const $content = $("#smarty-gtm-readme-content");
        $content.html("<p>Loading...</p>");

        $.ajax({
            url: smartyGtmEvents.ajaxUrl,
            type: "POST",
            data: {
                action: "smarty_gtm_load_readme",
                nonce: smartyGtmEvents.nonce,
            },
            success: function (response) {
                console.log(response);
                if (response.success) {
                    $content.html(response.data);
                } else {
                    $content.html("<p>Error loading README.md</p>");
                }
            },
        });
    });

    // Load CHANGELOG.md
    $("#smarty-gtm-load-changelog-btn").click(function () {
        const $content = $("#smarty-gtm-changelog-content");
        $content.html("<p>Loading...</p>");

        $.ajax({
            url: smartyGtmEvents.ajaxUrl,
            type: "POST",
            data: {
                action: "smarty_gtm_load_changelog",
                nonce: smartyGtmEvents.nonce,
            },
            success: function (response) {
                console.log(response);
                if (response.success) {
                    $content.html(response.data);
                } else {
                    $content.html("<p>Error loading CHANGELOG.md</p>");
                }
            },
        });
    });

    // Pagination logs
    let currentPage = 1;
    let totalPages = 1;
    const limit = 10; // Make sure this matches the limit in your PHP code

    function loadEventLogs(page) {
        $.ajax({
            url: smartyGtmEvents.ajaxUrl,
            method: 'POST',
            dataType: 'json',
            data: {
                action: 'smarty_gtm_load_event_logs_paginated',
                nonce: smartyGtmEvents.nonce,
                page: page,
            },
            beforeSend: function() {
                // Show a "loading" message in our container
                $('#smarty-gtm-event-logs-table-container').html('<p>Loading event logs...</p>');
            },
            success: function(response) {
                if (!response.success) {
                    // If success == false, display the error
                    $('#smarty-gtm-event-logs-table-container')
                        .html('<p>Error: ' + (response.data || 'Unknown error') + '</p>');
                    return;
                }

                const data = response.data;
                const logs = data.logs;
                const total = data.total;

                // Calculate how many pages exist
                totalPages = Math.ceil(total / limit);
                currentPage = page;

                // Build the table HTML
                let tableHtml = '<table class="widefat fixed" style="margin-bottom: 20px;">';
                tableHtml += '<thead><tr>';
                tableHtml += '    <th>Event Time</th>';
                tableHtml += '    <th>Event Name</th>';
                tableHtml += '    <th style="width: 60%;">Details</th>';
                tableHtml += '</tr></thead>'
                tableHtml += '<tbody>';

                if (logs.length > 0) {
                    logs.forEach(function(log) {
                        const eventDataRaw = log.event_data; // Now it's a JSON string, not PHP-serialized
                        tableHtml += '<tr>';
                        tableHtml += '  <td>' + log.event_time + '</td>';
                        tableHtml += '  <td>' + log.event_name + '</td>';
                        tableHtml += '  <td><details>'
                                   + '        <summary style="cursor: pointer;">View Details</summary>'
                                   + '        <pre>' + eventDataRaw + '</pre>'
                                   + '    </details></td>';
                        tableHtml += '</tr>';
                     });
                } else {
                    tableHtml += '<tr><td colspan="3">No events found.</td></tr>';
                }

                tableHtml += '</tbody></table>';

                // Replace the container HTML with our new table
                $('#smarty-gtm-event-logs-table-container').html(tableHtml);

                // Update pagination
                $('#smarty-gtm-current-page').text(currentPage);
                $('#smarty-gtm-total-pages').text(totalPages);

                // Show the pagination controls if there's more than one page or logs to show
                if (totalPages > 1 || logs.length > 0) {
                    $('#smarty-gtm-event-logs-pagination').show();
                } else {
                    $('#smarty-gtm-event-logs-pagination').hide();
                }
            },
            error: function() {
                $('#smarty-gtm-event-logs-table-container')
                    .html('<p>An unexpected error occurred while loading logs.</p>');
            }
        });
    }

    // Attach click events for pagination
    $('#smarty-gtm-prev-page').on('click', function() {
        if (currentPage > 1) {
            loadEventLogs(currentPage - 1);
        }
    });

    $('#smarty-gtm-next-page').on('click', function() {
        if (currentPage < totalPages) {
            loadEventLogs(currentPage + 1);
        }
    });

    // Load the first page on initial page load
    loadEventLogs(1);
});