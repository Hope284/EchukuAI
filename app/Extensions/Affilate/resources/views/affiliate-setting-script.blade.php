<script>
    $(document).ready(function() {
        $('.btn-status-update').click(function() {
            var userId = $(this).data('id');
            var newStatus = $(this).data('status');
            $.ajax({
                url: '/dashboard/admin/settings/affiliate-status-save/' + userId,
                type: 'POST',
                data: {
                    affiliate_status: newStatus,
                    _token: "{{ csrf_token() }}"
                },
                success: function(response) {
                    location.reload();
                }
            });
        });
    });
</script>