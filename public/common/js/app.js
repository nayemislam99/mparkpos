$(function () {
    const $header = $('header.header');
    if (!$header.length) return;

    $(document).on('scroll', function () {
        $header.toggleClass('shadow-sm', $(document).scrollTop() > 0);
    });

    // Keep active menu item and its parent group open and highlighted
    function syncActiveSidebar() {
        const $sb = $('#sidebar');
        if (!$sb.length) return;

        // Find active links in sidebar
        $sb.find('.nav-link.active').each(function () {
            const $link = $(this);
            // Open parent nav-group(s)
            $link.parents('.nav-group').each(function () {
                const $group = $(this);
                $group.addClass('show');
                $group.children('.nav-group-toggle')
                    .addClass('active')
                    .attr('aria-expanded', 'true');
            });
        });
    }

    $(function () {
        syncActiveSidebar();
        setTimeout(syncActiveSidebar, 50);
        setTimeout(syncActiveSidebar, 200);
    });

    $(window).on('load', function () {
        syncActiveSidebar();
        setTimeout(syncActiveSidebar, 50);
    });
});