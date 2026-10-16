/**
 * KaamMitra - Core Utilities & Shared Helpers
 */

(function() {
    'use strict';

    // 1. Resolve Application Base Path
    window.getAppBasePath = function() {
        if (window.KM_BASE_PATH) return window.KM_BASE_PATH;
        if (document.body && document.body.dataset && document.body.dataset.base) {
            return document.body.dataset.base;
        }
        return '';
    };

    // 2. HTML Escaping Utility
    window.escapeHtml = function(text) {
        if (!text) return '';
        return text.toString().replace(/[&<>"']/g, function(m) {
            return {
                '&': '&amp;',
                '<': '&lt;',
                '>': '&gt;',
                '"': '&quot;',
                "'": '&#039;'
            }[m];
        });
    };

    // 3. String Capitalization
    window.capitalize = function(str) {
        if (!str) return '';
        return str.charAt(0).toUpperCase() + str.slice(1);
    };

    // 4. Lucide Icon Name Mapping
    window.getLucideName = function(icon) {
        var map = {
            'droplet': 'droplet',
            'zap': 'zap',
            'cpu': 'cpu',
            'hammer': 'hammer',
            'sparkles': 'sparkles',
            'paint-brush': 'palette',
            'lock': 'lock',
            'wrench': 'wrench'
        };
        return map[icon] || 'wrench';
    };

    // 5. Search Text Highlight
    window.highlightMatch = function(text, query) {
        if (!query) return window.escapeHtml(text);
        var escapedText = window.escapeHtml(text);
        var escapedQuery = window.escapeHtml(query);
        var regex = new RegExp('(' + escapedQuery + ')', 'gi');
        return escapedText.replace(regex, '<span style="color: #FF5500; font-weight: 700;">$1</span>');
    };

    // 6. Haversine Distance (Kilometers)
    window.getDistanceKm = function(lat1, lon1, lat2, lon2) {
        var R = 6371;
        var dLat = (lat2 - lat1) * Math.PI / 180;
        var dLon = (lon2 - lon1) * Math.PI / 180;
        var a = Math.sin(dLat / 2) * Math.sin(dLat / 2) +
                Math.cos(lat1 * Math.PI / 180) * Math.cos(lat2 * Math.PI / 180) *
                Math.sin(dLon / 2) * Math.sin(dLon / 2);
        var c = 2 * Math.atan2(Math.sqrt(a), Math.sqrt(1 - a));
        return R * c;
    };

    // 7. Toast Notification Utility
    window.showToast = function(message, type) {
        type = type || 'success';
        var container = document.querySelector('.toast-container');
        if (!container) {
            container = document.createElement('div');
            container.className = 'toast-container';
            document.body.appendChild(container);
        }

        var toast = document.createElement('div');
        toast.className = 'toast toast-' + type;
        
        var iconName = type === 'success' ? 'check-circle' : 'alert-circle';
        var iconColor = type === 'success' ? '#10B981' : '#EF4444';

        toast.innerHTML = 
            '<i data-lucide="' + iconName + '" style="color: ' + iconColor + '; width: 20px; height: 20px; flex-shrink: 0;"></i>' +
            '<span>' + window.escapeHtml(message) + '</span>';

        container.appendChild(toast);

        if (window.lucide) {
            lucide.createIcons({ root: toast });
        }

        setTimeout(function() {
            toast.style.opacity = '0';
            toast.style.transform = 'translateY(-10px)';
            toast.style.transition = 'all 0.3s ease';
            setTimeout(function() { toast.remove(); }, 300);
        }, 4000);
    };

    // 8. Auto-initialize Lucide Icons on DOM ready
    document.addEventListener('DOMContentLoaded', function() {
        if (window.lucide) {
            lucide.createIcons();
        }
    });
})();
