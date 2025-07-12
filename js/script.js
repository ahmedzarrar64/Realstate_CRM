// Main JavaScript file for Real Estate CRM

$(document).ready(function() {
    // Initialize tooltips
    var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
    var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
        return new bootstrap.Tooltip(tooltipTriggerEl);
    });
    
    // Initialize datepickers
    if ($.fn.datepicker) {
        $('.datepicker').datepicker({
            format: 'yyyy-mm-dd',
            autoclose: true,
            todayHighlight: true
        });
    }
    
    // Auto-hide alerts after 5 seconds
    setTimeout(function() {
        $(".alert").alert('close');
    }, 5000);
    
    // Confirm delete actions
    $('.delete-confirm').on('click', function(e) {
        if (!confirm('Are you sure you want to delete this item? This action cannot be undone.')) {
            e.preventDefault();
        }
    });
    
    // Filter tables
    $("#tableSearch").on("keyup", function() {
        var value = $(this).val().toLowerCase();
        $("#dataTable tbody tr").filter(function() {
            $(this).toggle($(this).text().toLowerCase().indexOf(value) > -1);
        });
    });
    
    // Status filter for tables
    $('#statusFilter').on('change', function() {
        var value = $(this).val().toLowerCase();
        if (value === 'all') {
            $("#dataTable tbody tr").show();
        } else {
            $("#dataTable tbody tr").filter(function() {
                $(this).toggle($(this).find(".status-cell").text().toLowerCase().indexOf(value) > -1);
            });
        }
    });
    
    // Date range filter
    $('#dateFilterBtn').on('click', function() {
        var startDate = $('#startDate').val();
        var endDate = $('#endDate').val();
        
        if (startDate && endDate) {
            filterTableByDateRange(startDate, endDate);
        }
    });
    
    // Reset filters
    $('#resetFilters').on('click', function() {
        $('#tableSearch').val('');
        $('#statusFilter').val('all');
        $('#startDate').val('');
        $('#endDate').val('');
        $("#dataTable tbody tr").show();
    });
    
    // Dynamic form fields for property selection based on owner
    $('#owner_id').on('change', function() {
        var ownerId = $(this).val();
        if (ownerId) {
            $.ajax({
                url: 'get_properties.php',
                type: 'POST',
                data: {owner_id: ownerId},
                dataType: 'json',
                success: function(data) {
                    var options = '<option value="">Select Property</option>';
                    $.each(data, function(key, value) {
                        options += '<option value="' + value.id + '">' + value.address + '</option>';
                    });
                    $('#property_id').html(options);
                }
            });
        } else {
            $('#property_id').html('<option value="">Select Property</option>');
        }
    });
    
    // Export to CSV functionality
    $('#exportCSV').on('click', function() {
        exportTableToCSV('export.csv');
    });
});

// Function to filter table by date range
function filterTableByDateRange(startDate, endDate) {
    var start = new Date(startDate);
    var end = new Date(endDate);
    
    $("#dataTable tbody tr").filter(function() {
        var dateCell = $(this).find(".date-cell").text();
        var rowDate = new Date(dateCell);
        
        $(this).toggle(rowDate >= start && rowDate <= end);
    });
}

// Function to export table to CSV
function exportTableToCSV(filename) {
    var csv = [];
    var rows = document.querySelectorAll("#dataTable tr");
    
    for (var i = 0; i < rows.length; i++) {
        var row = [], cols = rows[i].querySelectorAll("td, th");
        
        for (var j = 0; j < cols.length; j++) {
            // Skip action columns
            if (!cols[j].classList.contains('actions-cell')) {
                row.push('"' + cols[j].innerText.replace(/"/g, '""') + '"');
            }
        }
        
        csv.push(row.join(","));
    }
    
    // Download CSV file
    downloadCSV(csv.join("\n"), filename);
}

function downloadCSV(csv, filename) {
    var csvFile;
    var downloadLink;
    
    // CSV file
    csvFile = new Blob([csv], {type: "text/csv"});
    
    // Download link
    downloadLink = document.createElement("a");
    
    // File name
    downloadLink.download = filename;
    
    // Create a link to the file
    downloadLink.href = window.URL.createObjectURL(csvFile);
    
    // Hide download link
    downloadLink.style.display = "none";
    
    // Add the link to DOM
    document.body.appendChild(downloadLink);
    
    // Click download link
    downloadLink.click();
    
    // Remove link from DOM
    document.body.removeChild(downloadLink);
}