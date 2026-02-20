jQuery(document).ready(function($) {
    let uploadKey = null;
    let totalRows = 0;
    
    // Upload form handler
    $('#advgls-csv-upload-form').on('submit', function(e) {
        e.preventDefault();
        
        const formData = new FormData(this);
        formData.append('action', 'advgls_preview_csv');
        formData.append('nonce', advglsCSV.nonce);
        
        const uploadBtn = $('#advgls-upload-btn');
        const originalText = uploadBtn.text();
        
        uploadBtn.prop('disabled', true).text(advglsCSV.uploading);
        
        $.ajax({
            url: advglsCSV.ajax_url,
            type: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            dataType: 'json',
            success: function(response) {
                if (response.success) {
                    uploadKey = response.data.upload_key;
                    totalRows = response.data.total_rows;
                    displayPreview(response.data);
                } else {
                    alert(response.data || advglsCSV.error);
                    uploadBtn.prop('disabled', false).text(originalText);
                }
            },
            error: function() {
                alert(advglsCSV.error);
                uploadBtn.prop('disabled', false).text(originalText);
            }
        });
    });
    
    // Display preview
    function displayPreview(data) {
        const previewSection = $('#advgls-preview-section');
        const previewContent = $('#advgls-preview-content');
        
        let html = '<div class="advgls-preview-stats">';
        html += '<p><strong>' + advglsCSV.total_rows + ':</strong> ' + data.total_rows + '</p>';
        html += '<p><strong>' + advglsCSV.valid_rows + ':</strong> ' + data.valid_rows + '</p>';
        if (data.invalid_rows > 0) {
            html += '<p class="advgls-error"><strong>' + advglsCSV.invalid_rows + ':</strong> ' + data.invalid_rows + '</p>';
        }
        html += '</div>';
        
        if (data.preview && data.preview.length > 0) {
            html += '<table class="wp-list-table widefat fixed striped">';
            html += '<thead><tr>';
            html += '<th>' + advglsCSV.row + '</th>';
            html += '<th>' + advglsCSV.term + '</th>';
            html += '<th>' + advglsCSV.definition + '</th>';
            html += '<th>' + advglsCSV.status + '</th>';
            html += '</tr></thead><tbody>';
            
            data.preview.forEach(function(row) {
                const statusClass = row._valid ? 'advgls-valid' : 'advgls-invalid';
                const statusText = row._valid ? advglsCSV.valid : advglsCSV.invalid;
                const definition = row._definition || '';
                const truncatedDef = definition.length > 100 ? definition.substring(0, 100) + '...' : definition;
                
                html += '<tr class="' + statusClass + '">';
                html += '<td>' + row._row_number + '</td>';
                html += '<td>' + escapeHtml(row._term || '') + '</td>';
                html += '<td>' + escapeHtml(truncatedDef) + '</td>';
                html += '<td><span class="advgls-status-badge ' + statusClass + '">' + statusText + '</span></td>';
                html += '</tr>';
            });
            
            html += '</tbody></table>';
            
            if (data.total_rows > 20) {
                html += '<p class="description">' + advglsCSV.showing_first_20 + '</p>';
            }
        }
        
        if (data.errors && data.errors.length > 0) {
            html += '<div class="advgls-errors">';
            html += '<h3>' + advglsCSV.validation_errors + '</h3>';
            html += '<ul>';
            data.errors.forEach(function(error) {
                html += '<li><strong>' + advglsCSV.row + ' ' + error.row + ':</strong> ' + error.errors.join(', ') + '</li>';
            });
            html += '</ul></div>';
        }
        
        previewContent.html(html);
        previewSection.show();
        
        $('#advgls-upload-btn').prop('disabled', false).text(advglsCSV.upload_and_preview);
    }
    
    // Start import
    $('#advgls-start-import-btn').on('click', function() {
        if (!uploadKey) {
            alert(advglsCSV.no_file);
            return;
        }
        
        const duplicateAction = $('#duplicate_action').val();
        const importStatus = $('#import_status').val();
        const importBtn = $(this);
        
        importBtn.prop('disabled', true);
        $('#advgls-preview-section').hide();
        $('#advgls-progress-section').show();
        
        processImport(0, duplicateAction, importStatus);
    });
    
    // Process import in batches
    function processImport(batch, duplicateAction, status) {
        $.ajax({
            url: advglsCSV.ajax_url,
            type: 'POST',
            data: {
                action: 'advgls_import_csv',
                nonce: advglsCSV.nonce,
                upload_key: uploadKey,
                duplicate_action: duplicateAction,
                status: status,
                batch: batch
            },
            dataType: 'json',
            success: function(response) {
                if (response.success) {
                    const data = response.data;
                    
                    // Update progress
                    const progress = (data.processed / data.total) * 100;
                    $('#advgls-progress-fill').css('width', progress + '%');
                    $('#advgls-progress-text').text(
                        advglsCSV.processing + ' ' + data.processed + ' / ' + data.total + 
                        ' (' + advglsCSV.imported + ': ' + data.results.imported + 
                        ', ' + advglsCSV.updated + ': ' + data.results.updated + 
                        ', ' + advglsCSV.skipped + ': ' + data.results.skipped + 
                        ', ' + advglsCSV.failed + ': ' + data.results.failed + ')'
                    );
                    
                    if (data.complete) {
                        // Import complete
                        displayResults(data.results);
                    } else {
                        // Process next batch
                        setTimeout(function() {
                            processImport(data.batch, duplicateAction, status);
                        }, 100);
                    }
                } else {
                    alert(response.data || advglsCSV.error);
                    $('#advgls-start-import-btn').prop('disabled', false);
                }
            },
            error: function() {
                alert(advglsCSV.error);
                $('#advgls-start-import-btn').prop('disabled', false);
            }
        });
    }
    
    // Display results
    function displayResults(results) {
        const resultsSection = $('#advgls-results-section');
        const resultsContent = $('#advgls-results-content');
        
        let html = '<div class="advgls-results-summary">';
        html += '<h3>' + advglsCSV.import_summary + '</h3>';
        html += '<ul>';
        html += '<li><strong>' + advglsCSV.imported + ':</strong> ' + results.imported + '</li>';
        html += '<li><strong>' + advglsCSV.updated + ':</strong> ' + results.updated + '</li>';
        html += '<li><strong>' + advglsCSV.skipped + ':</strong> ' + results.skipped + '</li>';
        html += '<li><strong>' + advglsCSV.failed + ':</strong> ' + results.failed + '</li>';
        html += '</ul></div>';
        
        if (results.errors && results.errors.length > 0) {
            html += '<div class="advgls-errors">';
            html += '<h3>' + advglsCSV.errors + '</h3>';
            html += '<table class="wp-list-table widefat fixed striped">';
            html += '<thead><tr>';
            html += '<th>' + advglsCSV.row + '</th>';
            html += '<th>' + advglsCSV.term + '</th>';
            html += '<th>' + advglsCSV.error + '</th>';
            html += '</tr></thead><tbody>';
            
            results.errors.forEach(function(error) {
                html += '<tr>';
                html += '<td>' + error.row + '</td>';
                html += '<td>' + escapeHtml(error.term || '') + '</td>';
                html += '<td>' + escapeHtml(error.error || '') + '</td>';
                html += '</tr>';
            });
            
            html += '</tbody></table></div>';
        }
        
        html += '<p><a href="' + advglsCSV.glossary_url + '" class="button button-primary">' + advglsCSV.view_glossary + '</a></p>';
        
        resultsContent.html(html);
        resultsSection.show();
        $('#advgls-progress-section').hide();
    }
    
    // Download template
    $('#advgls-download-template').on('click', function(e) {
        e.preventDefault();
        
        const form = $('<form>', {
            method: 'POST',
            action: advglsCSV.ajax_url
        });
        
        form.append($('<input>', {
            type: 'hidden',
            name: 'action',
            value: 'advgls_download_template'
        }));
        
        form.append($('<input>', {
            type: 'hidden',
            name: 'nonce',
            value: advglsCSV.nonce
        }));
        
        $('body').append(form);
        form.submit();
        form.remove();
    });
    
    // Escape HTML
    function escapeHtml(text) {
        const map = {
            '&': '&amp;',
            '<': '&lt;',
            '>': '&gt;',
            '"': '&quot;',
            "'": '&#039;'
        };
        return text ? text.replace(/[&<>"']/g, function(m) { return map[m]; }) : '';
    }
});
