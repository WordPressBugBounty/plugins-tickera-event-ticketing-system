/**
 * Floating Toolbar Module
 * 
 * Handles floating toolbar drag, dock, and collapse functionality
 */

(function($) {
	function T( k, fb ) { return ( (window.venueraTicketDesigner && window.venueraTicketDesigner.strings) && (window.venueraTicketDesigner && window.venueraTicketDesigner.strings)[ k ] ) || fb; }

    'use strict';

    if (typeof window.TicketDesigner === 'undefined') {
        console.error('TicketDesigner must be loaded before modules');
        return;
    }

    // Toolbar state
    var toolbarState = {
        isDragging: false,
        startX: 0,
        startY: 0,
        startLeft: 0,
        startTop: 0,
        position: 'left',
        isCollapsed: false,
        snapThreshold: 40
    };

    // Extend TicketDesigner with toolbar methods
    $.extend(TicketDesigner, {
        initFloatingToolbar: function() {
            var self = this;
            var $toolbar = $('#venuera-floating-toolbar');
            var $miniToolbar = $('#venuera-mini-toolbar');
            var $dragHandle = $toolbar.find('.venuera-toolbar-drag-handle');
            var $container = $('.venuera-canvas-wrapper');

            // Load saved position
            this.loadToolbarPosition();

            // Drag functionality
            $dragHandle.on('mousedown', function(e) {
                e.preventDefault();
                self.startToolbarDrag(e, $toolbar, $container);
            });

            // Collapse/Expand
            $('#venuera-toolbar-collapse').on('click', function() {
                self.collapseToolbar();
            });

            $('#venuera-toolbar-expand').on('click', function() {
                self.expandToolbar();
            });

            // Tool buttons
            $toolbar.find('.venuera-ftool-btn[data-tool]').on('click', function() {
                var tool = $(this).data('tool');
                self.setActiveTool(tool);
                $toolbar.find('.venuera-ftool-btn[data-tool]').removeClass('active');
                $(this).addClass('active');
            });

            // Element buttons in dropdown
            $toolbar.find('.venuera-element-btn[data-element]').on('click', function() {
                var elementType = $(this).data('element');
                self.addElement(elementType);
            });

            // Size options
            $toolbar.find('.venuera-size-option').on('click', function() {
                var size = $(this).data('size');
                if (size === 'custom') {
                    self.showCustomSizeModal();
                } else {
                    $('#venuera-ticket-size').val(size).trigger('change');
                }
            });

            // Zoom level click to reset
            $('#venuera-zoom-level').on('click', function() {
                self.zoomToFit();
            });

            // Click-to-toggle dropdowns (works on touch where :hover doesn't).
            $toolbar.find('.venuera-dropdown-trigger').on('click', function(e) {
                e.stopPropagation();
                var $dropdown = $(this).closest('.venuera-ftool-dropdown');
                var isOpen = $dropdown.hasClass('open');
                $toolbar.find('.venuera-ftool-dropdown').removeClass('open');
                if (!isOpen) {
                    $dropdown.addClass('open');
                }
            });

            // Close any open dropdown when clicking outside / picking an item.
            $(document).on('click.tdDropdown', function(e) {
                if (!$(e.target).closest('.venuera-ftool-dropdown').length) {
                    $toolbar.find('.venuera-ftool-dropdown').removeClass('open');
                }
            });

            // Delegated so it also covers dropdown items rendered later by
            // buildElementPanel() from the element registry.
            $toolbar.on('click', '.venuera-dropdown-item', function() {
                $toolbar.find('.venuera-ftool-dropdown').removeClass('open');
            });

            // Layer single-step controls (front/back are bound in toolbar.js).
            $('#venuera-bring-forward').on('click', function() {
                self.bringForward();
            });

            $('#venuera-send-backward').on('click', function() {
                self.sendBackward();
            });

            // Ticket background-color control.
            self.initBackgroundColorControl();

            // Mark toolbar as ready (triggers animation)
            setTimeout(function() {
                $toolbar.addClass('ready');
            }, 100);
        },

        /**
         * Wire the ticket background-color picker. Uses the WP color picker when
         * available (matching the rest of the editor), falling back to a plain
         * text/color input. Changing it updates the canvas background and marks
         * the template dirty so it persists on save.
         */
        initBackgroundColorControl: function() {
            var self = this;
            var $bg = $('#venuera-bg-color');

            if (!$bg.length) {
                return;
            }

            // Reflect the current (possibly loaded) template background.
            $bg.val(this.templateBackground || '#ffffff');

            var applyBackground = function(color) {
                if (color) {
                    self.setCanvasBackground(color);
                }
            };

            if ($.fn.wpColorPicker) {
                $bg.wpColorPicker({
                    width: 220,
                    defaultColor: this.templateBackground || '#ffffff',
                    change: function(event, ui) {
                        applyBackground(ui.color.toString());
                    },
                    clear: function() {
                        applyBackground('#ffffff');
                    }
                });
            } else {
                $bg.on('change input', function() {
                    applyBackground($(this).val());
                });
            }
        },

        startToolbarDrag: function(e, $toolbar, $container) {
            var self = this;
            var containerOffset = $container.offset();
            var containerWidth = $container.width();
            var containerHeight = $container.height();

            toolbarState.isDragging = true;
            toolbarState.startX = e.clientX;
            toolbarState.startY = e.clientY;

            var rect = $toolbar[0].getBoundingClientRect();
            toolbarState.startLeft = rect.left - containerOffset.left;
            toolbarState.startTop = rect.top - containerOffset.top;

            $toolbar.addClass('dragging');
            $toolbar.attr('data-position', 'floating');

            $(document).on('mousemove.toolbarDrag', function(e) {
                self.moveToolbar(e, $toolbar, $container, containerOffset, containerWidth, containerHeight);
            });

            $(document).on('mouseup.toolbarDrag', function() {
                self.endToolbarDrag($toolbar, $container, containerOffset, containerWidth, containerHeight);
            });
        },

        moveToolbar: function(e, $toolbar, $container, containerOffset, containerWidth, containerHeight) {
            if (!toolbarState.isDragging) return;

            var deltaX = e.clientX - toolbarState.startX;
            var deltaY = e.clientY - toolbarState.startY;

            var newLeft = toolbarState.startLeft + deltaX;
            var newTop = toolbarState.startTop + deltaY;

            // Constrain to container
            var toolbarWidth = $toolbar.outerWidth();
            var toolbarHeight = $toolbar.outerHeight();

            newLeft = Math.max(0, Math.min(newLeft, containerWidth - toolbarWidth));
            newTop = Math.max(0, Math.min(newTop, containerHeight - toolbarHeight));

            $toolbar.css({
                left: newLeft + 'px',
                top: newTop + 'px',
                right: 'auto',
                bottom: 'auto',
                transform: 'none'
            });

            // Show snap feedback
            var threshold = toolbarState.snapThreshold;
            var centerX = newLeft + toolbarWidth / 2;
            var centerY = newTop + toolbarHeight / 2;

            $toolbar.removeClass('snap-top snap-left snap-bottom');

            if (newTop < threshold && Math.abs(centerX - containerWidth / 2) < containerWidth / 3) {
                $toolbar.addClass('snap-top');
            } else if (newLeft < threshold && Math.abs(centerY - containerHeight / 2) < containerHeight / 3) {
                $toolbar.addClass('snap-left');
            } else if (newTop > containerHeight - toolbarHeight - threshold && Math.abs(centerX - containerWidth / 2) < containerWidth / 3) {
                $toolbar.addClass('snap-bottom');
            }
        },

        endToolbarDrag: function($toolbar, $container, containerOffset, containerWidth, containerHeight) {
            $(document).off('.toolbarDrag');
            $toolbar.removeClass('dragging snap-top snap-left snap-bottom');

            if (!toolbarState.isDragging) return;
            toolbarState.isDragging = false;

            var toolbarWidth = $toolbar.outerWidth();
            var toolbarHeight = $toolbar.outerHeight();
            var rect = $toolbar[0].getBoundingClientRect();
            var left = rect.left - containerOffset.left;
            var top = rect.top - containerOffset.top;
            var threshold = toolbarState.snapThreshold;

            var centerX = left + toolbarWidth / 2;
            var centerY = top + toolbarHeight / 2;

            // Snap to dock positions
            if (top < threshold && Math.abs(centerX - containerWidth / 2) < containerWidth / 3) {
                this.setToolbarPosition($toolbar, 'top');
            } else if (left < threshold && Math.abs(centerY - containerHeight / 2) < containerHeight / 3) {
                this.setToolbarPosition($toolbar, 'left');
            } else if (top > containerHeight - toolbarHeight - threshold && Math.abs(centerX - containerWidth / 2) < containerWidth / 3) {
                this.setToolbarPosition($toolbar, 'bottom');
            } else {
                toolbarState.position = 'floating';
                this.saveToolbarPosition(left, top, 'floating');
            }
        },

        setToolbarPosition: function($toolbar, position) {
            toolbarState.position = position;

            $toolbar.css({
                left: '',
                top: '',
                right: '',
                bottom: '',
                transform: ''
            });

            $toolbar.attr('data-position', position);
            this.saveToolbarPosition(0, 0, position);
        },

        collapseToolbar: function() {
            var $toolbar = $('#venuera-floating-toolbar');
            var $miniToolbar = $('#venuera-mini-toolbar');

            toolbarState.isCollapsed = true;
            $toolbar.hide();
            $miniToolbar.show();

            localStorage.setItem('tdToolbarCollapsed', 'true');
        },

        expandToolbar: function() {
            var $toolbar = $('#venuera-floating-toolbar');
            var $miniToolbar = $('#venuera-mini-toolbar');

            toolbarState.isCollapsed = false;
            $miniToolbar.hide();
            $toolbar.show();

            localStorage.setItem('tdToolbarCollapsed', 'false');
        },

        loadToolbarPosition: function() {
            var $toolbar = $('#venuera-floating-toolbar');
            var savedData = localStorage.getItem('tdToolbarPosition');
            var isCollapsed = localStorage.getItem('tdToolbarCollapsed') === 'true';

            if (savedData) {
                try {
                    var data = JSON.parse(savedData);
                    toolbarState.position = data.position || 'left';

                    if (data.position === 'floating' && data.left !== undefined && data.top !== undefined) {
                        $toolbar.attr('data-position', 'floating');
                        $toolbar.css({
                            left: data.left + 'px',
                            top: data.top + 'px',
                            right: 'auto',
                            bottom: 'auto',
                            transform: 'none'
                        });
                    } else {
                        $toolbar.attr('data-position', data.position || 'left');
                    }
                } catch (e) {
                    $toolbar.attr('data-position', 'left');
                }
            }

            if (isCollapsed) {
                this.collapseToolbar();
            }
        },

        saveToolbarPosition: function(left, top, position) {
            var data = {
                position: position,
                left: left,
                top: top
            };
            localStorage.setItem('tdToolbarPosition', JSON.stringify(data));
        },

        setActiveTool: function(tool) {
            this.currentTool = tool;

            // Toggle a body-level class so the wrapper CSS can flip the
            // cursor on the WHOLE canvas area (not just the fabric layer).
            // Makes the active hand tool obvious before the user starts a
            // drag — same affordance the Venue Designer gives.
            var $container = jQuery('#venuera-canvas-container');
            $container.toggleClass('tool-pan', tool === 'pan');

            if (tool === 'pan') {
                this.canvas.defaultCursor = 'grab';
                this.canvas.hoverCursor = 'grab';
                this.canvas.selection = false;
                this.canvas.forEachObject(function(obj) {
                    obj.selectable = false;
                    obj.evented = false;
                });
            } else {
                this.canvas.defaultCursor = 'default';
                this.canvas.hoverCursor = 'move';
                this.canvas.selection = true;
                this.canvas.forEachObject(function(obj) {
                    if (!obj.isGrid) {
                        obj.selectable = true;
                        obj.evented = true;
                    }
                });
            }

            this.canvas.renderAll();
        },

        showCustomSizeModal: function() {
            // Show custom size modal
            var self = this;
            var widthPts = this.templateWidth;
            var heightPts = this.templateHeight;
            
            var html = `
                <div class="venuera-modal" id="venuera-custom-size-modal">
                    <div class="venuera-modal-content">
                        <div class="venuera-modal-header">
                            <h3>${T('customTicketSize', 'Custom Ticket Size')}</h3>
                            <button type="button" class="venuera-modal-close">&times;</button>
                        </div>
                        <div class="venuera-modal-body">
                            <div class="venuera-prop-group">
                                <label>${T('width2', 'Width')}</label>
                                <input type="number" id="custom-width" class="venuera-prop-input" value="${widthPts}" min="72" max="900" step="1">
                            </div>
                            <div class="venuera-prop-group">
                                <label>${T('height2', 'Height')}</label>
                                <input type="number" id="custom-height" class="venuera-prop-input" value="${heightPts}" min="72" max="900" step="1">
                            </div>
                            <div class="venuera-prop-group">
                                <label>${T('unit', 'Unit')}</label>
                                <select id="custom-unit" class="venuera-prop-select">
                                    <option value="pt" selected>${T('pointsPt', 'Points (pt)')}</option>
                                    <option value="in">${T('inchesIn', 'Inches (in)')}</option>
                                    <option value="mm">${T('millimetersMm', 'Millimeters (mm)')}</option>
                                </select>
                            </div>
                        </div>
                        <div class="venuera-modal-footer">
                            <button type="button" class="button venuera-modal-close">${T('cancel', 'Cancel')}</button>
                            <button type="button" class="button button-primary" id="apply-custom-size">${T('apply', 'Apply')}</button>
                        </div>
                    </div>
                </div>
            `;

            // Guard against duplicates: two '.venuera-size-option' click handlers
            // (toolbar.js delegated + floating-toolbar.js direct) both fire, so
            // this can run twice per click and append two modals with the same
            // id. Remove any existing instance, and bind to the actual appended
            // node — re-selecting by id would grab the first/stale modal and
            // leave the visible one's buttons dead (Cancel/Apply/X did nothing).
            $('#venuera-custom-size-modal').remove();

            var $modal = $(html);
            $('body').append($modal);

            $modal.find('.venuera-modal-close').on('click', function() {
                $modal.remove();
            });

            $modal.on('click', function(e) {
                if ($(e.target).is('.venuera-modal')) {
                    $modal.remove();
                }
            });

            $modal.find('#apply-custom-size').on('click', function() {
                var width = parseFloat($modal.find('#custom-width').val());
                var height = parseFloat($modal.find('#custom-height').val());
                var unit = $modal.find('#custom-unit').val();

                // Convert to points
                if (unit === 'in') {
                    width = width * 72;
                    height = height * 72;
                } else if (unit === 'mm') {
                    width = width * 2.834645669;
                    height = height * 2.834645669;
                }

                self.setCanvasSize(Math.round(width), Math.round(height));
                self.updateSizeDisplay();
                $modal.remove();
            });
        }
    });

})(jQuery);

