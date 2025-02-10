<?php
$merakiplugin = new Meraki();
$merakipluginConfig = $merakiplugin->config->get('Plugins', 'Meraki');
if ($merakiplugin->auth->checkAccess($merakipluginConfig['ACL-ADMIN'] ?? null) == false) {
    $merakiplugin->api->setAPIResponse('Error', 'Unauthorized', 401);
    return;
}
?>

<div class="container-fluid">
    <div class="row">
        <div class="col">
            <h1>NOC Meraki Appliances Dashboard</h1>
        </div>
    </div>

    <!-- Overall Summary Dashboard -->
    <div class="row mb-4">
        <div class="col-12">
            <h4 class="mb-3">Overall Status</h4>
        </div>
        <div class="col-md-4">
            <div class="card bg-danger text-white">
                <div class="card-body">
                    <h5 class="card-title">Offline Appliances</h5>
                    <h2 class="card-text" id="OfflineAppliances">0</h2>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card bg-success text-white">
                <div class="card-body">
                    <h5 class="card-title">Online Appliances</h5>
                    <h2 class="card-text" id="OnlineAppliances">0</h2>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card bg-warning text-white">
                <div class="card-body">
                    <h5 class="card-title">Dormant Appliances</h5>
                    <h2 class="card-text" id="DormantAppliances">0</h2>
                </div>
            </div>
        </div>
    </div>

    <!-- Product Type Specific Status -->
    <div class="row mb-4">
        <div class="col-12">
            <h4 class="mb-3">Wireless Devices</h4>
        </div>
        <div class="col-md-4">
            <div class="card bg-danger text-white">
                <div class="card-body">
                    <h5 class="card-title">Offline Wireless</h5>
                    <h2 class="card-text" id="OfflineWireless">0</h2>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card bg-success text-white">
                <div class="card-body">
                    <h5 class="card-title">Online Wireless</h5>
                    <h2 class="card-text" id="OnlineWireless">0</h2>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card bg-warning text-white">
                <div class="card-body">
                    <h5 class="card-title">Dormant Wireless</h5>
                    <h2 class="card-text" id="DormantWireless">0</h2>
                </div>
            </div>
        </div>
    </div>

    <div class="row mb-4">
        <div class="col-12">
            <h4 class="mb-3">Switches</h4>
        </div>
        <div class="col-md-4">
            <div class="card bg-danger text-white">
                <div class="card-body">
                    <h5 class="card-title">Offline Switches</h5>
                    <h2 class="card-text" id="OfflineSwitch">0</h2>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card bg-success text-white">
                <div class="card-body">
                    <h5 class="card-title">Online Switches</h5>
                    <h2 class="card-text" id="OnlineSwitch">0</h2>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card bg-warning text-white">
                <div class="card-body">
                    <h5 class="card-title">Dormant Switches</h5>
                    <h2 class="card-text" id="DormantSwitch">0</h2>
                </div>
            </div>
        </div>
    </div>

    <div class="row mb-4">
        <div class="col-12">
            <h4 class="mb-3">Appliances</h4>
        </div>
        <div class="col-md-4">
            <div class="card bg-danger text-white">
                <div class="card-body">
                    <h5 class="card-title">Offline Appliances</h5>
                    <h2 class="card-text" id="OfflineAppliance">0</h2>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card bg-success text-white">
                <div class="card-body">
                    <h5 class="card-title">Online Appliances</h5>
                    <h2 class="card-text" id="OnlineAppliance">0</h2>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card bg-warning text-white">
                <div class="card-body">
                    <h5 class="card-title">Dormant Appliances</h5>
                    <h2 class="card-text" id="DormantAppliance">0</h2>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col">
            <div class="card">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <h5 class="card-title mb-0">Appliances List</h5>
                        <div class="d-flex align-items-center">
                            <div class="form-check form-switch me-3">
                                <input class="form-check-input" type="checkbox" id="autoRefreshToggle">
                                <label class="form-check-label" for="autoRefreshToggle">Auto-refresh</label>
                            </div>
                            <small id="lastRefreshTime" class="text-muted me-3">Last refreshed: Never</small>
                            <button id="refreshTable" class="btn btn-primary btn-sm">
                                <i class="fas fa-sync-alt me-1"></i> Refresh
                            </button>
                        </div>
                    </div>
                    <table id="AppliancesTable"></table>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
function StatusBadgeFormatter(value, row) {
    const statusClass = value === 'online' ? 'bg-success' : value === 'dormant' ? 'bg-warning' : 'bg-danger';
    return `<span class="badge ${statusClass}">${value}</span>`;
}

function TagsFormatter(value, row) {
    if (!value || value.length === 0) return '-';
    return value.map(tag => `<span class="badge bg-info me-1">${tag}</span>`).join(' ');
}

var table;
var refreshTimer;
const REFRESH_INTERVAL = 5 * 60 * 1000; // 5 minutes in milliseconds

function startAutoRefresh() {
    if (refreshTimer) clearInterval(refreshTimer);
    refreshTimer = setInterval(function() {
        $('#refreshTable').click();
    }, REFRESH_INTERVAL);
}

function stopAutoRefresh() {
    if (refreshTimer) {
        clearInterval(refreshTimer);
        refreshTimer = null;
    }
}

$(document).ready(function() {
    table = $("#AppliancesTable").bootstrapTable({
        url: "/api/plugin/Meraki/getdevicesavailabilities",
        method: 'GET',
        pagination: true,
        search: true,
        sortable: true,
        responseHandler: function(res) {
            if (res && res.result === 'Success' && Array.isArray(res.data)) {
                // Update overall summary counters
                const onlineCount = res.data.filter(device => device.status === 'online').length;
                const dormantCount = res.data.filter(device => device.status === 'dormant').length;
                const offlineCount = res.data.filter(device => device.status !== 'online' && device.status !== 'dormant').length;
                
                $('#OnlineAppliances').text(onlineCount);
                $('#DormantAppliances').text(dormantCount);
                $('#OfflineAppliances').text(offlineCount);

                // Update product type specific counters
                ['wireless', 'switch', 'appliance'].forEach(type => {
                    const typeDevices = res.data.filter(device => device.productType === type);
                    const typeOnline = typeDevices.filter(device => device.status === 'online').length;
                    const typeDormant = typeDevices.filter(device => device.status === 'dormant').length;
                    const typeOffline = typeDevices.filter(device => device.status !== 'online' && device.status !== 'dormant').length;

                    $(`#Online${type.charAt(0).toUpperCase() + type.slice(1)}`).text(typeOnline);
                    $(`#Dormant${type.charAt(0).toUpperCase() + type.slice(1)}`).text(typeDormant);
                    $(`#Offline${type.charAt(0).toUpperCase() + type.slice(1)}`).text(typeOffline);
                });

                return res.data;
            }
            return [];
        },
        columns: [{
            field: "name",
            title: "Name",
            sortable: true
        }, {
            field: 'productType',
            title: 'Product Type',
            sortable: true
        }, {
            field: 'serial',
            title: 'Serial',
            sortable: true
        }, {
            field: 'mac',
            title: 'MAC Address',
            sortable: true
        }, {
            field: 'network.id',
            title: 'Network ID',
            sortable: true,
            formatter: function(value, row) {
                return row.network?.id || '-';
            }
        }, {
            field: 'status',
            title: 'Status',
            sortable: true,
            formatter: StatusBadgeFormatter,
            class: 'text-center'
        }, {
            field: 'tags',
            title: 'Tags',
            formatter: TagsFormatter,
            class: 'text-center'
        }]
    });

    // Handle refresh button click
    $('#refreshTable').click(function() {
        const $btn = $(this);
        const $icon = $btn.find('i');
        
        $btn.prop('disabled', true);
        $icon.addClass('fa-spin');
        
        table.bootstrapTable('refresh');
        
        // Update the last refresh time
        const now = new Date();
        $('#lastRefreshTime').text('Last refreshed: ' + now.toLocaleString());
        
        // Re-enable the button after a short delay
        setTimeout(function() {
            $btn.prop('disabled', false);
            $icon.removeClass('fa-spin');
        }, 1000);
    });

    // Handle auto-refresh toggle
    $('#autoRefreshToggle').change(function() {
        if ($(this).is(':checked')) {
            startAutoRefresh();
        } else {
            stopAutoRefresh();
        }
    });
});
</script>