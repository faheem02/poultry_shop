$(document).ready(function () {

    // Sidebar toggle — collapse to icon-only on desktop, slide in/out on mobile
    $('#sidebarToggle, #sidebarToggleTop').on('click', function () {
        $('body').toggleClass('sidebar-toggled');
    });

    // Reset sidebar on desktop resize
    $(window).resize(function () {
        if ($(window).width() >= 768) {
            $('body').removeClass('sidebar-toggled');
        }
    }).trigger('resize');

    // DataTables
    if ($.fn.dataTable) {
        $('.datatable').DataTable({
            pageLength: 25,
            language: {
                search: '<i class="fas fa-search me-1"></i>',
                searchPlaceholder: 'Search...',
                lengthMenu: '_MENU_ per page',
                info: 'Showing _START_ to _END_ of _TOTAL_',
            },
            dom: '<"row align-items-center mb-3"<"col-sm-6"l><"col-sm-6"f>>tip',
            stateSave: true,
        });
    }

    // Auto-dismiss alerts
    window.setTimeout(function () {
        $('.alert').fadeOut(500);
    }, 4000);

    // Delete confirmation with SweetAlert2
    $(document).on('click', '.btn-delete', function (e) {
        e.preventDefault();
        const link = $(this).attr('href');
        const text = $(this).data('text') || 'This action cannot be undone.';
        Swal.fire({
            title: 'Are you sure?',
            text: text,
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#059669',
            cancelButtonColor: '#d33',
            confirmButtonText: 'Yes, delete it!',
            cancelButtonText: 'Cancel',
        }).then((result) => {
            if (result.isConfirmed) {
                window.location.href = link;
            }
        });
    });

    // Flash message helper
    window.showSuccess = function (msg) {
        Swal.fire({ icon: 'success', title: 'Success!', text: msg, timer: 2000, showConfirmButton: false });
    };

    window.showError = function (msg) {
        Swal.fire({ icon: 'error', title: 'Error!', text: msg });
    };

});
