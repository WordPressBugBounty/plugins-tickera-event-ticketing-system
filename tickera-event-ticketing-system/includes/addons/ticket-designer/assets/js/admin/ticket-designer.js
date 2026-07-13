/**
 * Venuera Ticket Designer
 * 
 * Main entry point for the ticket template editor.
 * Uses Fabric.js for canvas manipulation.
 */

(function($) {
    'use strict';

    // Global editor object - will be extended by modules
    window.TicketDesigner = {
        canvas: null,
        currentTool: 'select',
        currentZoom: 1,
        isDirty: false,
        undoStack: [],
        redoStack: [],
        selectedElement: null,
        showGrid: false,
        snapToGrid: false,
        gridSize: 10,
        
        // Template settings (dimensions in points - PDF standard)
        templateWidth: 432,   // 6 inches
        templateHeight: 180,  // 2.5 inches
        templateBackground: '#ffffff',
        templateUnit: 'pt',   // pt, mm, or px
        
        // Points per inch/mm for conversions
        PTS_PER_INCH: 72,
        PTS_PER_MM: 2.834645669,
        
        // Size presets (in points - standard PDF unit)
        sizePresets: {
            standard:     { width: 432, height: 180, label: 'Standard (6" × 2.5")' },
            concert:      { width: 576, height: 216, label: 'Concert (8" × 3")' },
            compact:      { width: 360, height: 144, label: 'Compact (5" × 2")' },
            large:        { width: 612, height: 252, label: 'Large (8.5" × 3.5")' },
            pass:         { width: 252, height: 360, label: 'Pass (3.5" × 5")' },
            badge:        { width: 252, height: 324, label: 'Badge (3.5" × 4.5")' },
            a6_landscape: { width: 420, height: 297, label: 'A6 Landscape' },
            a6_portrait:  { width: 297, height: 420, label: 'A6 Portrait' },
            a5_landscape: { width: 595, height: 420, label: 'A5 Landscape' },
            a5_portrait:  { width: 420, height: 595, label: 'A5 Portrait' },
            a4_landscape: { width: 842, height: 595, label: 'A4 Landscape' },
            a4_portrait:  { width: 595, height: 842, label: 'A4 Portrait' },
        },
        
        /**
         * Convert points to display unit.
         */
        ptsToDisplay: function(pts) {
            switch (this.templateUnit) {
                case 'mm':
                    return Math.round(pts / this.PTS_PER_MM * 10) / 10;
                case 'in':
                    return Math.round(pts / this.PTS_PER_INCH * 100) / 100;
                case 'px':
                    return Math.round(pts / 0.75);
                default: // pt
                    return pts;
            }
        },
        
        /**
         * Convert display unit to points.
         */
        displayToPts: function(value) {
            switch (this.templateUnit) {
                case 'mm':
                    return value * this.PTS_PER_MM;
                case 'in':
                    return value * this.PTS_PER_INCH;
                case 'px':
                    return value * 0.75;
                default: // pt
                    return value;
            }
        },

        /**
         * Initialize the ticket designer.
         */
        init: function() {
            var self = this;
            
            // Check if we're on the editor page
            if (!$('#venuera-ticket-canvas').length) {
                return;
            }

            this.initCanvas();
            this.bindEvents();
            this.loadTemplateData();
            this.initColorPickers();
            // Fit the whole ticket into view on load (handles new templates too).
            this.zoomToFit();
        },

        /**
         * Initialize color pickers.
         */
        initColorPickers: function() {
            if ($.fn.wpColorPicker) {
                $('.venuera-color-picker').each(function() {
                    if ($(this).data('wp-color-picker')) {
                        $(this).wpColorPicker('destroy');
                    }
                });
                
                $('.venuera-color-picker').wpColorPicker({
                    width: 220,
                    change: function(event, ui) {
                        var $input = $(event.target);
                        var color = (ui && ui.color) ? ui.color.toString() : $input.val();
                        $input.val(color);

                        // Apply the color to the *current* active object. Using the
                        // live active object (rather than a captured closure) keeps
                        // edits correct after a QR/barcode/image regeneration swap,
                        // and firing on wpColorPicker's debounced `change` (not per
                        // keystroke) avoids regenerating bitmap elements mid-typing.
                        var name = $input.attr('name');
                        var obj = TicketDesigner.canvas && TicketDesigner.canvas.getActiveObject();
                        if (obj && name && typeof TicketDesigner.updateElementProperty === 'function') {
                            TicketDesigner.updateElementProperty(obj, name, color);
                        }
                    }
                });

                // The palette is floated as a fixed popup (see admin.css — the open
                // holder is revealed and position:fixed'd via .wp-picker-open). Here
                // we compute its left/top so it sits under the swatch, clamped into
                // the viewport. We bind a CAPTURE-PHASE native listener: wpColorPicker
                // can stopImmediatePropagation on its own (bubble-phase) click handler,
                // which would silently skip a jQuery handler bound to the same button —
                // that's why placement failed on the 2nd open. Capture always fires.
                // Bind once per button node (flag) so re-inits don't stack listeners
                // on the persistent canvas background picker. Targets ALL result
                // buttons (element fill/stroke AND the background picker).
                $('.venuera-editor-container .wp-color-result').each(function() {
                    var btn = this;
                    if (btn.tdcpBound) { return; }
                    btn.tdcpBound = true;
                    btn.addEventListener('click', function() {
                        var holder = btn.closest('.wp-picker-container').querySelector('.wp-picker-holder');
                        if (!holder) { return; }
                        var place = function() {
                            var r = btn.getBoundingClientRect();
                            var hw = holder.offsetWidth || 240;
                            var hh = holder.offsetHeight || 235;
                            var left = Math.min(r.left, window.innerWidth - hw - 8);
                            var top = Math.min(r.bottom + 6, window.innerHeight - hh - 8);
                            holder.style.left = Math.round(Math.max(8, left)) + 'px';
                            holder.style.top = Math.round(Math.max(8, top)) + 'px';
                        };
                        // Run after wpColorPicker has toggled the palette open and
                        // laid it out (the swatch itself shifts as the input-wrap
                        // appears, so re-place a couple of times as it settles).
                        setTimeout(place, 0);
                        setTimeout(place, 60);
                        setTimeout(place, 160);
                    }, true);
                });
            }
        },

        /**
         * Show notification.
         * 
         * @param {string} message Message to display.
         * @param {string} type    Type: success, error, warning.
         */
        showNotification: function(message, type) {
            var $notification = $('<div class="venuera-notification ' + type + '">' + message + '</div>');
            $('body').append($notification);

            setTimeout(function() {
                $notification.fadeOut(function() {
                    $(this).remove();
                });
            }, 3000);
        },

        /**
         * Mark template as dirty (has unsaved changes).
         */
        markDirty: function() {
            this.isDirty = true;
        },

        /**
         * Mark template as clean (saved).
         */
        markClean: function() {
            this.isDirty = false;
        },

        /**
         * Update zoom display.
         */
        updateZoomDisplay: function() {
            // Show the REAL on-screen scale (the fabric viewport zoom), not the
            // internal user-zoom multiplier. So "Fit to Screen" on a large ticket
            // honestly reads e.g. 56% (fitted/shrunk to the window) and 100% means
            // the ticket is shown at its true print size.
            var scale = (this.canvas && typeof this.canvas.getZoom === 'function')
                ? this.canvas.getZoom()
                : this.currentZoom;
            $('#venuera-zoom-level').text(Math.round(scale * 100) + '%');
        },

        /**
         * Update size display (shows print dimensions).
         */
        updateSizeDisplay: function() {
            var widthIn = this.templateWidth / this.PTS_PER_INCH;
            var heightIn = this.templateHeight / this.PTS_PER_INCH;
            
            // Format nicely
            var widthStr = widthIn % 1 === 0 ? widthIn : widthIn.toFixed(2).replace(/\.?0+$/, '');
            var heightStr = heightIn % 1 === 0 ? heightIn : heightIn.toFixed(2).replace(/\.?0+$/, '');
            
            $('#venuera-size-display').text(widthStr + '" × ' + heightStr + '"');
        },

        /**
         * Get sample data for preview.
         * 
         * @param {string} field Data field name.
         * @return {string} Sample value.
         */
        getSampleData: function(field) {
            var sampleData = venueraTicketDesigner.sampleData || {};
            if (sampleData[field]) { return sampleData[field]; }
            // Custom attendee fields don't have sample values; use their
            // friendly label from the customFields cache (seeded at boot from
            // preResolvedCustomFields) so the canvas shows "[Iskustvo]"
            // instead of the raw "[attendee_field_<uuid>]".
            if (field && field.indexOf('attendee_field_') === 0 && this.customFields) {
                var match = this.customFields.filter(function(f) { return f && f.id === field; })[0];
                if (match && match.label) {
                    return '[' + match.label + ']';
                }
            }
            return '[' + field + ']';
        },

        /**
         * Ensure a (web) font family is loaded before fabric measures/paints it,
         * so text reflows with correct metrics. Loads the common weights/styles.
         *
         * @param {string}   family Font family name.
         * @param {Function} [cb]   Called when loading settles (success or not).
         */
        ensureFontLoaded: function(family, cb) {
            if (!family || !document.fonts || !document.fonts.load) {
                if (cb) { cb(); }
                return;
            }
            try {
                Promise.all([
                    document.fonts.load('400 16px "' + family + '"'),
                    document.fonts.load('700 16px "' + family + '"'),
                    document.fonts.load('italic 400 16px "' + family + '"')
                ]).then(function() { if (cb) { cb(); } })
                  .catch(function() { if (cb) { cb(); } });
            } catch (e) {
                if (cb) { cb(); }
            }
        },

        /**
         * Generate unique element ID.
         *
         * @param {string} type Element type.
         * @return {string} Unique ID.
         */
        generateElementId: function(type) {
            return type + '_' + Date.now() + '_' + Math.random().toString(36).substr(2, 9);
        },

        /**
         * Hex to RGBA conversion.
         * 
         * @param {string} hex   Hex color.
         * @param {number} alpha Alpha value.
         * @return {string} RGBA color string.
         */
        hexToRgba: function(hex, alpha) {
            if (!hex) return 'rgba(0,0,0,' + alpha + ')';
            
            hex = hex.replace('#', '');
            var r = parseInt(hex.slice(0, 2), 16);
            var g = parseInt(hex.slice(2, 4), 16);
            var b = parseInt(hex.slice(4, 6), 16);
            return 'rgba(' + r + ', ' + g + ', ' + b + ', ' + alpha + ')';
        }
    };

    // Initialize on document ready
    $(document).ready(function() {
        if ($('#venuera-ticket-canvas').length) {
            TicketDesigner.init();
        }
    });

    // Warn about unsaved changes
    $(window).on('beforeunload', function() {
        if (TicketDesigner.isDirty) {
            return venueraTicketDesigner.strings.unsavedChanges;
        }
    });

})(jQuery);

