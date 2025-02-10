// Helper function to format date and time
function formatDateTime(dateTimeString) {
    if (!dateTimeString) return '-';
    
    const date = new Date(dateTimeString);
    if (isNaN(date.getTime())) return dateTimeString;
    
    return date.toLocaleString('en-GB', {
        day: '2-digit',
        month: '2-digit',
        year: 'numeric',
        hour: '2-digit',
        minute: '2-digit',
        second: '2-digit',
        hour12: false
    }).replace(',', '');
}

function StatusBadgeFormatter(value, row) {
    const statusClass = value === 'online' ? 'bg-success' : value === 'dormant' ? 'bg-warning' : 'bg-danger';
    return `<span class="badge ${statusClass}">${value}</span>`;
}

function TagsFormatter(value, row) {
    if (!value || value.length === 0) return '-';
    return value.map(tag => `<span class="badge bg-info me-1">${tag}</span>`).join(' ');
}

// Handle refresh button click
$('#refreshTable').click(function() {
    const $btn = $(this);
    const $icon = $btn.find('i');
    
    $btn.prop('disabled', true);
    $icon.addClass('fa-spin');
    
    $('#AppliancesTable').bootstrapTable('refresh')
        .then(function() {
            const now = new Date();
            $('#lastRefreshTime').text('Last refreshed: ' + now.toLocaleString());
        })
        .catch(function(error) {
            console.error('Error refreshing data:', error);
            alert('Error refreshing data. Please try again.');
        })
        .finally(function() {
            $btn.prop('disabled', false);
            $icon.removeClass('fa-spin');
        });
});
