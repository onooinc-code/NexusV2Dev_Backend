// Global App Scripts

$(document).ready(function() {
    // Setup CSRF Token for all AJAX requests
    $.ajaxSetup({
        headers: {
            'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
        }
    });

    // Sidebar Toggle
    $("#menu-toggle").click(function(e) {
        e.preventDefault();
        $("body").toggleClass("toggled");
    });
});
