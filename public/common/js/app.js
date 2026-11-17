$(function () {
    const $header = $('header.header');
    if (!$header.length) return;

    $(document).on('scroll', function () {
        $header.toggleClass('shadow-sm', $(document).scrollTop() > 0);
    });

    // Remove any expanded state
    function collapseAllSidebarGroups() {
        const $sb = $('#sidebar');
        if (!$sb.length) return;

        // remove any expanded state
        $sb.find('.nav-group.show').removeClass('show');
        $sb.find('.nav-group-items.show').removeClass('show');

        // if any link is active by template, remove it for now
        // (later you will set active from routes)
        $sb.find('.nav-link.active').removeClass('active');
    }

    function clearCoreUIState() {
        try {
            Object.keys(localStorage).forEach(function (key) {
                const k = key.toLowerCase();
                // keep it broad because CoreUI keys vary by version/template
                if (k.includes('coreui') || k.includes('navigation') || k.includes('sidebar')) {
                    localStorage.removeItem(key);
                }
            });
        } catch (e) { }
    }

    $(function () {
        // clear saved state first
        clearCoreUIState();

        // collapse immediately
        collapseAllSidebarGroups();

        // collapse again after CoreUI finishes binding navigation
        setTimeout(collapseAllSidebarGroups, 50);
        setTimeout(collapseAllSidebarGroups, 200);
    });

    // also run on full load (some bundles init late)
    $(window).on('load', function () {
        collapseAllSidebarGroups();
        setTimeout(collapseAllSidebarGroups, 50);
    });
});