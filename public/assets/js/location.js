/**
 * KaamMitra - Real-Time Geolocation Detection & Service Coverage Controller
 * Detects visitor's actual coordinates, reverse geocodes via PHP backend,
 * and displays verified locality beside the navbar logo and coverage banner.
 */

(function() {
    'use strict';

    var locationWatchId = null;
    var lastEvaluatedCoords = { lat: null, lng: null, time: 0 };
    var isLocationFetchPending = false;

    document.addEventListener('DOMContentLoaded', function() {
        initRealTimeLocation();
    });

    function initRealTimeLocation() {
        var navLocationIndicator = document.getElementById('navLocationIndicator');
        var navLocationName = document.getElementById('navLocationName');

        // Check browser Geolocation API support
        // If unsupported, keep location unset silently
        if (!('geolocation' in navigator)) {
            return;
        }

        var geoOptions = {
            enableHighAccuracy: true,
            timeout: 10000,
            maximumAge: 60000
        };

        // Start watching position continuously while page is active
        try {
            locationWatchId = navigator.geolocation.watchPosition(
                handlePositionSuccess,
                handlePositionError,
                geoOptions
            );
        } catch (e) {
            // Leave location unset silently
        }

        // Clean up watcher when leaving page
        window.addEventListener('beforeunload', function() {
            if (locationWatchId !== null && navigator.geolocation) {
                navigator.geolocation.clearWatch(locationWatchId);
                locationWatchId = null;
            }
        });

        // Handle Position Success — strictly uses actual browser coordinates
        function handlePositionSuccess(position) {
            if (!position || !position.coords) return;
            var coords = position.coords;
            var lat = coords.latitude;
            var lng = coords.longitude;
            var accuracy = coords.accuracy || null;
            var timestamp = position.timestamp || Date.now();

            // Validate actual numeric coordinates
            if (typeof lat !== 'number' || typeof lng !== 'number' || isNaN(lat) || isNaN(lng)) return;

            // Check if movement is significant (> 300 meters) or first lookup to avoid redundant calls
            if (lastEvaluatedCoords.lat !== null && lastEvaluatedCoords.lng !== null) {
                var distanceMovedKm = window.getDistanceKm 
                    ? window.getDistanceKm(lat, lng, lastEvaluatedCoords.lat, lastEvaluatedCoords.lng)
                    : 1.0;
                var timeSinceLastSec = (Date.now() - lastEvaluatedCoords.time) / 1000;
                if (distanceMovedKm < 0.3 && timeSinceLastSec < 300) {
                    return;
                }
            }

            if (isLocationFetchPending) return;
            isLocationFetchPending = true;

            fetchLocationData({ lat: lat, lng: lng, accuracy: accuracy, timestamp: timestamp });
        }

        // Handle Geolocation Permission / GPS Failure
        function handlePositionError() {
            // Silently leave location unset; keep navbar location indicator hidden
            if (navLocationIndicator) {
                navLocationIndicator.style.display = 'none';
            }
            if (navLocationName) {
                navLocationName.textContent = '';
            }
        }

        // Fetch location & coverage details from backend
        async function fetchLocationData(params) {
            try {
                var queryParams = new URLSearchParams();
                queryParams.set('lat', params.lat);
                queryParams.set('lng', params.lng);
                if (params.accuracy !== null && params.accuracy !== undefined) {
                    queryParams.set('accuracy', params.accuracy);
                }
                if (params.timestamp) {
                    queryParams.set('timestamp', params.timestamp);
                }

                var basePath = window.getAppBasePath ? window.getAppBasePath() : '';
                var apiPath = basePath + 'api/detect-location.php?' + queryParams.toString();
                var res = await fetch(apiPath, {
                    method: 'GET',
                    headers: { 'Accept': 'application/json' }
                });

                var data = await res.json();
                isLocationFetchPending = false;

                if (data && data.success) {
                    lastEvaluatedCoords = { lat: params.lat, lng: params.lng, time: Date.now(), verified: true };
                    applyServiceCoverage(data);
                }
            } catch (error) {
                isLocationFetchPending = false;
                // Network or server error: leave location unset silently
            }
        }

        // Update UI with verified real-time location and database coverage data
        function applyServiceCoverage(data) {
            // A. Show Actual Location beside Logo in Navbar
            if (data.display_name && data.display_name.trim() !== '') {
                if (navLocationName) {
                    navLocationName.textContent = data.display_name;
                }
                if (navLocationIndicator) {
                    navLocationIndicator.style.display = 'inline-flex';
                    if (window.lucide) {
                        lucide.createIcons({ root: navLocationIndicator });
                    }
                }
            } else {
                // If reverse geocoding did not return a valid locality/city, keep location unset
                if (navLocationIndicator) {
                    navLocationIndicator.style.display = 'none';
                }
                if (navLocationName) {
                    navLocationName.textContent = '';
                }
            }

            // B. Update Service Availability Banner using actual database records
            var coverageBanner = document.getElementById('serviceCoverageBanner');
            var coverageDot = document.getElementById('coverageDot');
            var coverageTitle = document.getElementById('coverageTitle');
            var coverageSubtitle = document.getElementById('coverageSubtitle');
            var coverageMeta = document.getElementById('coverageMeta');

            if (coverageBanner) {
                if (data.serviceable && data.active_providers > 0) {
                    coverageBanner.style.display = 'block';
                    coverageBanner.className = 'service-coverage-banner serviceable';
                    if (coverageDot) coverageDot.className = 'coverage-dot';
                    if (coverageTitle) {
                        coverageTitle.textContent = 'Doorstep Services Active in ' + data.display_name;
                    }
                    if (coverageSubtitle) {
                        coverageSubtitle.textContent = data.active_providers + ' verified service provider(s) registered in your area.';
                    }
                    if (coverageMeta) {
                        coverageMeta.innerHTML = '<span class="coverage-badge-pill"><i data-lucide="shield-check" style="width: 14px; height: 14px;"></i> Verified Providers Active</span>';
                        if (window.lucide) lucide.createIcons({ root: coverageMeta });
                    }
                } else if (data.display_name) {
                    coverageBanner.style.display = 'block';
                    coverageBanner.className = 'service-coverage-banner unserviceable';
                    if (coverageDot) coverageDot.className = 'coverage-dot dot-amber';
                    if (coverageTitle) {
                        coverageTitle.textContent = 'Service Expanding Soon to ' + data.display_name;
                    }
                    if (coverageSubtitle) {
                        coverageSubtitle.textContent = 'No approved service providers currently registered for this location.';
                    }
                    if (coverageMeta) {
                        coverageMeta.innerHTML = '<a href="tel:18005226648" class="btn btn-outline btn-sm" style="font-size: 0.8rem; text-decoration: none;">Call Helpline</a>';
                    }
                }
            }

            // C. Update Search Placeholder if city was detected
            var headerSearchInput = document.getElementById('headerSearchInput');
            if (headerSearchInput && data.city) {
                headerSearchInput.setAttribute('placeholder', 'Search repairs in ' + data.city + '...');
            }
        }
    }
})();
