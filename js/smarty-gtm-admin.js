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
});