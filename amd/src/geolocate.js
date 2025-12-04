
/* eslint no-undef: "off" */

define(['jquery', 'core/prefetch', 'core/templates',
        'core/str', 'core/log', 'core/config'], function($, Prefetch, Templates, Strings, log, cfg) {

    var geolocate = {

        map: null,

        markers: Array(),

        popupmarkers: Array(),

        centerloc: null,

        strs: null,

        /**
         * Initialise la carte Leaflet
         * @param {string} data
         */
        init: async function(data) {

            if (this.map) {
                return;
            }

            // Fetch some strings.
            Strings.get_strings([{
                key: 'geofetch',
                component: 'local_courseindex'
            }, {
                key: 'geonotfound',
                component: 'local_courseindex'
            }, {
                key: 'geonotfoundwarn',
                component: 'local_courseindex'
            }]).then(function(results) {
                this.strs['fetch'] = results[0];
                this.strs['notfound'] = results[1];
                this.strs['notfoundwarn'] = results[2];
            });

            var zoomLevel;

            this.markers = data.markers;
            this.defaultmapcenter = data.defaultcenterloc;
            this.defaultzoom = data.defaultzoom;
            if (this.defaultmapcenter != '') {
                var result = await geolocate.geocodeLocation(this.defaultmapcenter);
                this.centerloc = [result.lat, result.lon];
            } else {
                this.centerloc = [46.2276, 2.2137];
            }

            if (this.defaultzoom > 0) {
                zoomLevel = this.defaultzoom;
            } else {
                zoomLevel = 8;
            }

            this.map = L.map('map', {
                zoomControl: true,
                attributionControl: false,
                dragging: true,
                touchZoom: true,
                scrollWheelZoom: true
            }).setView(this.centerloc, zoomLevel);

            // Add OpenStreetMap tiles
            L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                attribution: false,
                maxZoom: 19,
                minZoom: 4,
                preferCanvas: true
            }).addTo(this.map);

            // Invalidate map size.
            /*
            setTimeout(() => {
                if (this.map) {
                    this.map.invalidateSize();
                }
            }, 350);
            */

            Prefetch.prefetchTemplate('local_courseindex/geomarker');
            log.debug('AMD courseindex geocode initialized');

            // Locate all markers.
            for (i = 0; i < this.markers.length; i++) {
                let m = this.markers[i];
                let result = await geolocate.geocodeLocation(m.location);
                geolocate.markers[i].lon = result.lon;
                geolocate.markers[i].lat = result.lat;
                geolocate.markers[i].locname = result.name;
                geolocate.markers[i].locdisplayname = result.displayName;
                geolocate.addMarker(geolocate.markers[i]);
            }

            log.debug('AMD courseindex geocode markers initialized');
            // Focus to region center.
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
        addMarker: async function(marker) {

            // Create a custom icon.
            const icon = L.icon({
                iconUrl: cfg.wwwroot + '/local/courseindex/pix/location/jpg',
                iconSize: [32, 40],
                iconAnchor: [16, 40],
                popupAnchor: [0, -40],
                title: marker.location,
                className: 'custom-marker'
            });

            // Create the marker.
            Templates.render('local_courseindex/geomarker', {
                    hascost:marker.hascost,
                    cost:marker.cost,
                    timestart:marker.timestart,
                    timeend:marker.timeend,
                    coursename: marker.course,
                    location: marker.location,
                    mode: marker.mode
            }).then(function(html) {
                geolocate.popupmarkers.push(L.marker([marker.lat, marker.lon], { icon: icon })
                    .addTo(geolocate.map)
                    .bindPopup(html, {
                        maxWidth: 250,
                        maxHeight: 250,
                        className: 'custom-popup'
                    }).openPopup());
            });

            // Centrer et zoomer sur le marqueur
            this.map.setView([marker.lat, marker.lon], 12);
        },

        /**
         * Show the map poiting to a location
         * @param {string} locationName
         */
        showLocation: async function(locationName) {

            // If necessary.
            geolocate.init();

            const locationInfo = $('#locationInfo');
            if (locationInfo) {
                locationInfo.html(geolocate.strs['fetch']);
            }

            // Geocode location
            const location = await geolocate.geocodeLocation(locationName);

            if (location) {
                geolocate.currentLocation = location;
                geolocate.addMarker(location.lat, location.lon, location.name);
                if (locationInfo) {
                    locationInfo.html(location.name);
                }
            } else {
                if (locationInfo) {
                    locationInfo.html(geolocale.strs['notfound']);
                }
                log.warn(geolocale.strs['notfoundwarn'].replace('{{locname}}', locationName));
            }
        },

        /**
         * Escape HTML chars
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