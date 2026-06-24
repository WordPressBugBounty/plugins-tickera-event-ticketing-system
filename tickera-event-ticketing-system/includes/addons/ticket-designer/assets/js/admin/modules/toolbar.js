/**
 * Venuera Ticket Designer - Toolbar Module
 * 
 * Handles toolbar interactions and element panel.
 */

(function($) {
    'use strict';

    if (typeof TicketDesigner === 'undefined') {
        return;
    }

    /**
     * Bind toolbar events.
     */
    TicketDesigner.bindEvents = function() {
        var self = this;

        // Populate the Add-Elements menu from the full element registry so EVERY
        // registered element (all categories) is addable — not just the 8 fallback
        // buttons. The element modules register on script load, before this runs.
        self.buildElementPanel();

        // Turn the buttons' native (delayed) title tooltips into instant, styled
        // captions so every tool clearly shows what it does.
        self.initToolTooltips();

        // Element buttons - click to add
        $(document).on('click', '.venuera-element-btn', function() {
            var elementId = $(this).data('element');
            if (elementId) {
                self.addElement(elementId);
            }
        });

        // Populate and wire the "Custom fields for" context selector.
        self.initFieldContextSelector();

        // Size selector (hidden select for backwards compatibility)
        $('#venuera-ticket-size').on('change', function() {
            var value = $(this).val();
            
            if (value === 'custom') {
                // Legacy back-compat inputs stay hidden; custom size is set via
                // the modal (#venuera-custom-size-modal), not this inline block.
                // Show current size in current unit
                $('#venuera-ticket-width').val(self.ptsToDisplay(self.templateWidth));
                $('#venuera-ticket-height').val(self.ptsToDisplay(self.templateHeight));
            } else {
                $('.venuera-custom-size').hide();
                
                var preset = self.sizePresets[value];
                if (preset) {
                    self.setCanvasSize(preset.width, preset.height);
                }
            }
            self.updateSizeDisplay();
        });

        // Size options in floating toolbar dropdown
        $(document).on('click', '.venuera-size-option', function() {
            var size = $(this).data('size');
            if (size === 'custom') {
                self.showCustomSizeModal();
            } else {
                $('#venuera-ticket-size').val(size).trigger('change');
            }
        });

        // Custom size inputs
        $('#venuera-ticket-width, #venuera-ticket-height').on('change', function() {
            var widthInput = parseFloat($('#venuera-ticket-width').val()) || 432;
            var heightInput = parseFloat($('#venuera-ticket-height').val()) || 180;
            
            // Convert to points
            var widthPts = self.displayToPts(widthInput);
            var heightPts = self.displayToPts(heightInput);
            
            self.setCanvasSize(widthPts, heightPts);
            self.updateSizeDisplay();
        });

        // Unit selector
        $('#venuera-ticket-unit').on('change', function() {
            var oldUnit = self.templateUnit;
            self.templateUnit = $(this).val();
            
            // Update displayed values
            $('#venuera-ticket-width').val(self.ptsToDisplay(self.templateWidth));
            $('#venuera-ticket-height').val(self.ptsToDisplay(self.templateHeight));
            self.updateSizeDisplay();
        });

        // Zoom controls
        $('#venuera-zoom-in').on('click', function() {
            self.zoomIn();
        });

        $('#venuera-zoom-out').on('click', function() {
            self.zoomOut();
        });

        $('#venuera-zoom-fit').on('click', function() {
            self.zoomToFit();
        });

        // Clicking the zoom percentage resets the view to fit the screen.
        $('#venuera-zoom-level').on('click', function() {
            self.zoomToFit();
        });

        // Grid and snap
        $('#venuera-toggle-grid').on('click', function() {
            self.toggleGrid();
        });

        $('#venuera-toggle-snap').on('click', function() {
            self.toggleSnapToGrid();
        });

        // Off-canvas (onion) view toggle.
        $('#venuera-toggle-onion').on('click', function() {
            if (typeof self.toggleOnionView === 'function') {
                self.toggleOnionView();
            }
        });

        // Undo/Redo
        $('#venuera-undo').on('click', function() {
            self.undo();
        });

        $('#venuera-redo').on('click', function() {
            self.redo();
        });

        // Delete
        $('#venuera-delete-element').on('click', function() {
            self.deleteSelectedElement();
        });

        // Layer controls
        $('#venuera-bring-front').on('click', function() {
            self.bringToFront();
        });

        $('#venuera-send-back').on('click', function() {
            self.sendToBack();
        });

        // Save button
        $('#venuera-save-template').on('click', function() {
            self.saveTemplate();
        });

        // Preview button
        $('#venuera-preview-template').on('click', function() {
            self.showPreview();
        });

        // Modal close
        $('.venuera-modal-close').on('click', function() {
            $(this).closest('.venuera-modal').hide();
        });

        // Click outside modal to close
        $('.venuera-modal').on('click', function(e) {
            if (e.target === this) {
                $(this).hide();
            }
        });
    };

    /**
     * Convert native `title` tooltips on the toolbar(s) into instant styled
     * captions. The title is moved to `data-tooltip` (rendered via CSS on hover)
     * and mirrored to `aria-label` for accessibility, and the native title is
     * removed so the slow browser tooltip does not also appear.
     */
    TicketDesigner.initToolTooltips = function() {
        $('#venuera-floating-toolbar [title], #venuera-mini-toolbar [title]').each(function() {
            var $el = $(this);
            var t = $el.attr('title');
            if (t) {
                $el.attr('data-tooltip', t);
                if (!$el.attr('aria-label')) {
                    $el.attr('aria-label', t);
                }
                $el.removeAttr('title');
            }
        });
    };

    /**
     * Populate and wire the editor-header "Custom fields for" context selector.
     *
     * Options are populated from the localized fieldContexts (events + ticket-type
     * products). Selecting a context fetches that context's attendee fields via
     * the tc_designer_get_custom_fields AJAX action and refreshes the field picker.
     */
    TicketDesigner.initFieldContextSelector = function() {
        var self = this;
        var $select = $('#venuera-field-context');

        if (!$select.length) {
            return;
        }

        var contexts = (venueraTicketDesigner && venueraTicketDesigner.fieldContexts) || [];

        contexts.forEach(function(ctx) {
            if (!ctx || !ctx.id) {
                return;
            }
            // Encode the context type into the option value: "event:12" / "product:34".
            var value = ctx.type + ':' + ctx.id;
            $select.append('<option value="' + value + '">' + (ctx.label || value) + '</option>');
        });

        $select.on('change', function() {
            var value = $(this).val();
            if (!value) {
                self.customFields = [];
                if (typeof self.refreshCustomFieldOptions === 'function') {
                    self.refreshCustomFieldOptions();
                }
                return;
            }

            var parts = value.split(':');
            var type = parts[0];
            var id = parseInt(parts[1], 10) || 0;

            var context = {
                eventId: type === 'event' ? id : 0,
                productId: type === 'product' ? id : 0
            };

            if (typeof self.loadCustomFields === 'function') {
                self.loadCustomFields(context);
            }
        });
    };

    /**
     * Populate the Add-Elements dropdown from the registry, grouped by category,
     * so every registered element is addable. Falls back silently if the registry
     * or the menu container isn't present.
     */
    TicketDesigner.buildElementPanel = function() {
        if (!this.ElementRegistry) { return; }
        var $menu = $('.venuera-elements-menu');
        if (!$menu.length) { return; }

        var categories = this.ElementRegistry.getCategoriesWithElements();
        if (!categories.length) { return; }

        $menu.empty();

        categories.forEach(function(category, idx) {
            if (idx > 0) {
                $menu.append('<div class="venuera-dropdown-divider"></div>');
            }
            $menu.append('<div class="venuera-dropdown-header">' + category.label + '</div>');

            for (var elementId in category.elements) {
                if (!category.elements.hasOwnProperty(elementId)) { continue; }
                var element = category.elements[elementId];
                // data-category lets the stylesheet tint each section's
                // icon chip distinctly (data / attendee / codes / text /
                // media / shapes), so the picker reads at a glance.
                var $btn = $('<button type="button" class="venuera-dropdown-item venuera-element-btn">')
                    .attr('data-element', elementId)
                    .attr('data-category', category.id)
                    .attr('title', element.description || element.label)
                    .append('<span class="dashicons dashicons-' + (element.icon || 'marker') + '"></span>')
                    .append('<span>' + element.label + '</span>');
                $menu.append($btn);
            }
        });

        // Convert the freshly-built element buttons' titles into instant captions
        // too (buildElementPanel can run after the initial tooltip pass).
        if (typeof this.initToolTooltips === 'function') {
            this.initToolTooltips();
        }
    };

})(jQuery);

