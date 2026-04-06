jQuery(document).ready(function($) {
    function renderResults(data) {
        let html = '';
        html += '<h2>Baseline Load Time</h2><p><strong>' + data.baseline_time + ' seconds</strong></p>';
        html += '<h2>Overall Memory Usage</h2><p><strong>Peak Memory Usage: ' + data.peak_memory + '</strong></p>';
        html += '<h2>Active Theme Performance</h2><p>Theme: <strong>' + data.theme_name + '</strong></p>';
        html += '<p>Load Time: <strong>' + data.theme_time + ' seconds</strong></p>';
        html += '<h2>Active Plugins</h2><ol>';
        data.plugins.forEach(function(p) {
            html += '<li>' + p + '</li>';
        });
        html += '</ol>';
        $('#pa-results').html(html);

        // Trend chart
        const ctx = document.getElementById('trendChart').getContext('2d');
        const labels = data.trends.map(t => t.timestamp);
        const baseline = data.trends.map(t => t.baseline_time);
        const memory = data.trends.map(t => t.peak_memory);
        const themeTime = data.trends.map(t => t.theme_time);

        if (window.paChart) window.paChart.destroy();
        window.paChart = new Chart(ctx, {
            type: 'line',
            data: {
                labels: labels,
                datasets: [{
                        label: 'Baseline Load Time (s)',
                        data: baseline,
                        borderColor: 'rgba(75,192,192,1)',
                        fill: false
                    },
                    {
                        label: 'Peak Memory Usage (MB/GB)',
                        data: memory,
                        borderColor: 'rgba(255,159,64,1)',
                        fill: false
                    },
                    {
                        label: 'Theme Load Time (s)',
                        data: themeTime,
                        borderColor: 'rgba(153,102,255,1)',
                        fill: false
                    }
                ]
            }
        });
    }

    $('#pa-refresh').on('click', function() {
        $.post(patchwingPerformanceAnalyzer.ajax_url, {
            action: 'patchwing_performance_analyzer_refresh',
            nonce: patchwingPerformanceAnalyzer.nonce
        }, function(response) {
            renderResults(response);
        });
    });

    $('#pa-clear').on('click', function() {
        if (!confirm('Are you sure you want to clear all trend data?')) return;
        $.post(patchwingPerformanceAnalyzer.ajax_url, {
            action: 'patchwing_performance_analyzer_clear',
            nonce: patchwingPerformanceAnalyzer.nonce
        }, function(response) {
            if (response.success) {
                $('#pa-results').html('<p>Data cleared.</p>');
                if (window.paChart) window.paChart.destroy();
            }
        });
    });

    // Auto refresh on load
    $('#pa-refresh').trigger('click');
});