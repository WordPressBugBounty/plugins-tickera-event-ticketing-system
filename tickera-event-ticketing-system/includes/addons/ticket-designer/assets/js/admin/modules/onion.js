/**
 * Venuera Ticket Designer - Onion / Off-canvas View Module
 *
 * Adds a toggle that reveals elements lying OUTSIDE the ticket (printable) area.
 *
 * Normally the Fabric canvas is sized exactly to the ticket, so anything pushed
 * outside its bounds — typically after the ticket format/size is changed — is
 * clipped and invisible, which makes those elements impossible to find or move
 * back. When the onion view is on, the canvas viewport is expanded into a
 * "pasteboard" so off-canvas elements become visible and editable again, the
 * printable ticket area is shaded + outlined, and every off-canvas element gets
 * a dashed red outline so it's easy to spot and drag back inside.
 *
 * Guides are non-interactive, excluded from export/history, and never saved into
 * the template data or thumbnail.
 */

(function($) {
    'use strict';

    if (typeof TicketDesigner === 'undefined') {
        return;
    }

    // Whether the off-canvas (onion) view is currently active.
    TicketDesigner.onionView = false;

    // Guide objects currently on the canvas (ticket area, boundary, outlines).
    TicketDesigner.onionGuides = [];

    // Pasteboard background shown around the ticket while the onion view is on.
    TicketDesigner.onionPasteboardColor = '#c7ccd4';

    // Extra margin (in points) added around the content union so off-canvas
    // elements are not flush against the viewport edge.
    TicketDesigner.onionPadding = 50;

    /**
     * Toggle the off-canvas (onion) view on/off.
     *
     * View-only: never marks the template dirty or writes to history.
     */
    TicketDesigner.toggleOnionView = function() {
        this.onionView = !this.onionView;
        $('#venuera-toggle-onion').toggleClass('active', this.onionView);

        if (this.onionView) {
            this.canvas.setBackgroundColor(this.onionPasteboardColor, this.canvas.renderAll.bind(this.canvas));
            this.refreshOnionGuides();
        } else {
            this.removeOnionGuides();
            this.canvas.setBackgroundColor(this.templateBackground, this.canvas.renderAll.bind(this.canvas));
            // Clear the pasteboard pan offset left over from onion mode. setZoom()
            // preserves the existing offset, so without this the ticket stays
            // shifted/clipped after toggling the view off.
            var z = this.canvas.getZoom();
            this.canvas.setViewportTransform([z, 0, 0, z, 0, 0]);
        }

        // Recompute the viewport (expanded in onion mode, ticket-only otherwise).
        this.resizeCanvas();
        // Re-centre the scroll so the ticket sits in the same place whether the
        // pasteboard is shown or hidden (prevents the canvas from appearing to
        // shift/clip when the canvas element grows or shrinks on toggle).
        this.centerCanvasScroll();
        this.updateOnionStatus();
    };

    /**
     * Centre the scrollable canvas container on its content. When the content
     * fits, scroll resolves to 0 (centred by flexbox); when it overflows, this
     * keeps the ticket centred and reachable.
     */
    TicketDesigner.centerCanvasScroll = function() {
        var el = document.querySelector('#venuera-canvas-container');
        if (!el) {
            return;
        }
        el.scrollLeft = Math.max(0, (el.scrollWidth - el.clientWidth) / 2);
        el.scrollTop = Math.max(0, (el.scrollHeight - el.clientHeight) / 2);
    };

    /**
     * Whether a bounding rect lies (even partly) outside the ticket bounds.
     *
     * @param {Object} r Bounding rect { left, top, width, height } in object space.
     * @param {number} w Ticket width (points).
     * @param {number} h Ticket height (points).
     * @return {boolean}
     */
    TicketDesigner.isOffCanvas = function(r, w, h) {
        var tol = 0.5;
        return ( r.left < -tol ) ||
               ( r.top < -tol ) ||
               ( ( r.left + r.width ) > ( w + tol ) ) ||
               ( ( r.top + r.height ) > ( h + tol ) );
    };

    /**
     * Compute the bounding box that encloses both the ticket and every element
     * (so off-canvas elements are included), with padding.
     *
     * @return {Object} { minX, minY, maxX, maxY } in object space (points).
     */
    TicketDesigner.computeContentBounds = function() {
        var minX = 0;
        var minY = 0;
        var maxX = this.templateWidth;
        var maxY = this.templateHeight;

        this.canvas.getObjects().forEach(function(o) {
            if (o.isGrid || o.isOnionGuide) {
                return;
            }
            var r = o.getBoundingRect(true, true);
            minX = Math.min(minX, r.left);
            minY = Math.min(minY, r.top);
            maxX = Math.max(maxX, r.left + r.width);
            maxY = Math.max(maxY, r.top + r.height);
        });

        var pad = this.onionPadding;
        return {
            minX: minX - pad,
            minY: minY - pad,
            maxX: maxX + pad,
            maxY: maxY + pad
        };
    };

    /**
     * Size + position the canvas viewport for the off-canvas (onion) view.
     *
     * Critical: the ticket must NOT move or change size when the onion view is
     * toggled. So we use the EXACT same zoom as the normal view (fit-to-ticket)
     * and grow the canvas element by a SYMMETRIC pasteboard margin around the
     * ticket — large enough to reveal the farthest off-canvas element on any
     * side. Because the margin is symmetric and the container centers the canvas,
     * the ticket stays put; only gray pasteboard (and the off-canvas elements)
     * appears around it.
     */
    TicketDesigner.resizeCanvasOnion = function() {
        var w = this.templateWidth;
        var h = this.templateHeight;

        // Use the same real on-screen scale as the normal view so the ticket
        // keeps its exact size/position when the onion view is toggled.
        var totalZoom = this.currentZoom;

        // How far elements spill past each ticket edge (in points).
        var minX = 0, minY = 0, maxX = w, maxY = h;
        this.canvas.getObjects().forEach(function(o) {
            if (o.isGrid || o.isOnionGuide) { return; }
            var r = o.getBoundingRect(true, true);
            minX = Math.min(minX, r.left);
            minY = Math.min(minY, r.top);
            maxX = Math.max(maxX, r.left + r.width);
            maxY = Math.max(maxY, r.top + r.height);
        });

        // Symmetric pad keeps the ticket centred (no jump on toggle). Always show
        // at least a small visual margin so the pasteboard is visible.
        var visual = 28;
        var padX = Math.max(-minX, maxX - w, 0) + visual;
        var padY = Math.max(-minY, maxY - h, 0) + visual;

        this.canvas.setWidth((w + 2 * padX) * totalZoom);
        this.canvas.setHeight((h + 2 * padY) * totalZoom);
        // Shift the origin by the symmetric pad so the ticket sits in the middle.
        this.canvas.setViewportTransform([totalZoom, 0, 0, totalZoom, padX * totalZoom, padY * totalZoom]);
        this.canvas.requestRenderAll();
    };

    /**
     * Rebuild the onion guide overlay: a shaded ticket area, the ticket boundary
     * outline, and a dashed outline around each off-canvas element.
     */
    TicketDesigner.refreshOnionGuides = function() {
        this.removeOnionGuides();

        if (!this.onionView || !this.canvas) {
            return;
        }

        var self = this;
        var w = this.templateWidth;
        var h = this.templateHeight;
        var guides = [];

        var guideBase = {
            selectable: false,
            evented: false,
            excludeFromExport: true,
            isOnionGuide: true,
            hoverCursor: 'default',
            objectCaching: false
        };

        // 1) The printable ticket area, painted with the ticket background so the
        //    surrounding pasteboard is clearly distinct. Sits at the very back.
        var area = new fabric.Rect($.extend({}, guideBase, {
            left: 0,
            top: 0,
            width: w,
            height: h,
            fill: this.templateBackground || '#ffffff',
            stroke: null,
            // Soft shadow so the printable ticket reads as a card floating on the
            // pasteboard — makes it obvious the off-canvas view is active.
            shadow: new fabric.Shadow({ color: 'rgba(0,0,0,0.28)', blur: 16, offsetX: 0, offsetY: 6 })
        }));
        this.canvas.add(area);
        this.canvas.sendToBack(area);
        guides.push(area);

        // 2) The ticket boundary outline, just above the area so it is always
        //    visible at the edges of the printable region.
        var boundary = new fabric.Rect($.extend({}, guideBase, {
            left: 0,
            top: 0,
            width: w,
            height: h,
            fill: '',
            stroke: '#2563eb',
            strokeWidth: 2,
            strokeUniform: true
        }));
        this.canvas.add(boundary);
        this.canvas.sendToBack(boundary);
        this.canvas.bringForward(boundary);
        guides.push(boundary);

        // 3) A dashed outline around every off-canvas element, drawn on top so it
        //    frames the element wherever it sits in the pasteboard.
        this.canvas.getObjects().forEach(function(o) {
            if (o.isGrid || o.isOnionGuide) {
                return;
            }
            var r = o.getBoundingRect(true, true);
            if (!self.isOffCanvas(r, w, h)) {
                return;
            }
            var outline = new fabric.Rect($.extend({}, guideBase, {
                left: r.left - 3,
                top: r.top - 3,
                width: r.width + 6,
                height: r.height + 6,
                fill: 'rgba(220,38,38,0.10)',
                stroke: '#dc2626',
                strokeWidth: 2,
                strokeDashArray: [6, 4],
                strokeUniform: true
            }));
            self.canvas.add(outline);
            guides.push(outline);
        });

        this.onionGuides = guides;
        this.canvas.requestRenderAll();
    };

    /**
     * Remove all onion guide objects from the canvas.
     */
    TicketDesigner.removeOnionGuides = function() {
        if (this.onionGuides && this.onionGuides.length) {
            var canvas = this.canvas;
            this.onionGuides.forEach(function(g) {
                canvas.remove(g);
            });
        }
        this.onionGuides = [];
    };

    /**
     * Update the status-bar note with the off-canvas element count. When there
     * are off-canvas elements it renders a clickable "Bring inside" action.
     */
    TicketDesigner.updateOnionStatus = function() {
        var $info = $('#venuera-status-info');
        if (!$info.length) {
            return;
        }

        if (!this.onionView) {
            $info.empty();
            return;
        }

        var self = this;
        var w = this.templateWidth;
        var h = this.templateHeight;
        var count = this.getOffCanvasCount();

        $info.empty();

        if (count > 0) {
            $info.append(
                $('<span class="venuera-onion-count"></span>').text(
                    count + (count === 1 ? ' element outside the ticket' : ' elements outside the ticket')
                )
            );
            $('<button type="button" class="button button-small venuera-onion-bring-in"></button>')
                .text('Bring inside')
                .css({ 'margin-left': '8px' })
                .on('click', function() { self.bringOffCanvasIntoView(); })
                .appendTo($info);
        } else {
            $info.append(
                $('<span class="venuera-onion-count"></span>').text('All elements are inside the ticket')
            );
        }
    };

    /**
     * Count elements whose bounding box falls (even partly) outside the ticket.
     *
     * @return {number}
     */
    TicketDesigner.getOffCanvasCount = function() {
        var self = this;
        var w = this.templateWidth;
        var h = this.templateHeight;
        var count = 0;
        this.canvas.getObjects().forEach(function(o) {
            if (o.isGrid || o.isOnionGuide) {
                return;
            }
            if (self.isOffCanvas(o.getBoundingRect(true, true), w, h)) {
                count++;
            }
        });
        return count;
    };

    /**
     * Move every off-canvas element back so its bounding box sits inside the
     * ticket. Clamps position on each axis; oversized elements are aligned to the
     * top-left corner.
     */
    TicketDesigner.bringOffCanvasIntoView = function() {
        var self = this;
        var w = this.templateWidth;
        var h = this.templateHeight;
        var moved = 0;

        this.canvas.getObjects().forEach(function(o) {
            if (o.isGrid || o.isOnionGuide) {
                return;
            }
            var r = o.getBoundingRect(true, true);
            if (!self.isOffCanvas(r, w, h)) {
                return;
            }

            var dx = 0;
            var dy = 0;
            if (r.left < 0) { dx = -r.left; }
            else if (r.left + r.width > w) { dx = w - (r.left + r.width); }
            if (r.top < 0) { dy = -r.top; }
            else if (r.top + r.height > h) { dy = h - (r.top + r.height); }

            o.set({ left: o.left + dx, top: o.top + dy });
            o.setCoords();
            moved++;
        });

        if (moved > 0) {
            this.canvas.requestRenderAll();
            this.markDirty();
            this.saveToHistory();
            this.refreshOnionGuides();
            this.updateOnionStatus();
            this.resizeCanvas();
        }
    };

    /**
     * Produce a clean, ticket-only PNG thumbnail regardless of onion state.
     *
     * In onion mode the viewport is expanded and guides are present, so for the
     * snapshot we temporarily drop to the normal ticket-only view, capture, then
     * restore the onion view exactly as it was.
     *
     * @return {string} PNG data URL.
     */
    TicketDesigner.snapshotThumbnail = function() {
        var wasOnion = this.onionView;

        if (wasOnion) {
            this.onionView = false; // make resizeCanvas use the normal path
            this.removeOnionGuides();
            this.canvas.setBackgroundColor(this.templateBackground);
            this.resizeCanvas(); // ticket-only viewport
        }

        var dataUrl = this.canvas.toDataURL({
            format: 'png',
            quality: 0.8,
            multiplier: 0.5
        });

        if (wasOnion) {
            this.onionView = true;
            this.canvas.setBackgroundColor(this.onionPasteboardColor);
            this.refreshOnionGuides();
            this.resizeCanvas();
        }

        return dataUrl;
    };

})(jQuery);
