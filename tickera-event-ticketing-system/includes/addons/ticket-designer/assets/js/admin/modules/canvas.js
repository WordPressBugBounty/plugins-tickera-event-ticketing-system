/**
 * Venuera Ticket Designer - Canvas Module
 * 
 * Handles canvas initialization and management.
 */

(function($) {
    'use strict';

    if (typeof TicketDesigner === 'undefined') {
        return;
    }

    /**
     * Initialize the Fabric.js canvas.
     */
    TicketDesigner.initCanvas = function() {
        var self = this;
        var container = $('#venuera-canvas-container');
        var canvas = $('#venuera-ticket-canvas');

        // Set canvas dimensions (in points, will be scaled by zoom)
        canvas.attr({
            width: this.templateWidth,
            height: this.templateHeight
        });

        // Initialize Fabric.js canvas
        this.canvas = new fabric.Canvas('venuera-ticket-canvas', {
            backgroundColor: this.templateBackground,
            selection: true,
            preserveObjectStacking: true,
            renderOnAddRemove: true,
        });

        // Set up canvas event handlers
        this.canvas.on('selection:created', function(e) {
            self.onObjectSelected(e.selected[0]);
        });

        this.canvas.on('selection:updated', function(e) {
            self.onObjectSelected(e.selected[0]);
        });

        this.canvas.on('selection:cleared', function() {
            self.onSelectionCleared();
        });

        this.canvas.on('object:modified', function(e) {
            self.onObjectModified(e.target);
        });

        this.canvas.on('object:moving', function(e) {
            if (self.snapToGrid) {
                self.snapObjectToGrid(e.target);
            }
        });

        // Keyboard shortcuts
        $(document).on('keydown', function(e) {
            self.handleKeyboard(e);
        });

        // Resize canvas on window resize
        $(window).on('resize', function() {
            self.resizeCanvas();
        });

        this.resizeCanvas();

        // Wheel-zoom + space-drag pan interactions.
        this.initCanvasInteractions();

        // Reconcile grid / snap toggle buttons with the real model defaults.
        this.syncToggleStates();

        // Initialize floating toolbar after canvas is ready
        if (typeof this.initFloatingToolbar === 'function') {
            this.initFloatingToolbar();
        }
    };

    /**
     * Resize canvas to fit container while maintaining aspect ratio.
     * 1:1 mapping: canvas pixels = PDF points
     */
    TicketDesigner.resizeCanvas = function() {
        if (!this.canvas) return;

        // Off-canvas (onion) view expands the viewport to include elements that
        // sit outside the ticket; delegate to that path when it is active.
        if (this.onionView && typeof this.resizeCanvasOnion === 'function') {
            this.resizeCanvasOnion();
            return;
        }

        // currentZoom is the REAL on-screen scale (1 = actual print size). The
        // canvas element is sized to the ticket at that scale and centred in the
        // scrollable container; "Fit to Screen" (zoomToFit) chooses the scale.
        var z = this.currentZoom;

        this.canvas.setViewportTransform([z, 0, 0, z, 0, 0]);
        this.canvas.setWidth(this.templateWidth * z);
        this.canvas.setHeight(this.templateHeight * z);
        this.canvas.requestRenderAll();
    };

    /**
     * Set canvas size in points.
     * 
     * @param {number} width  Canvas width in points.
     * @param {number} height Canvas height in points.
     */
    TicketDesigner.setCanvasSize = function(width, height) {
        this.templateWidth = width;
        this.templateHeight = height;
        // Changing the format re-fits the new size into view so the whole ticket
        // is visible (zoomToFit calls resizeCanvas).
        this.zoomToFit();
        this.markDirty();
        this.updateSizeDisplay();

        // Changing the ticket size is exactly when elements can fall outside the
        // ticket, so refresh the off-canvas guides/notice if the onion view is on.
        if (this.onionView) {
            this.refreshOnionGuides();
            this.updateOnionStatus();
        }
    };

    /**
     * Set canvas background color.
     * 
     * @param {string} color Background color.
     */
    TicketDesigner.setCanvasBackground = function(color) {
        this.templateBackground = color;
        this.canvas.setBackgroundColor(color, this.canvas.renderAll.bind(this.canvas));
        this.markDirty();
        this.syncBackgroundControl(color);
    };

    /**
     * Reflect the current background color in the picker UI without re-triggering
     * its change handler (avoids feedback loops / spurious dirty marks).
     *
     * @param {string} color Background color.
     */
    TicketDesigner.syncBackgroundControl = function(color) {
        var $bg = $('#venuera-bg-color');
        if (!$bg.length || $bg.val() === color) {
            return;
        }
        $bg.val(color);
        // Keep the WP color picker swatch in sync if it's initialised.
        if ($.fn.wpColorPicker && $bg.hasClass('wp-color-picker')) {
            $bg.wpColorPicker('color', color);
        }
    };

    /**
     * Handle object selection.
     * 
     * @param {fabric.Object} obj Selected object.
     */
    TicketDesigner.onObjectSelected = function(obj) {
        // During a programmatic element swap (QR/barcode/image regeneration) the
        // remove/add cycle fires selection events; skip the full panel rebuild so
        // scroll position and any open color picker are preserved (the swap
        // re-binds the panel to the new object itself).
        if (this._swapping) {
            this.selectedElement = obj;
            return;
        }
        this.selectedElement = obj;
        this.showProperties(obj);
        $('.venuera-layer-controls').show();
    };

    /**
     * Handle selection cleared.
     */
    TicketDesigner.onSelectionCleared = function() {
        // Ignore the transient clear emitted while swapping an element in place.
        if (this._swapping) {
            return;
        }
        this.selectedElement = null;
        this.hideProperties();
        $('.venuera-layer-controls').hide();
    };

    /**
     * Handle object modification.
     * 
     * @param {fabric.Object} obj Modified object.
     */
    TicketDesigner.onObjectModified = function(obj) {
        this.markDirty();

        // CRITICAL — keep elementData in sync with what was JUST resized so
        // the PDF reads the same dimensions the user sees on canvas.
        //
        // Fabric tracks resize as a scale on the group/object — `obj.width` /
        // `obj.height` stay at the ORIGINAL intrinsic size, and only
        // `scaleX`/`scaleY` change. The PDF renderer reads concrete
        // dimensions (`size` for QR, `width`/`height` for barcode/image/
        // rect), so we must mirror the visual dimensions back into
        // elementData here.  Without this, a user-resized QR keeps its
        // original 100pt size in the PDF even though the canvas/preview
        // show the new size.
        var needsRebuild = false;
        if (obj && obj.elementData) {
            var data = obj.elementData;

            // Bake any scaleX/scaleY left over from a corner-handle resize
            // back into the object's INTRINSIC dimensions so:
            //   - elementData stores the visual values the user just set,
            //   - the PDF renderer (which reads intrinsic fontSize/width/
            //     height — not the scale factor) shows what canvas shows,
            //   - subsequent renders/serializations don't double-scale.
            //
            // For text: multiply fontSize by the scale and reset scale.
            // For rectangles/lines: set width/height to visual values and
            // reset scale.
            if ((obj.type === 'textbox' || obj.type === 'text' || obj.type === 'i-text')
                && (obj.scaleX !== 1 || obj.scaleY !== 1)) {
                var fontScale = obj.scaleY || obj.scaleX || 1;
                if (Math.abs(fontScale - 1) > 0.001) {
                    obj.set({
                        fontSize: (obj.fontSize || 14) * fontScale,
                        width:    (obj.width    || 0)  * (obj.scaleX || 1),
                        scaleX:   1,
                        scaleY:   1
                    });
                    obj.setCoords();
                }
            } else if (obj.type === 'rect' && (obj.scaleX !== 1 || obj.scaleY !== 1)) {
                obj.set({
                    width:  (obj.width  || 0) * (obj.scaleX || 1),
                    height: (obj.height || 0) * (obj.scaleY || 1),
                    scaleX: 1,
                    scaleY: 1
                });
                obj.setCoords();
            }

            if (obj.width) {
                data.width = Math.round(obj.width * obj.scaleX);
            }
            if (obj.height) {
                data.height = Math.round(obj.height * obj.scaleY);
            }
            // Text: persist the (now-baked) fontSize so the PDF reads it
            // straight from elementData.
            if (obj.type === 'textbox' || obj.type === 'text' || obj.type === 'i-text') {
                data.fontSize = obj.fontSize;
            }
            // QR stores its dimension as a single `size` field — keep it in
            // lock-step with width since the element is square (lockUniScaling).
            if (data.baseType === 'qrcode' && data.width) {
                data.size = data.width;
            }
            // QR / Barcode are fabric.Groups whose children (background
            // rect, padding, internal QR/barcode bitmap) all visually scale
            // along with the group on resize — that includes the QUIET
            // ZONE (padding). The PDF renderer treats padding as an
            // ABSOLUTE pt value baked into the element data, so a scaled
            // canvas-side padding would no longer match the PDF (and the
            // bitmap inside the card would visually shift).
            //
            // Regenerate the group from scratch using the freshly-saved
            // width/height + the user's chosen padding so the on-canvas
            // padding stays the literal pt value, exactly like the PDF.
            // Only do this when the group was actually scaled — otherwise
            // a plain move would needlessly rebuild the bitmap.
            var wasScaled = Math.abs((obj.scaleX || 1) - 1) > 0.001
                         || Math.abs((obj.scaleY || 1) - 1) > 0.001;
            if (wasScaled && (data.baseType === 'qrcode' || data.baseType === 'barcode')) {
                needsRebuild = true;
            }
        }

        if (needsRebuild) {
            var dataLocal = obj.elementData;
            if (dataLocal.baseType === 'qrcode' && TicketDesigner.BaseElements &&
                typeof TicketDesigner.BaseElements.regenerateQRCode === 'function') {
                obj = TicketDesigner.BaseElements.regenerateQRCode(obj);
            } else if (dataLocal.baseType === 'barcode' && TicketDesigner.BaseElements &&
                       typeof TicketDesigner.BaseElements.regenerateBarcode === 'function') {
                obj = TicketDesigner.BaseElements.regenerateBarcode(obj);
            }
        }

        this.saveToHistory();

        // Update properties panel if object is selected
        if (this.selectedElement === obj) {
            this.updatePositionProperties(obj);
        }

        // Keep the off-canvas outlines in sync after a move/resize.
        if (this.onionView) {
            this.refreshOnionGuides();
            this.updateOnionStatus();
        }
    };

    /**
     * Update position properties in panel.
     * 
     * @param {fabric.Object} obj Object to update.
     */
    TicketDesigner.updatePositionProperties = function(obj) {
        var $panel = $('#venuera-properties-content');
        $panel.find('input[name="x"]').val(Math.round(obj.left));
        $panel.find('input[name="y"]').val(Math.round(obj.top));
        
        if (obj.width) {
            $panel.find('input[name="width"]').val(Math.round(obj.width * obj.scaleX));
        }
        if (obj.height) {
            $panel.find('input[name="height"]').val(Math.round(obj.height * obj.scaleY));
        }
    };

    /**
     * Snap object to grid.
     * 
     * @param {fabric.Object} obj Object to snap.
     */
    TicketDesigner.snapObjectToGrid = function(obj) {
        obj.set({
            left: Math.round(obj.left / this.gridSize) * this.gridSize,
            top: Math.round(obj.top / this.gridSize) * this.gridSize
        });
    };

    /**
     * Handle keyboard shortcuts.
     * 
     * @param {Event} e Keyboard event.
     */
    TicketDesigner.handleKeyboard = function(e) {
        // Don't handle if typing in input
        if ($(e.target).is('input, textarea, select')) {
            return;
        }

        var key = e.key.toLowerCase();
        var ctrl = e.ctrlKey || e.metaKey;

        // Delete selected element
        if (key === 'delete' || key === 'backspace') {
            e.preventDefault();
            this.deleteSelectedElement();
        }

        // Undo (Ctrl+Z)
        if (ctrl && key === 'z' && !e.shiftKey) {
            e.preventDefault();
            this.undo();
        }

        // Redo (Ctrl+Y or Ctrl+Shift+Z)
        if (ctrl && (key === 'y' || (key === 'z' && e.shiftKey))) {
            e.preventDefault();
            this.redo();
        }

        // Save (Ctrl+S)
        if (ctrl && key === 's') {
            e.preventDefault();
            this.saveTemplate();
        }

        // Copy (Ctrl+C)
        if (ctrl && key === 'c') {
            e.preventDefault();
            this.copyElement();
        }

        // Paste (Ctrl+V)
        if (ctrl && key === 'v') {
            e.preventDefault();
            this.pasteElement();
        }

        // View toggles (no modifier): G = grid, S = snap, O = off-canvas/onion.
        if (!ctrl && key === 'g') {
            e.preventDefault();
            this.toggleGrid();
        }
        if (!ctrl && key === 's') {
            e.preventDefault();
            this.toggleSnapToGrid();
        }
        if (!ctrl && key === 'o' && typeof this.toggleOnionView === 'function') {
            e.preventDefault();
            this.toggleOnionView();
        }

        // Move selected element with arrow keys.
        //
        // Mouse-drag updates obj.left/top AND calls obj.setCoords()
        // internally, plus pushes a history entry on mouse-up. Arrow-key
        // moves previously only mutated left/top and rendered — without
        // setCoords() fabric kept stale internal corner/bounding caches,
        // which collided with save+reload (the saved coordinate was
        // correct but ALSO mirrored into elementData stale state, and
        // any post-move action that read aCoords saw the OLD bounding
        // box). Also push a history entry so Undo works after an arrow
        // nudge.
        if (this.selectedElement && ['arrowup', 'arrowdown', 'arrowleft', 'arrowright'].includes(key)) {
            e.preventDefault();
            var step = e.shiftKey ? 10 : 1;
            var obj = this.selectedElement;

            switch (key) {
                case 'arrowup':
                    obj.set('top', obj.top - step);
                    break;
                case 'arrowdown':
                    obj.set('top', obj.top + step);
                    break;
                case 'arrowleft':
                    obj.set('left', obj.left - step);
                    break;
                case 'arrowright':
                    obj.set('left', obj.left + step);
                    break;
            }

            // Mirror the new position into elementData (mouse-drag does
            // this via fabric's `object:modified` event listeners; arrow
            // moves bypass those, so we sync explicitly here).
            if (obj.elementData) {
                obj.elementData.x = Math.round(obj.left);
                obj.elementData.y = Math.round(obj.top);
            }

            // Recompute internal corner cache so subsequent selection /
            // snapping / bounds checks see the new position.
            obj.setCoords();

            this.canvas.renderAll();
            this.updatePositionProperties(obj);
            this.markDirty();
            if (typeof this.saveToHistory === 'function') {
                this.saveToHistory();
            }
        }
    };

    /**
     * Delete selected element.
     */
    TicketDesigner.deleteSelectedElement = function() {
        if (this.selectedElement) {
            this.canvas.remove(this.selectedElement);
            this.selectedElement = null;
            this.onSelectionCleared();
            this.markDirty();
            this.saveToHistory();
        }
    };

    /**
     * Copy clipboard data.
     */
    TicketDesigner.clipboardData = null;

    /**
     * Copy selected element.
     */
    TicketDesigner.copyElement = function() {
        if (this.selectedElement) {
            this.selectedElement.clone(function(cloned) {
                TicketDesigner.clipboardData = cloned;
            });
        }
    };

    /**
     * Paste copied element.
     */
    TicketDesigner.pasteElement = function() {
        if (this.clipboardData) {
            this.clipboardData.clone(function(cloned) {
                cloned.set({
                    left: cloned.left + 20,
                    top: cloned.top + 20,
                    evented: true,
                });
                
                // Generate new ID
                if (cloned.elementData) {
                    cloned.elementData.id = TicketDesigner.generateElementId(cloned.elementData.type);
                }
                
                TicketDesigner.canvas.add(cloned);
                TicketDesigner.canvas.setActiveObject(cloned);
                TicketDesigner.canvas.renderAll();
                TicketDesigner.markDirty();
                TicketDesigner.saveToHistory();
            });
        }
    };

    /**
     * Zoom in (user zoom, on top of base WYSIWYG zoom).
     */
    TicketDesigner.zoomIn = function() {
        this.currentZoom = Math.min(this.currentZoom + 0.1, 4);
        this.resizeCanvas();
        this.updateZoomDisplay();
    };

    /**
     * Zoom out.
     */
    TicketDesigner.zoomOut = function() {
        this.currentZoom = Math.max(this.currentZoom - 0.1, 0.25);
        this.resizeCanvas();
        this.updateZoomDisplay();
    };

    /**
     * Zoom to fit (100% = actual print size at 96 DPI).
     */
    TicketDesigner.zoomToFit = function() {
        var container = $('#venuera-canvas-container');
        var cw = container.width() - 60;
        var ch = container.height() - 60;

        // Scale the WHOLE ticket to fill the visible area — enlarge small tickets
        // and shrink large ones (true "fit to screen", no 100% cap).
        var fit = Math.min(cw / this.templateWidth, ch / this.templateHeight);
        if (!isFinite(fit) || fit <= 0) {
            fit = 1;
        }
        fit = Math.max(0.1, Math.min(fit, 4));

        this.currentZoom = Math.round(fit * 100) / 100;
        this.resizeCanvas();
        this.updateZoomDisplay();
        if (typeof this.centerCanvasScroll === 'function') {
            this.centerCanvasScroll();
        }
    };

    /**
     * Toggle grid display.
     */
    TicketDesigner.toggleGrid = function() {
        this.showGrid = !this.showGrid;
        
        if (this.showGrid) {
            this.drawGrid();
            $('#venuera-toggle-grid').addClass('active');
        } else {
            this.removeGrid();
            $('#venuera-toggle-grid').removeClass('active');
        }
    };

    /**
     * Draw grid on canvas.
     */
    TicketDesigner.drawGrid = function() {
        this.removeGrid();
        
        var gridSize = this.gridSize;
        var lines = [];
        
        // Vertical lines
        for (var x = 0; x <= this.templateWidth; x += gridSize) {
            lines.push(new fabric.Line([x, 0, x, this.templateHeight], {
                stroke: '#e0e0e0',
                strokeWidth: 1,
                selectable: false,
                evented: false,
                isGrid: true
            }));
        }
        
        // Horizontal lines
        for (var y = 0; y <= this.templateHeight; y += gridSize) {
            lines.push(new fabric.Line([0, y, this.templateWidth, y], {
                stroke: '#e0e0e0',
                strokeWidth: 1,
                selectable: false,
                evented: false,
                isGrid: true
            }));
        }
        
        lines.forEach(function(line) {
            TicketDesigner.canvas.add(line);
            line.sendToBack();
        });
        
        this.gridLines = lines;
        this.canvas.renderAll();
    };

    /**
     * Remove grid from canvas.
     */
    TicketDesigner.removeGrid = function() {
        if (this.gridLines && this.gridLines.length) {
            this.gridLines.forEach(function(line) {
                TicketDesigner.canvas.remove(line);
            });
            this.gridLines = [];
            this.canvas.renderAll();
        }
    };

    /**
     * Toggle snap to grid.
     */
    TicketDesigner.toggleSnapToGrid = function() {
        this.snapToGrid = !this.snapToGrid;
        
        if (this.snapToGrid) {
            $('#venuera-toggle-snap').addClass('active');
        } else {
            $('#venuera-toggle-snap').removeClass('active');
        }
    };

    /**
     * Bring selected element to front.
     */
    TicketDesigner.bringToFront = function() {
        if (this.selectedElement) {
            this.canvas.bringToFront(this.selectedElement);
            this.canvas.renderAll();
            this.markDirty();
        }
    };

    /**
     * Send selected element to back.
     */
    TicketDesigner.sendToBack = function() {
        if (this.selectedElement) {
            this.canvas.sendToBack(this.selectedElement);

            // Keep grid lines at very back
            this.keepGridAtBack();

            this.canvas.renderAll();
            this.markDirty();
        }
    };

    /**
     * Bring selected element forward one step.
     */
    TicketDesigner.bringForward = function() {
        if (this.selectedElement) {
            this.canvas.bringForward(this.selectedElement);
            this.canvas.renderAll();
            this.markDirty();
        }
    };

    /**
     * Send selected element backward one step.
     */
    TicketDesigner.sendBackward = function() {
        if (this.selectedElement) {
            this.canvas.sendBackwards(this.selectedElement);

            // Keep grid lines at very back so stepping backward never hides behind them.
            this.keepGridAtBack();

            this.canvas.renderAll();
            this.markDirty();
        }
    };

    /**
     * Push all grid lines to the very back of the stacking order.
     */
    TicketDesigner.keepGridAtBack = function() {
        if (this.showGrid && this.gridLines) {
            this.gridLines.forEach(function(line) {
                TicketDesigner.canvas.sendToBack(line);
            });
        }
    };

    /**
     * Sync the grid / snap toggle button states with the current model defaults.
     * Called once after init so the toolbar reflects showGrid / snapToGrid.
     */
    TicketDesigner.syncToggleStates = function() {
        $('#venuera-toggle-grid').toggleClass('active', !!this.showGrid);
        $('#venuera-toggle-snap').toggleClass('active', !!this.snapToGrid);

        // Draw the grid if it is on by default.
        if (this.showGrid) {
            this.drawGrid();
        }
    };

    /**
     * Mouse-wheel zoom and Space-drag panning.
     *
     * Wheel zoom adjusts the user-zoom multiplier (currentZoom) and re-runs
     * resizeCanvas() so it stays consistent with the +/- zoom buttons and the
     * 1:1 points = pixels save model (object coordinates are never touched).
     * The canvas element is shrink-wrapped + centered in the scrollable
     * container, so we keep that model (no viewport translation on zoom) and
     * scroll the cursor's point back into view so wheel-zoom still feels
     * anchored toward the pointer.
     */
    TicketDesigner.initCanvasInteractions = function() {
        var self = this;

        if (!this.canvas) {
            return;
        }

        // --- Mouse-wheel zoom (works anywhere over the canvas area, like the
        // Venue Designer). The fabric canvas only covers the ticket itself, so
        // scrolling over the grey pasteboard around it would never reach a
        // fabric `mouse:wheel` listener. We listen on the whole scrollable
        // container instead, so the wheel zooms wherever the cursor is. ---
        var containerEl = document.querySelector('#venuera-canvas-container');
        if (containerEl) {
            containerEl.addEventListener('wheel', function(e) {
                // In a design canvas the wheel always zooms (never page-scrolls).
                e.preventDefault();

                var prevZoom = self.currentZoom;
                var factor = e.deltaY > 0 ? 0.9 : 1.1;
                var newZoom = prevZoom * factor;
                newZoom = Math.max(0.25, Math.min(newZoom, 4));
                newZoom = Math.round(newZoom * 100) / 100;
                if (newZoom === prevZoom) {
                    return;
                }

                // Keep the point under the cursor roughly fixed after the zoom.
                var rect = containerEl.getBoundingClientRect();
                var cursorX = (e.clientX - rect.left + containerEl.scrollLeft);
                var cursorY = (e.clientY - rect.top + containerEl.scrollTop);
                var fracX = containerEl.scrollWidth > 0 ? cursorX / containerEl.scrollWidth : 0.5;
                var fracY = containerEl.scrollHeight > 0 ? cursorY / containerEl.scrollHeight : 0.5;

                self.currentZoom = newZoom;
                self.resizeCanvas();

                containerEl.scrollLeft = (fracX * containerEl.scrollWidth) - (e.clientX - rect.left);
                containerEl.scrollTop = (fracY * containerEl.scrollHeight) - (e.clientY - rect.top);

                self.updateZoomDisplay();
            }, { passive: false });
        }

        // --- Hold SPACE + drag to pan ---
        this.isPanning = false;
        this.spaceDown = false;
        var lastPos = { x: 0, y: 0 };

        $(document).on('keydown.tdPan', function(e) {
            // Don't hijack the space bar while typing in a field or editing a
            // Fabric text object (which uses its own hidden textarea).
            if ($(e.target).is('input, textarea, select') || $(e.target).attr('contenteditable') === 'true') {
                return;
            }
            var active = self.canvas.getActiveObject();
            if (active && active.isEditing) {
                return;
            }
            if (e.code === 'Space' || e.key === ' ' || e.keyCode === 32) {
                if (!self.spaceDown) {
                    self.spaceDown = true;
                    self.canvas.defaultCursor = 'grab';
                    self.canvas.hoverCursor = 'grab';
                    self.canvas.setCursor('grab');
                }
                e.preventDefault();
            }
        });

        $(document).on('keyup.tdPan', function(e) {
            if (e.code === 'Space' || e.key === ' ' || e.keyCode === 32) {
                self.spaceDown = false;
                if (!self.isPanning) {
                    self.restoreCursorAfterPan();
                }
            }
        });

        this.canvas.on('mouse:down', function(opt) {
            var e = opt.e;
            // Pan on drag when holding Space or when the Pan/Hand tool is active.
            if (!self.spaceDown && self.currentTool !== 'pan') {
                return;
            }
            self.isPanning = true;
            // Disable selection while panning so we don't drag elements.
            self.canvas.selection = false;
            self.canvas.discardActiveObject();
            self.canvas.setCursor('grabbing');
            $('#venuera-canvas-container').addClass('is-panning');
            lastPos.x = e.clientX;
            lastPos.y = e.clientY;
        });

        this.canvas.on('mouse:move', function(opt) {
            if (!self.isPanning) {
                return;
            }
            var e = opt.e;
            self.canvas.relativePan(new fabric.Point(e.clientX - lastPos.x, e.clientY - lastPos.y));
            lastPos.x = e.clientX;
            lastPos.y = e.clientY;
            self.canvas.setCursor('grabbing');
        });

        this.canvas.on('mouse:up', function() {
            if (!self.isPanning) {
                return;
            }
            self.isPanning = false;
            $('#venuera-canvas-container').removeClass('is-panning');
            if (self.spaceDown) {
                self.canvas.setCursor('grab');
            } else {
                // Restores select/pan-tool cursor + selection state.
                self.restoreCursorAfterPan();
            }
        });

        // --- Middle mouse button = temporary pan (native DOM events, like the
        // Venue Designer). Works regardless of the active tool and restores it on
        // release. Native events are used because Fabric ignores the middle
        // button by default. ---
        var canvasEl = this.canvas.upperCanvasEl;
        if (canvasEl) {
            $(canvasEl).on('mousedown.tdmidpan', function(e) {
                if (e.button !== 1) { return; }
                e.preventDefault();
                e.stopPropagation();
                self.isPanning = true;
                self.isMiddleMousePan = true;
                self.midPos = { x: e.clientX, y: e.clientY };
                self.canvas.selection = false;
                self.canvas.setCursor('grabbing');
                $('#venuera-canvas-container').addClass('is-panning');
            });

            $(canvasEl).on('auxclick.tdmidpan', function(e) {
                if (e.button === 1) { e.preventDefault(); }
            });

            $(document).on('mousemove.tdmidpan', function(e) {
                if (!self.isMiddleMousePan || !self.isPanning) { return; }
                self.canvas.relativePan(new fabric.Point(e.clientX - self.midPos.x, e.clientY - self.midPos.y));
                self.midPos = { x: e.clientX, y: e.clientY };
            });

            $(document).on('mouseup.tdmidpan', function(e) {
                if (!self.isMiddleMousePan) { return; }
                self.isMiddleMousePan = false;
                self.isPanning = false;
                $('#venuera-canvas-container').removeClass('is-panning');
                self.restoreCursorAfterPan();
            });
        }

        // Clicking the empty area AROUND the ticket (the grey pasteboard/canvas
        // container, outside the Fabric canvas itself) deselects everything.
        // Fabric only clears the selection for clicks on the canvas; clicks in the
        // surrounding container never reach it, so handle them here.
        $('#venuera-canvas-container').on('mousedown.tddeselect', function(e) {
            if (e.target !== this) {
                return; // clicked the canvas, toolbar, status bar, etc.
            }
            if (self.isPanning || self.spaceDown || self.currentTool === 'pan') {
                return; // a pan gesture, not a deselect
            }
            if (self.canvas.getActiveObject()) {
                self.canvas.discardActiveObject();
                self.canvas.requestRenderAll();
                self.onSelectionCleared();
            }
        });
    };

    /**
     * Restore default cursor / selection after a pan gesture, respecting the
     * currently active tool (pan tool keeps grab, select tool restores select).
     */
    TicketDesigner.restoreCursorAfterPan = function() {
        if (!this.canvas) {
            return;
        }
        if (this.currentTool === 'pan') {
            this.canvas.defaultCursor = 'grab';
            this.canvas.hoverCursor = 'grab';
            this.canvas.selection = false;
        } else {
            this.canvas.defaultCursor = 'default';
            this.canvas.hoverCursor = 'move';
            this.canvas.selection = true;
        }
        this.canvas.setCursor(this.canvas.defaultCursor);
        this.canvas.requestRenderAll();
    };

})(jQuery);

