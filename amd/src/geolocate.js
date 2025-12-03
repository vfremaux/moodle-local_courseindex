
/* eslint no-undef: "off" */

define(['jquery', 'core/prefetch', 'core/templates', 'core/log'], function($, Prefetch, Templates, log) {

    var geolocate = {

        map: null,

        markers: Array(),

        popupmarkers: Array(),

        /**
         * Initialise la carte Leaflet
         * @param {string} markers
         */
        init: function(data) {
            if (this.map) {
                return; // Évite les initialisations multiples.
            }

            this.markers = data.markers;
            this.defaultmapcenter = data.defaultcenterloc;
            this.defaultzoom = data.defaultzoom;

            var centerlocdata = geolocate.geocodeLocation(this.defaultmapcenter);
            var centerloc = [centerlocdata.lat, centerlocdata.lon];

            // Centre initial sur la France
            // const franceCenter = [46.2276, 2.2137];
            var zoomLevel = this.defaultzoom;

            this.map = L.map('map', {
                zoomControl: true,
                attributionControl: false,
                dragging: true,
                touchZoom: true,
                scrollWheelZoom: true
            }).setView(centerloc, zoomLevel);

            // Add OpenStreetMap tiles
            L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                attribution: false,
                maxZoom: 19,
                minZoom: 4,
                preferCanvas: true
            }).addTo(this.map);

            // Invalidate map size.
            setTimeout(() => {
                if (this.map) {
                    this.map.invalidateSize();
                }
            }, 350);

            Prefetch.prefetchTemplate('local_courseindex/geomarker');
            log.debug('AMD courseindex geocode initialized');

            // Locate all markers.
            for (i = 0; i < this.markers.length; i++) {
                let m = this.markers[i];
                let locdata = geolocate.geocodeLocation(m.location);
                this.markers[i].lon = locdata.lon;
                this.markers[i].lat = locdata.lat;
                this.markers[i].locname = locdata.name;
                this.markers[i].locdisplayname = locdata.displayName;
                geolocate.addMarker(this.markers[i]);
            }

            // Focus to region center
            geolocate.showLocation();
        },

        /**
         * Geolocates using Nominatim API
         * @param {string} locationName
         */
        geocodeLocation: async function(locationName) {
            try {
                const response = await fetch(
                    'https://nominatim.openstreetmap.org/search?format=json&q=' +
                    encodeURIComponent(locationName) + ',France&limit=1',
                    {
                        headers: {
                            'User-Agent': 'MoodleFormationMap/1.0'
                        }
                    }
                );

                if (!response.ok) {
                    throw new Error('Network error');
                }

                const data = await response.json();

                if (data && data.length > 0) {
                    return {
                        lat: parseFloat(data[0].lat),
                        lon: parseFloat(data[0].lon),
                        name: data[0].name,
                        displayName: data[0].display_name
                    };
                }
                return null;
            } catch (error) {
                log.debug('Geocoding error :' + error);
                return null;
            }
        },

        /**
         * Ajoute un marqueur sur la carte
         * @param {object} marker
         */
        addMarker: function(marker) {

            // Create a custom icon.
            const icon = L.icon({
                iconUrl: 'data:image/svg+xml;base64,PHN2ZyB4bWxucz0iaHR0cDovL3d3dy53My5vcmcvMjAwMC9' +
                'zdmciIHdpZHRoPSIzMiIgaGVpZ2h0PSI0MCIgdmlld0JveD0iMCAwIDMyIDQwIj48cGF0aCBkPSJNMTYgMEM' +
                '4LjI3IDAgMiA2LjI3IDIgMTRjMCA0IDAgMTggMTQgMjZzMTQtMjIgMTQtMjZjMC03LjczLTYuMjctMTQtMTQtM' +
                'TR6TTEyIDIwYzAgMi4yMiAxLjc4IDQgNCA0czQtMS43OCA0LTQtMS43OC00LTQtNC00IDEuNzgtNCA0eiIgZml' +
                'sbD0iIzY2N2VlYSIvPjwvc3ZnPg==',
                iconSize: [32, 40],
                iconAnchor: [16, 40],
                popupAnchor: [0, -40],
                title: marker.location,
                className: 'custom-marker'
            });

            let popupContent = Templates.render('local_courseindex/geomarker', {
                    hascost:marker.hascost,
                    cost:marker.cost,
                    timestart:marker.timestart,
                    timeend:marker.timeend,
                    coursename: marker.course,
                    location: marker.location,
                    mode: marker.mode
            });

            // Create the markers

            this.popupmarkers.push(L.marker([marker.lat, marker.lon], { icon: icon })
                .addTo(this.map)
                .bindPopup(popupContent, {
                    maxWidth: 250,
                    maxHeight: 250,
                    className: 'custom-popup'
                }).openPopup());

            // Centrer et zoomer sur le marqueur
            this.map.setView([marker.lat, marker.lon], 12);
        },

        /**
         * Show the map poiting to a location
         * @param {string} locationName
         */
        showLocation: async function(locationName) {

            this.initMap();

            const locationInfo = $('#locationInfo');
            if (locationInfo) {
                locationInfo.html('Géolocalisation en cours...');
            }

            // Geocode location
            const location = await this.geocodeLocation(locationName);

            if (location) {
                this.currentLocation = location;
                this.addMarker(location.lat, location.lon, location.name);
                if (locationInfo) {
                    locationInfo.html('?? ' + location.name);
                }
            } else {
                if (locationInfo) {
                    locationInfo.html('? Localisation non trouvée');
                }
                log.warn('La localité "' + locationName + '" n\'a pas pu être géolocalisée');
            }
        },

        /**
         * Échappe les caractères HTML
         * @param {string} text
         */
        escapeHtml: function(text) {

            if (!text) {
                return '';
            }

            const map = {
                '&': '&amp;',
                '<': '&lt;',
                '>': '&gt;',
                '"': '&quot;',
                "'": '&#039;'
            };
            return String(text).replace(/[&<>"']/g, function(m) { return map[m]; });
        },

        /**
         * Ouvre le modal avec la carte
         * @param {string} locationName
         */
        openMap: function(locationName) {
            const themap = $('#mapOverlay');
            if (!themap) {
                return;
            }

            themap.addClass('active');

            geolocate.showLocation(locationName);
        }

    };

    return geolocate;
});