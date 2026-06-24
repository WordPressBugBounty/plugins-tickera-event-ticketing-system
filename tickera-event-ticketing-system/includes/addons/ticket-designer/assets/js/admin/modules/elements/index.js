/**
 * Venuera Ticket Designer - Elements Registry
 * 
 * Central registry for all ticket elements.
 * Elements are organized by category and loaded from separate files.
 */

(function($) {
    'use strict';

    if (typeof TicketDesigner === 'undefined') {
        return;
    }

    /**
     * Element Registry - manages all available elements.
     */
    TicketDesigner.ElementRegistry = {
        
        // Registered element types
        elements: {},
        
        // Element categories.
        //
        // The old "Event & Ticket Data" bucket has been split into smaller,
        // more scannable groups (Event / Venue / Ticket / Order / Venue)
        // so users can find a specific field without skimming the whole list.
        // "Attendee" still holds the per-ticket standard fields (Name /
        // Email / Phone — resolved at checkout via the standard-fields
        // system with billing fallback) plus the dynamic Custom Field bound
        // to the Attendee Fields addon.
        categories: {
            event: {
                label: 'Event',
                icon: 'calendar-alt',
                order: 1
            },
            venue: {
                label: 'Venue',
                icon: 'location',
                order: 2
            },
            ticket: {
                label: 'Ticket',
                icon: 'tickets-alt',
                order: 3
            },
            order: {
                label: 'Order',
                icon: 'clipboard',
                order: 4
            },
            venue: {
                label: 'Venue',
                icon: 'screenoptions',
                order: 5
            },
            attendee: {
                label: 'Attendee',
                icon: 'admin-users',
                order: 6
            },
            codes: {
                label: 'Codes',
                icon: 'grid-view',
                order: 7
            },
            text: {
                label: 'Text',
                icon: 'editor-textcolor',
                order: 8
            },
            media: {
                label: 'Media',
                icon: 'format-image',
                order: 9
            },
            shapes: {
                label: 'Shapes',
                icon: 'admin-customizer',
                order: 10
            },
            seating: {
                label: 'Seating',
                icon: 'screenoptions',
                order: 11
            },
            woo: {
                label: 'WooCommerce',
                icon: 'cart',
                order: 12
            },
            addons: {
                label: 'Add-on Fields',
                icon: 'admin-plugins',
                order: 13
            }
        },

        /**
         * Register an element type.
         * 
         * @param {string} id      Unique element ID.
         * @param {Object} config  Element configuration.
         */
        register: function(id, config) {
            this.elements[id] = $.extend({
                id: id,
                label: id,
                // Default category for legacy registrations; the new
                // subdivided categories are picked explicitly per element.
                category: 'event',
                icon: 'marker',
                description: '',
                baseType: 'text',
                defaults: {},
                create: null,
                getProperties: null,
                updateFromProperties: null,
                render: null
            }, config);
        },

        /**
         * Get element by ID.
         * 
         * @param {string} id Element ID.
         * @return {Object|null} Element config or null.
         */
        get: function(id) {
            return this.elements[id] || null;
        },

        /**
         * Get all elements.
         * 
         * @return {Object} All registered elements.
         */
        getAll: function() {
            return this.elements;
        },

        /**
         * Get elements by category.
         * 
         * @param {string} category Category ID.
         * @return {Object} Elements in category.
         */
        getByCategory: function(category) {
            var filtered = {};
            for (var id in this.elements) {
                if (this.elements[id].category === category) {
                    filtered[id] = this.elements[id];
                }
            }
            return filtered;
        },

        /**
         * Get categories with their elements.
         * 
         * @return {Array} Sorted categories with elements.
         */
        getCategoriesWithElements: function() {
            var self = this;
            var result = [];
            
            // Sort categories by order
            var sortedCategories = Object.keys(this.categories).sort(function(a, b) {
                return self.categories[a].order - self.categories[b].order;
            });
            
            sortedCategories.forEach(function(catId) {
                var elements = self.getByCategory(catId);
                if (Object.keys(elements).length > 0) {
                    result.push({
                        id: catId,
                        label: self.categories[catId].label,
                        icon: self.categories[catId].icon,
                        elements: elements
                    });
                }
            });
            
            return result;
        },

        /**
         * Aliases mapping toolbar/legacy element ids to registered element ids.
         *
         * The add-element menu (admin PHP) emits some ids that are not registered
         * directly (e.g. "image", "line"); map them to a real registered creator.
         * NOTE: do not rename existing registry ids — only alias toward them.
         */
        aliases: {
            image: 'custom_image',
            line: 'horizontal_line'
        },

        /**
         * Resolve a possibly-aliased element id to a registered id.
         *
         * @param {string} id Element ID (may be an alias).
         * @return {string} Resolved element ID.
         */
        resolveId: function(id) {
            return this.aliases[id] || id;
        },

        /**
         * Create element on canvas.
         *
         * @param {string} elementId Element type ID.
         * @param {Object} options   Optional override options.
         * @return {fabric.Object|null} Created fabric object or null.
         */
        createElement: function(elementId, options) {
            // Map legacy/toolbar aliases (image, line, ...) to registered ids.
            elementId = this.resolveId(elementId);

            var element = this.get(elementId);
            if (!element) {
                console.error('Unknown element type:', elementId);
                return null;
            }

            options = options || {};
            var defaults = $.extend({}, element.defaults, options);
            
            // Generate unique ID
            defaults.id = defaults.id || TicketDesigner.generateElementId(elementId);
            
            // Use element's create function or fall back to base type
            if (typeof element.create === 'function') {
                return element.create(defaults);
            }
            
            // Fall back to base type creator
            return this.createByBaseType(element.baseType, elementId, defaults);
        },

        /**
         * Create element by base type.
         * 
         * @param {string} baseType  Base type (text, image, code, shape).
         * @param {string} elementId Element type ID.
         * @param {Object} options   Element options.
         * @return {fabric.Object|null} Created fabric object.
         */
        createByBaseType: function(baseType, elementId, options) {
            switch (baseType) {
                case 'text':
                    return TicketDesigner.BaseElements.createText(elementId, options);
                case 'image':
                    return TicketDesigner.BaseElements.createImage(elementId, options);
                case 'qrcode':
                    return TicketDesigner.BaseElements.createQRCode(elementId, options);
                case 'barcode':
                    return TicketDesigner.BaseElements.createBarcode(elementId, options);
                case 'rectangle':
                    return TicketDesigner.BaseElements.createRectangle(elementId, options);
                case 'line':
                    return TicketDesigner.BaseElements.createLine(elementId, options);
                default:
                    console.error('Unknown base type:', baseType);
                    return null;
            }
        }
    };

    /**
     * Add element to canvas.
     * 
     * @param {string} elementId Element type ID.
     * @param {Object} options   Optional options.
     */
    TicketDesigner.addElement = function(elementId, options) {
        var fabricObj = this.ElementRegistry.createElement(elementId, options);

        if (fabricObj) {
            // Some element types carry hardcoded default positions sized for a
            // larger ticket (e.g. QR x:480, barcode x:400/y:200, terms y:230),
            // so on a smaller ticket they'd spawn outside the ticket area. When
            // the element is added fresh (no explicit position given), nudge it
            // back inside the ticket bounds so it's always visible on the canvas.
            if (!options || (options.x == null && options.y == null)) {
                this.clampElementIntoTicket(fabricObj);
            }
            this.canvas.add(fabricObj);
            this.canvas.setActiveObject(fabricObj);
            this.canvas.renderAll();
            this.markDirty();
            this.saveToHistory();
        }
    };

    /**
     * Shift an object so its bounding box sits fully within the ticket
     * (0,0 → templateWidth,templateHeight). Origin-agnostic: works for text,
     * images, QR/barcode groups, shapes. Objects larger than the ticket pin to
     * the top-left corner.
     */
    TicketDesigner.clampElementIntoTicket = function(obj) {
        if (!obj || typeof obj.getBoundingRect !== 'function') { return; }
        var tw = this.templateWidth, th = this.templateHeight;
        if (!tw || !th) { return; }

        var br = obj.getBoundingRect(true); // scene coords, ignores zoom/pan

        // Some structural/decorative defaults are sized for a larger reference
        // ticket and are WIDER/TALLER than this ticket (full-width header/footer
        // bands, ticket border, full-height sidebar/lines). Shifting can't make
        // something bigger than the ticket fit, so shrink it to fit first — which
        // is also the intended look (a full-width band should match the ticket).
        var sx = br.width  > tw ? tw / br.width  : 1;
        var sy = br.height > th ? th / br.height : 1;
        if (sx < 1 || sy < 1) {
            obj.scaleX = (obj.scaleX || 1) * sx;
            obj.scaleY = (obj.scaleY || 1) * sy;
            obj.setCoords();
            br = obj.getBoundingRect(true); // recompute after resize
        }

        var nl = Math.max(0, Math.min(br.left, tw - br.width));
        var nt = Math.max(0, Math.min(br.top, th - br.height));

        if (nl !== br.left || nt !== br.top) {
            obj.left += (nl - br.left);
            obj.top  += (nt - br.top);
            obj.setCoords();
        }
    };

})(jQuery);

