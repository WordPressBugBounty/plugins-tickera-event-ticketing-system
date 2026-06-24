/**
 * Venuera Ticket Designer - Base Elements
 * 
 * Base element creators that other elements extend.
 */

(function($) {
    'use strict';

    if (typeof TicketDesigner === 'undefined') {
        return;
    }

    /**
     * Base element creators.
     */
    TicketDesigner.BaseElements = {

        /**
         * Create a text element.
         * 
         * @param {string} elementId Element type ID.
         * @param {Object} options   Element options.
         * @return {fabric.Text} Text object.
         */
        createText: function(elementId, options) {
            var element = TicketDesigner.ElementRegistry.get(elementId);
            var displayText = options.text || TicketDesigner.getSampleData(options.dataField) || element.label;

            // Add label prefix if specified
            if (options.label && options.labelPosition === 'before') {
                displayText = options.label + ' ' + displayText;
            } else if (options.label && options.labelPosition === 'after') {
                displayText = displayText + ' ' + options.label;
            }

            // Use Textbox (not Text) so the explicit box width enables wrapping
            // and text-align (left/center/right) actually take visible effect.
            var text = new fabric.Textbox(displayText, {
                left: options.x != null ? options.x : 50,
                top: options.y != null ? options.y : 50,
                width: options.width || 200,
                fontSize: options.fontSize || 14,
                fontFamily: options.fontFamily || 'Arial',
                fontWeight: options.fontWeight || 'normal',
                fontStyle: options.fontStyle || 'normal',
                fill: options.fill || '#333333',
                textAlign: options.textAlign || 'left',
                angle: options.rotation || 0,
                opacity: options.opacity != null ? options.opacity : 1,
                originX: 'left',
                originY: 'top',
            });

            // Store element metadata
            text.elementData = {
                id: options.id,
                type: elementId,
                baseType: 'text',
                dataField: options.dataField || null,
                label: options.label || '',
                labelPosition: options.labelPosition || 'before',
                conditional: options.conditional || false,
                staticText: options.text || null,
                width: options.width || 200,
                textAlign: options.textAlign || 'left',
                rotation: options.rotation || 0,
                opacity: options.opacity != null ? options.opacity : 1
            };

            return text;
        },

        /**
         * Create an image element.
         * 
         * @param {string} elementId Element type ID.
         * @param {Object} options   Element options.
         * @return {fabric.Image|fabric.Rect} Image object or placeholder.
         */
        createImage: function(elementId, options) {
            var width = options.width || 100;
            var height = options.height || 100;

            if (options.src) {
                // Load actual image
                return new Promise(function(resolve) {
                    fabric.Image.fromURL(options.src, function(img) {
                        img.set({
                            left: options.x != null ? options.x : 50,
                            top: options.y != null ? options.y : 50,
                            scaleX: width / img.width,
                            scaleY: height / img.height,
                            angle: options.rotation || 0,
                            opacity: options.opacity != null ? options.opacity : 1,
                        });

                        img.elementData = {
                            id: options.id,
                            type: elementId,
                            baseType: 'image',
                            src: options.src,
                            fit: options.fit || 'contain',
                            dataField: options.dataField || null,
                            width: width,
                            height: height,
                            rotation: options.rotation || 0,
                            opacity: options.opacity != null ? options.opacity : 1
                        };

                        resolve(img);
                    });
                });
            }

            // Create placeholder
            var placeholder = new fabric.Rect({
                left: options.x != null ? options.x : 50,
                top: options.y != null ? options.y : 50,
                width: width,
                height: height,
                fill: '#f0f0f0',
                stroke: '#cccccc',
                strokeWidth: 1,
                strokeDashArray: [5, 5],
                angle: options.rotation || 0,
                opacity: options.opacity != null ? options.opacity : 1,
            });

            placeholder.elementData = {
                id: options.id,
                type: elementId,
                baseType: 'image',
                src: '',
                fit: options.fit || 'contain',
                dataField: options.dataField || null,
                width: width,
                height: height,
                rotation: options.rotation || 0,
                opacity: options.opacity != null ? options.opacity : 1
            };

            return placeholder;
        },

        /**
         * Create a QR code element.
         * 
         * @param {string} elementId Element type ID.
         * @param {Object} options   Element options.
         * @return {fabric.Group} QR code group.
         */
        createQRCode: function(elementId, options) {
            // Prefer the saved width/height (which mirror the post-resize
            // dimensions written by onObjectModified) over the original
            // `size` baked into the element at creation time. Old templates
            // saved before that sync still have a stale `size` even though
            // their `width`/`height` reflect the user's last resize.
            var size = options.width || options.height || options.size || 100;
            var foreground = options.foreground || '#000000';
            var background = options.background || '#ffffff';
            // Quiet-zone padding (in canvas px) around the QR modules.
            // Mirrored by the PDF renderer so all three contexts agree.
            var padding = options.padding != null ? Number(options.padding) : 5;
            if (isNaN(padding) || padding < 0) padding = 0;
            // Cap padding so the inner QR area stays positive.
            if (padding > (size / 2 - 5)) padding = Math.max(0, Math.floor(size / 2 - 5));
            var borderWidth  = options.borderWidth  != null ? Number(options.borderWidth)  : 0;
            var borderColor  = options.borderColor  || '#000000';
            var borderRadius = options.borderRadius != null ? Number(options.borderRadius) : 0;
            if (isNaN(borderWidth)  || borderWidth  < 0) borderWidth  = 0;
            if (isNaN(borderRadius) || borderRadius < 0) borderRadius = 0;
            var data = TicketDesigner.getSampleData(options.dataField || 'ticket_id');

            var angle = options.rotation || 0;
            var opacity = options.opacity != null ? options.opacity : 1;

            var elementData = {
                id: options.id,
                type: elementId,
                baseType: 'qrcode',
                dataField: options.dataField || 'ticket_id',
                size: size,
                errorCorrectionLevel: options.errorCorrectionLevel || 'M',
                foreground: foreground,
                background: background,
                padding: padding,
                borderWidth: borderWidth,
                borderColor: borderColor,
                borderRadius: borderRadius,
                rotation: angle,
                opacity: opacity
            };

            // Inner QR area = size minus padding on each side.
            var innerSize = Math.max(10, size - padding * 2);

            // Render the real QR via qrcodejs at the INNER size; we add the
            // quiet-zone padding ourselves by placing the QR image inside a
            // group with a background rect that's `size` × `size`.
            var qrCanvas = TicketDesigner.BaseElements.generateQRCanvas(data, innerSize, foreground, background, elementData.errorCorrectionLevel);

            if (qrCanvas) {
                // Outer background (quiet zone + optional rounded border).
                // The border lives ON the background rect so it visually
                // wraps the entire QR card including the padding.
                var bg = new fabric.Rect({
                    left: 0,
                    top: 0,
                    width: size,
                    height: size,
                    fill: background,
                    rx: borderRadius,
                    ry: borderRadius,
                    stroke: borderWidth > 0 ? borderColor : null,
                    strokeWidth: borderWidth,
                    // Stroke is drawn centred on the rect's edge by default —
                    // that bleeds outside `size`. Switch to the SVG "inside"
                    // behaviour by keeping it centred but shrinking the rect.
                });

                var qrImage = new fabric.Image(qrCanvas, {
                    left: padding,
                    top: padding,
                    selectable: false,
                    evented: false
                });
                if (qrCanvas.width) {
                    qrImage.scaleToWidth(innerSize);
                }

                var group = new fabric.Group([bg, qrImage], {
                    left: options.x != null ? options.x : 50,
                    top: options.y != null ? options.y : 50,
                    angle: angle,
                    opacity: opacity
                });
                group.elementData = elementData;

                // Lock to corner-only resize — middle handles allow non-
                // proportional scaling which distorts the QR modules and
                // can defeat scanning. Corners + lockUniScaling preserve
                // the square shape.
                group.setControlsVisibility({
                    mt: false, mb: false, ml: false, mr: false,
                    mtr: true,
                    tl: true,  tr: true,  bl: true,  br: true
                });
                group.lockUniScaling = true;

                return group;
            }

            // ----- Placeholder fallback -----
            var bg = new fabric.Rect({
                width: size,
                height: size,
                fill: background,
                stroke: '#cccccc',
                strokeWidth: 1,
            });

            var pattern = new fabric.Text('QR', {
                fontSize: size / 3,
                fontFamily: 'Arial',
                fontWeight: 'bold',
                fill: foreground,
                originX: 'center',
                originY: 'center',
                left: size / 2,
                top: size / 2,
            });

            var group = new fabric.Group([bg, pattern], {
                left: options.x != null ? options.x : 50,
                top: options.y != null ? options.y : 50,
                angle: angle,
                opacity: opacity,
            });

            group.elementData = elementData;

            return group;
        },

        /**
         * Generate a QR code canvas using qrcodejs.
         *
         * @param {string} data         Data to encode.
         * @param {number} size         Pixel size.
         * @param {string} foreground   Dark color.
         * @param {string} background   Light color.
         * @param {string} errorLevel   L | M | Q | H.
         * @return {HTMLCanvasElement|null} Rendered canvas or null on failure.
         */
        generateQRCanvas: function(data, size, foreground, background, errorLevel) {
            if (typeof QRCode === 'undefined') {
                return null;
            }

            try {
                // Render at a higher resolution for crisp scaling on the canvas/PDF.
                var renderSize = Math.max(size * 2, 200);

                var holder = document.createElement('div');
                holder.style.position = 'absolute';
                holder.style.left = '-9999px';
                holder.style.top = '-9999px';
                document.body.appendChild(holder);

                var correctLevel = QRCode.CorrectLevel ? QRCode.CorrectLevel[errorLevel || 'M'] : undefined;

                /* eslint-disable no-new */
                new QRCode(holder, {
                    text: data || ' ',
                    width: renderSize,
                    height: renderSize,
                    colorDark: foreground || '#000000',
                    colorLight: background || '#ffffff',
                    correctLevel: correctLevel
                });
                /* eslint-enable no-new */

                // qrcodejs draws into a <canvas> (with an <img> fallback). Prefer
                // the canvas; if only an <img> exists, copy it into a canvas.
                var canvas = holder.querySelector('canvas');

                if (!canvas) {
                    var img = holder.querySelector('img');
                    if (img && img.src) {
                        canvas = document.createElement('canvas');
                        canvas.width = renderSize;
                        canvas.height = renderSize;
                        var ctx = canvas.getContext('2d');
                        ctx.drawImage(img, 0, 0, renderSize, renderSize);
                    }
                }

                // Detach the holder; the canvas keeps its own bitmap.
                var result = null;
                if (canvas) {
                    // Clone into a standalone canvas so removing the holder is safe.
                    result = document.createElement('canvas');
                    result.width = canvas.width;
                    result.height = canvas.height;
                    result.getContext('2d').drawImage(canvas, 0, 0);
                }

                document.body.removeChild(holder);

                return result;
            } catch (e) {
                return null;
            }
        },

        /**
         * Create a barcode element.
         *
         * Renders a REAL barcode on canvas via JsBarcode (same library the
         * preview uses) so the on-screen barcode visually matches the
         * preview and the rendered PDF. The previous implementation drew
         * a random-lines placeholder, which caused users to see three
         * different visuals in canvas / preview / PDF for the same field.
         *
         * @param {string} elementId Element type ID.
         * @param {Object} options   Element options.
         * @return {fabric.Object} A fabric.Image with the real barcode, or a
         *                         placeholder group if JsBarcode is missing.
         */
        createBarcode: function(elementId, options) {
            var width      = options.width  || 150;
            var height     = options.height || 50;
            var foreground = options.foreground || '#000000';
            var background = options.background || '#ffffff';
            var format     = options.format || 'CODE128';
            var showText   = options.showText !== false;
            var angle      = options.rotation || 0;
            var opacity    = options.opacity != null ? options.opacity : 1;

            // Quiet-zone + optional rounded border, mirroring the QR
            // element. Cap padding so the inner bars area stays positive
            // on both axes.
            var padding = options.padding != null ? Number(options.padding) : 3;
            if (isNaN(padding) || padding < 0) padding = 0;
            var maxPad = Math.min(width / 2 - 5, height / 2 - 5);
            if (maxPad < 0) maxPad = 0;
            if (padding > maxPad) padding = Math.floor(maxPad);
            var borderWidth  = options.borderWidth  != null ? Number(options.borderWidth)  : 0;
            var borderColor  = options.borderColor  || '#000000';
            var borderRadius = options.borderRadius != null ? Number(options.borderRadius) : 0;
            if (isNaN(borderWidth)  || borderWidth  < 0) borderWidth  = 0;
            if (isNaN(borderRadius) || borderRadius < 0) borderRadius = 0;

            // Resolve the sample value to encode — same lookup the preview
            // and PDF use, so all three contexts encode the same string.
            var dataField = options.dataField || 'ticket_id';
            var sampleValue = (typeof TicketDesigner.getSampleData === 'function')
                ? TicketDesigner.getSampleData(dataField)
                : '';
            if (!sampleValue) {
                sampleValue = 'TKT123456';
            }

            var elementData = {
                id: options.id,
                type: elementId,
                baseType: 'barcode',
                dataField: dataField,
                format: format,
                width: width,
                height: height,
                showText: showText,
                foreground: foreground,
                background: background,
                padding: padding,
                borderWidth: borderWidth,
                borderColor: borderColor,
                borderRadius: borderRadius,
                rotation: angle,
                opacity: opacity
            };

            // Inner bars area (the element box minus the quiet zone on
            // every side).
            var innerWidth  = Math.max(10, width  - padding * 2);
            var innerHeight = Math.max(10, height - padding * 2);

            // Try to render a real barcode via JsBarcode (enqueued).
            var bcCanvas = TicketDesigner.BaseElements.generateBarcodeCanvas(
                sampleValue, format, foreground, background, showText
            );

            if (bcCanvas) {
                var bg = new fabric.Rect({
                    left: 0,
                    top: 0,
                    width: width,
                    height: height,
                    fill: background,
                    rx: borderRadius,
                    ry: borderRadius,
                    stroke: borderWidth > 0 ? borderColor : null,
                    strokeWidth: borderWidth
                });

                var img = new fabric.Image(bcCanvas, {
                    left: padding,
                    top: padding,
                    selectable: false,
                    evented: false
                });
                // Stretch the rendered bitmap to the INNER bars area so it
                // fits inside the quiet zone. Independent scaleX/scaleY
                // are fine — the bars retain encoding integrity since
                // JsBarcode produces a fixed module pattern.
                if (bcCanvas.width && bcCanvas.height) {
                    img.scaleX = innerWidth  / bcCanvas.width;
                    img.scaleY = innerHeight / bcCanvas.height;
                }

                var group = new fabric.Group([bg, img], {
                    left: options.x != null ? options.x : 50,
                    top: options.y != null ? options.y : 50,
                    angle: angle,
                    opacity: opacity
                });
                group.elementData = elementData;

                // Hide middle-edge handles so users can't independently
                // squish width or height — the bars need proportional
                // resizing to remain scannable.
                group.setControlsVisibility({
                    mt: false, mb: false, ml: false, mr: false,
                    mtr: true,
                    tl: true,  tr: true,  bl: true,  br: true
                });
                group.lockUniScaling = true;

                return group;
            }

            // ----- Placeholder fallback (JsBarcode unavailable) -----
            var bg = new fabric.Rect({
                width: width,
                height: height,
                fill: background,
                stroke: '#cccccc',
                strokeWidth: 1,
            });
            var lines = [];
            for (var i = 0; i < width; i += 3) {
                var lineHeight = height * 0.7;
                lines.push(new fabric.Rect({
                    left: i,
                    top: (height - lineHeight) / 2,
                    width: (i % 6 === 0) ? 2 : 1,
                    height: lineHeight,
                    fill: foreground,
                }));
            }
            var group = new fabric.Group([bg].concat(lines), {
                left: options.x != null ? options.x : 50,
                top: options.y != null ? options.y : 50,
                angle: angle,
                opacity: opacity,
            });
            group.elementData = elementData;
            return group;
        },

        /**
         * Generate a barcode bitmap via JsBarcode, returning an
         * HTMLCanvasElement that can be wrapped in a fabric.Image.
         *
         * Mirrors the preview's call to JsBarcode so the visual result on
         * canvas and in preview is the same. Renders at a higher pixel
         * density (`width: 3`, `height: 80`) for crisp scaling once the
         * fabric.Image is sized down to the user's requested element box.
         *
         * @param {string} data       Value to encode.
         * @param {string} format     JsBarcode format id (e.g. "CODE128").
         * @param {string} foreground Bar color (hex).
         * @param {string} background Background color (hex).
         * @param {boolean} showText  Whether the human-readable value is drawn.
         * @return {HTMLCanvasElement|null}
         */
        generateBarcodeCanvas: function(data, format, foreground, background, showText) {
            if (typeof JsBarcode === 'undefined') {
                return null;
            }
            try {
                var canvas = document.createElement('canvas');
                // Shared JsBarcode params — must match the preview's call in
                // preview.js so the two render the same bitmap. `margin: 0`
                // removes the default padding so the visible bars start at
                // the bitmap's top-left, which lines up with the fabric
                // image's reported left/top on canvas — eliminates the
                // position offset users were seeing between canvas/preview.
                JsBarcode(canvas, String(data || ' '), TicketDesigner.BaseElements.getBarcodeRenderOptions(format, foreground, background, showText));
                return canvas;
            } catch (e) {
                return null;
            }
        },

        /**
         * Single source of truth for JsBarcode render options.
         *
         * Used by both the canvas creator (this module) and the preview
         * (preview.js → renderPreviewBarcodes) so the two contexts produce
         * a bit-identical rendering before the consumer scales the result
         * to the element box.
         *
         * The bitmap intentionally has zero margin and a tall bar height
         * so the proportion of bars-to-text is fixed and predictable;
         * downstream callers stretch the result to the user-chosen
         * element width × height.
         *
         * @param {string} format     JsBarcode format id (CODE128, …).
         * @param {string} foreground Bar color (hex).
         * @param {string} background Background color (hex).
         * @param {boolean} showText  Whether the human-readable value is drawn.
         * @return {Object}
         */
        getBarcodeRenderOptions: function(format, foreground, background, showText) {
            return {
                format:       format || 'CODE128',
                width:        4,
                height:       80,
                displayValue: showText !== false,
                fontSize:     20,
                textMargin:   2,
                margin:       0,
                // JsBarcode default font is monospace; the PDF renderer
                // draws helvetica. Match the PDF look so the label reads
                // the same in all three contexts.
                font:         'sans-serif',
                fontOptions:  '',
                lineColor:    foreground || '#000000',
                background:   background || '#ffffff'
            };
        },

        /**
         * Create a rectangle element.
         * 
         * @param {string} elementId Element type ID.
         * @param {Object} options   Element options.
         * @return {fabric.Rect} Rectangle object.
         */
        createRectangle: function(elementId, options) {
            var rect = new fabric.Rect({
                left: options.x != null ? options.x : 50,
                top: options.y != null ? options.y : 50,
                width: options.width || 200,
                height: options.height || 100,
                fill: options.fill || '#f0f0f0',
                stroke: options.stroke || '#cccccc',
                strokeWidth: options.strokeWidth || 1,
                rx: options.rx || 0,
                ry: options.ry || 0,
                angle: options.rotation || 0,
                opacity: options.opacity != null ? options.opacity : 1,
            });

            rect.elementData = {
                id: options.id,
                type: elementId,
                baseType: 'rectangle',
                rotation: options.rotation || 0,
                opacity: options.opacity != null ? options.opacity : 1
            };

            return rect;
        },

        /**
         * Create a line element.
         * 
         * @param {string} elementId Element type ID.
         * @param {Object} options   Element options.
         * @return {fabric.Line} Line object.
         */
        createLine: function(elementId, options) {
            var length = options.width || 200;
            var isVertical = options.orientation === 'vertical';
            
            var coords = isVertical 
                ? [0, 0, 0, length]
                : [0, 0, length, 0];

            var line = new fabric.Line(coords, {
                left: options.x != null ? options.x : 50,
                top: options.y != null ? options.y : 50,
                stroke: options.stroke || '#cccccc',
                strokeWidth: options.strokeWidth || 1,
                angle: options.rotation || 0,
                opacity: options.opacity != null ? options.opacity : 1,
            });

            line.elementData = {
                id: options.id,
                type: elementId,
                baseType: 'line',
                orientation: options.orientation || 'horizontal',
                length: length,
                rotation: options.rotation || 0,
                opacity: options.opacity != null ? options.opacity : 1
            };

            return line;
        },

        /**
         * Regenerate a QR code bitmap in place after a property change.
         *
         * Rebuilds the QR canvas from the element's current elementData and
         * swaps the fabric object on the canvas, preserving position, angle,
         * opacity, id and selection.
         *
         * @param {fabric.Object} obj Existing QR fabric object.
         * @return {fabric.Object} The (possibly new) fabric object now on canvas.
         */
        regenerateQRCode: function(obj) {
            var data = obj.elementData || {};
            // CRITICAL: every visual prop must be forwarded into the new
            // QR options — otherwise a sidebar tweak (padding, border, …)
            // updates elementData but the regenerated bitmap reads the
            // defaults and the user sees nothing change.
            var options = {
                id: data.id,
                x: obj.left,
                y: obj.top,
                size: data.size || 100,
                dataField: data.dataField || 'ticket_id',
                errorCorrectionLevel: data.errorCorrectionLevel || 'M',
                foreground: data.foreground || '#000000',
                background: data.background || '#ffffff',
                padding:      data.padding      != null ? data.padding      : 5,
                borderWidth:  data.borderWidth  != null ? data.borderWidth  : 0,
                borderColor:  data.borderColor  || '#000000',
                borderRadius: data.borderRadius != null ? data.borderRadius : 2,
                rotation: obj.angle || 0,
                opacity: obj.opacity != null ? obj.opacity : 1
            };

            var fresh = TicketDesigner.BaseElements.createQRCode(data.type || 'qr_code', options);
            return TicketDesigner.BaseElements.swapCanvasObject(obj, fresh);
        },

        /**
         * Regenerate a barcode placeholder in place after a property change.
         *
         * @param {fabric.Object} obj Existing barcode fabric object.
         * @return {fabric.Object} The new fabric object now on canvas.
         */
        regenerateBarcode: function(obj) {
            var data = obj.elementData || {};
            // Forward every visual prop into the new options bag so a
            // sidebar tweak (padding, border, ...) actually triggers a
            // visible re-render — analogous to regenerateQRCode.
            var options = {
                id: data.id,
                x: obj.left,
                y: obj.top,
                width: data.width || 150,
                height: data.height || 50,
                dataField: data.dataField || 'ticket_id',
                format: data.format || 'CODE128',
                showText: data.showText !== false,
                foreground: data.foreground || '#000000',
                background: data.background || '#ffffff',
                padding:      data.padding      != null ? data.padding      : 3,
                borderWidth:  data.borderWidth  != null ? data.borderWidth  : 0,
                borderColor:  data.borderColor  || '#000000',
                borderRadius: data.borderRadius != null ? data.borderRadius : 0,
                rotation: obj.angle || 0,
                opacity: obj.opacity != null ? obj.opacity : 1
            };

            var fresh = TicketDesigner.BaseElements.createBarcode(data.type || 'barcode', options);
            return TicketDesigner.BaseElements.swapCanvasObject(obj, fresh);
        },

        /**
         * Swap one fabric object for another on the canvas in place.
         *
         * Removes the old object and adds the new one, restoring stacking
         * index and selection. Handles Promise-returning creators (images).
         *
         * @param {fabric.Object}         oldObj Object currently on canvas.
         * @param {fabric.Object|Promise} newObj Replacement object or promise.
         * @return {fabric.Object} The new object (when synchronous).
         */
        swapCanvasObject: function(oldObj, newObj) {
            var canvas = TicketDesigner.canvas;
            var wasActive = canvas.getActiveObject() === oldObj;
            var index = canvas.getObjects().indexOf(oldObj);

            var place = function(obj) {
                // Guard so the canvas selection handlers do NOT rebuild the whole
                // properties panel during this programmatic remove/add/setActive
                // cycle (which would reset scroll and destroy an open color picker
                // mid-edit). We re-bind the panel to the new object explicitly.
                TicketDesigner._swapping = true;
                canvas.remove(oldObj);
                canvas.add(obj);
                if (index >= 0 && typeof obj.moveTo === 'function') {
                    obj.moveTo(index);
                }
                if (wasActive) {
                    canvas.setActiveObject(obj);
                    TicketDesigner.selectedElement = obj;
                    // The property-panel event handlers close over the previous
                    // object; re-bind them to the freshly created one so further
                    // edits keep targeting the live object (without rebuilding the
                    // whole panel and losing input focus/scroll).
                    if (typeof TicketDesigner.rebindPropertyEvents === 'function') {
                        TicketDesigner.rebindPropertyEvents(obj);
                    }
                }
                canvas.renderAll();
                TicketDesigner._swapping = false;
            };

            if (newObj instanceof Promise) {
                newObj.then(place);
                return oldObj;
            }

            place(newObj);
            return newObj;
        }
    };

})(jQuery);

