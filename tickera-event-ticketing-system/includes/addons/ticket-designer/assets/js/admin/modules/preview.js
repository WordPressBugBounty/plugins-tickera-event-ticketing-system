/**
 * Venuera Ticket Designer - Preview Module
 * 
 * Handles ticket preview functionality.
 */

(function($) {
    'use strict';

    if (typeof TicketDesigner === 'undefined') {
        return;
    }

    /**
     * Show ticket preview.
     */
    TicketDesigner.showPreview = function() {
        var $modal = $('#venuera-preview-modal');
        var $container = $('#venuera-preview-container');
        
        // Generate preview HTML
        var html = this.generatePreviewHTML();
        
        $container.html(html);
        $modal.show();
        
        // Render any barcodes
        this.renderPreviewBarcodes();
    };

    /**
     * Generate preview HTML.
     * 
     * @return {string} HTML string.
     */
    TicketDesigner.generatePreviewHTML = function() {
        var sampleData = venueraTicketDesigner.sampleData || {};
        
        var html = '<div class="venuera-ticket-preview" style="';
        html += 'width: ' + this.templateWidth + 'px;';
        html += 'height: ' + this.templateHeight + 'px;';
        html += 'background: ' + this.templateBackground + ';';
        html += 'position: relative;';
        html += 'overflow: hidden;';
        html += 'border: 1px solid #ddd;';
        html += 'box-shadow: 0 2px 10px rgba(0,0,0,0.1);';
        html += '">';
        
        // Render each element
        this.canvas.getObjects().forEach(function(obj) {
            if (obj.isGrid) return;
            
            html += TicketDesigner.renderElementToHTML(obj, sampleData);
        });
        
        html += '</div>';
        
        return html;
    };

    /**
     * Render element to HTML.
     * 
     * @param {fabric.Object} obj        Fabric object.
     * @param {Object}        sampleData Sample data.
     * @return {string} HTML string.
     */
    TicketDesigner.renderElementToHTML = function(obj, sampleData) {
        var data = obj.elementData || {};
        var type = obj.type;
        var baseType = data.baseType || type;

        var left = Math.round(obj.left);
        var top = Math.round(obj.top);

        // Rotation/opacity styles shared by every element. The live fabric
        // object carries `angle` (degrees) and `opacity` (0-1); fall back to the
        // saved elementData values so this matches the serialized contract.
        var transform = this.getTransformStyle(obj, data);

        // Text element (Textbox reports type 'textbox')
        if (type === 'text' || type === 'i-text' || type === 'textbox') {
            var text = obj.text;

            // Replace with sample data if dynamic
            if (data.dataField && sampleData[data.dataField]) {
                text = sampleData[data.dataField];
                if (data.label) {
                    if (data.labelPosition === 'after') {
                        text = text + ' ' + data.label;
                    } else {
                        text = data.label + ' ' + text;
                    }
                }
            }

            // Box width: prefer the explicit elementData width, then the scaled
            // fabric width, then a sensible default. Honor wrapping (no nowrap).
            var textWidth = data.width || Math.round(obj.width * obj.scaleX) || 200;

            var style = 'position: absolute;';
            style += 'left: ' + left + 'px;';
            style += 'top: ' + top + 'px;';
            style += 'width: ' + textWidth + 'px;';
            style += 'font-size: ' + obj.fontSize + 'px;';
            style += 'font-family: ' + obj.fontFamily + ';';
            style += 'font-weight: ' + obj.fontWeight + ';';
            style += 'font-style: ' + (obj.fontStyle || 'normal') + ';';
            style += 'color: ' + obj.fill + ';';
            style += 'text-align: ' + (obj.textAlign || 'left') + ';';
            // Explicit UNITLESS line-height = fabric.Textbox's default (1.16),
            // relative to THIS element's font-size. Critical: without it the
            // text divs inherit the admin container's fixed line-height
            // (~18.2px), which is SMALLER than large font sizes (e.g. a 30px
            // title), so the text overflows its 18px line box and overlaps the
            // fields above/below. Unitless 1.16 keeps each line box proportional
            // to its own font-size and matches the canvas + PDF.
            style += 'line-height: 1.16;';
            // Mirror Fabric's own wrapping decision so the preview matches the
            // canvas exactly: if Fabric lays the element out on a single line, the
            // browser must not re-wrap it (canvas/DOM font metrics differ slightly,
            // which otherwise wraps tightly-sized boxes like a short price).
            var fabricSingleLine = !(obj.textLines && obj.textLines.length > 1);
            if (fabricSingleLine) {
                style += 'white-space: nowrap;';
            } else {
                style += 'white-space: normal;';
                style += 'word-wrap: break-word;';
                style += 'overflow-wrap: break-word;';
            }
            style += transform;

            return '<div style="' + style + '">' + this.escapeHtml(text) + '</div>';
        }
        
        // Rectangle
        if (type === 'rect' && baseType !== 'image') {
            var width = Math.round(obj.width * obj.scaleX);
            var height = Math.round(obj.height * obj.scaleY);
            var sw = obj.strokeWidth || 0;
            var hasStroke = sw > 0 && obj.stroke && obj.stroke !== 'transparent';
            var rx = obj.rx || 0;

            var style = 'position: absolute;';
            // Replicate fabric's stroke model: a fabric.Rect stroke is centred
            // ON the path edge (straddles half-in/half-out), so the outer box is
            // width+sw and the top-left sits at (left-sw/2, top-sw/2). A plain
            // CSS border grows fully outward instead. We use box-sizing:border-box
            // on a box expanded by sw and shifted by -sw/2 so the OUTER edge, the
            // visible fill, and the corner radius all match the canvas/PDF.
            if (hasStroke) {
                style += 'left: ' + (left - sw / 2) + 'px;';
                style += 'top: ' + (top - sw / 2) + 'px;';
                style += 'width: ' + (width + sw) + 'px;';
                style += 'height: ' + (height + sw) + 'px;';
                style += 'box-sizing: border-box;';
                style += 'border: ' + sw + 'px solid ' + obj.stroke + ';';
                style += 'border-radius: ' + (rx + sw / 2) + 'px;';
            } else {
                style += 'left: ' + left + 'px;';
                style += 'top: ' + top + 'px;';
                style += 'width: ' + width + 'px;';
                style += 'height: ' + height + 'px;';
                style += 'border-radius: ' + rx + 'px;';
            }
            style += 'background: ' + (obj.fill || 'transparent') + ';';
            style += transform;

            return '<div style="' + style + '"></div>';
        }

        // Line
        if (type === 'line') {
            var isVertical = data.orientation === 'vertical' || data.type === 'vertical_line';
            var length = data.length || Math.abs(obj.x2 - obj.x1) || Math.abs(obj.y2 - obj.y1);
            var strokeWidth = obj.strokeWidth || data.strokeWidth || 1;
            // Dashed when the editor variant is dashed_line or a dash array is set.
            var isDashed = data.type === 'dashed_line'
                || (obj.strokeDashArray && obj.strokeDashArray.length)
                || (data.strokeDashArray && data.strokeDashArray.length);

            // Render the line as an inline SVG so the stroke straddles the
            // geometric line (matching fabric.Line and the PDF, which both
            // centre the stroke on the path) and so the dash cadence is exactly
            // "5,5" — CSS `dashed` borders use a browser-defined cadence that
            // scales with thickness and never matches the canvas/PDF.
            var dashAttr = isDashed ? ' stroke-dasharray="5,5"' : '';
            var strokeColor = this.escapeHtml(obj.stroke || '#000000');
            var svg;
            if (isVertical) {
                // Box width = strokeWidth, shifted left by half a stroke so the
                // line centre sits exactly on `left`.
                var boxW = strokeWidth;
                var style = 'position: absolute;left:' + (left - strokeWidth / 2) + 'px;top:' + top + 'px;' + transform;
                svg = '<svg width="' + boxW + '" height="' + length + '" style="' + style + '" viewBox="0 0 ' + boxW + ' ' + length + '" preserveAspectRatio="none">'
                    + '<line x1="' + (boxW / 2) + '" y1="0" x2="' + (boxW / 2) + '" y2="' + length + '" stroke="' + strokeColor + '" stroke-width="' + strokeWidth + '"' + dashAttr + ' /></svg>';
            } else {
                var boxH = strokeWidth;
                var styleH = 'position: absolute;left:' + left + 'px;top:' + (top - strokeWidth / 2) + 'px;' + transform;
                svg = '<svg width="' + length + '" height="' + boxH + '" style="' + styleH + '" viewBox="0 0 ' + length + ' ' + boxH + '" preserveAspectRatio="none">'
                    + '<line x1="0" y1="' + (boxH / 2) + '" x2="' + length + '" y2="' + (boxH / 2) + '" stroke="' + strokeColor + '" stroke-width="' + strokeWidth + '"' + dashAttr + ' /></svg>';
            }
            return svg;
        }
        
        // Group (QR code, barcode, etc.)
        if (type === 'group') {
            var width = Math.round(obj.width * obj.scaleX);
            var height = Math.round(obj.height * obj.scaleY);
            
            // QR Code
            if (baseType === 'qrcode') {
                // dataField is the single source of truth for what is encoded.
                var qrData = (data.dataField && sampleData[data.dataField]) || sampleData.ticket_id || 'SAMPLE-QR-123';
                // Honour the LIVE box size (after any in-session resize),
                // falling back to the stored size for freshly-loaded
                // templates that haven't been touched yet.
                var size = width || data.size || 100;
                var fg = data.foreground || '#000000';
                var bg = data.background || '#ffffff';

                // Quiet-zone padding + optional border — must match the
                // canvas' createQRCode so what the user sees in the
                // designer is what the preview renders.
                var qrPadding = data.padding != null ? Number(data.padding) : 5;
                if (isNaN(qrPadding) || qrPadding < 0) qrPadding = 0;
                if (qrPadding > (size / 2 - 5)) qrPadding = Math.max(0, Math.floor(size / 2 - 5));
                var qrBorderWidth  = data.borderWidth  != null ? Number(data.borderWidth)  : 0;
                var qrBorderColor  = data.borderColor  || '#000000';
                var qrBorderRadius = data.borderRadius != null ? Number(data.borderRadius) : 0;
                if (isNaN(qrBorderWidth)  || qrBorderWidth  < 0) qrBorderWidth  = 0;
                if (isNaN(qrBorderRadius) || qrBorderRadius < 0) qrBorderRadius = 0;

                var innerSize = Math.max(10, size - qrPadding * 2);

                var style = 'position: absolute;';
                style += 'left: ' + left + 'px;';
                style += 'top: ' + top + 'px;';
                style += 'width: ' + size + 'px;';
                style += 'height: ' + size + 'px;';
                style += 'background: ' + bg + ';';
                style += 'padding: ' + qrPadding + 'px;';
                style += 'box-sizing: border-box;';
                if (qrBorderRadius > 0) style += 'border-radius: ' + qrBorderRadius + 'px;';
                if (qrBorderWidth > 0) style += 'border: ' + qrBorderWidth + 'px solid ' + qrBorderColor + ';';
                style += 'overflow: hidden;';
                style += transform;

                // Generate the QR at the INNER size (without padding), so
                // when we drop it into the padded container the final
                // visual size matches the user's element box exactly.
                var qrCanvas = (TicketDesigner.BaseElements && TicketDesigner.BaseElements.generateQRCanvas)
                    ? TicketDesigner.BaseElements.generateQRCanvas(qrData, innerSize, fg, bg, data.errorCorrectionLevel || 'M')
                    : null;

                if (qrCanvas) {
                    return '<div style="' + style + '"><img src="' + qrCanvas.toDataURL('image/png') + '" style="width:100%;height:100%;display:block;"></div>';
                }

                return '<div style="' + style + 'display:flex;align-items:center;justify-content:center;color:' + fg + ';font-weight:bold;">QR</div>';
            }

            // Barcode
            if (baseType === 'barcode') {
                var barcodeData = (data.dataField && sampleData[data.dataField]) || sampleData.ticket_id || 'TKT123456';
                var bcShowText  = data.showText !== false ? '1' : '0';
                var bcFg        = data.foreground || '#000000';
                var bcBg        = data.background || '#ffffff';

                // Use the LIVE fabric dimensions (`width` / `height` above,
                // = obj.width * obj.scaleX) — these reflect any resize the
                // user did via the fabric handles. The stored elementData
                // width/height only update on save, so reading data.width
                // here used to show the pre-resize size in the preview.
                var bcWidth  = width  || data.width  || 150;
                var bcHeight = height || data.height || 50;

                // Quiet-zone padding + optional border, matching canvas/PDF.
                var bcPadding = data.padding != null ? Number(data.padding) : 3;
                if (isNaN(bcPadding) || bcPadding < 0) bcPadding = 0;
                var maxBcPad = Math.min(bcWidth / 2 - 5, bcHeight / 2 - 5);
                if (maxBcPad < 0) maxBcPad = 0;
                if (bcPadding > maxBcPad) bcPadding = Math.floor(maxBcPad);
                var bcBorderWidth  = data.borderWidth  != null ? Number(data.borderWidth)  : 0;
                var bcBorderColor  = data.borderColor  || '#000000';
                var bcBorderRadius = data.borderRadius != null ? Number(data.borderRadius) : 0;
                if (isNaN(bcBorderWidth)  || bcBorderWidth  < 0) bcBorderWidth  = 0;
                if (isNaN(bcBorderRadius) || bcBorderRadius < 0) bcBorderRadius = 0;

                var style = 'position: absolute;';
                style += 'left: ' + left + 'px;';
                style += 'top: ' + top + 'px;';
                style += 'width: ' + bcWidth + 'px;';
                style += 'height: ' + bcHeight + 'px;';
                style += 'background: ' + bcBg + ';';
                style += 'padding: ' + bcPadding + 'px;';
                style += 'box-sizing: border-box;';
                if (bcBorderRadius > 0) style += 'border-radius: ' + bcBorderRadius + 'px;';
                if (bcBorderWidth > 0) style += 'border: ' + bcBorderWidth + 'px solid ' + bcBorderColor + ';';
                style += 'overflow: hidden;';
                style += transform;

                return '<div class="preview-barcode" style="' + style + '"' +
                    ' data-value="' + TicketDesigner.escapeHtml(String(barcodeData)) + '"' +
                    ' data-format="' + TicketDesigner.escapeHtml(String(data.format || 'CODE128')) + '"' +
                    ' data-show-text="' + bcShowText + '"' +
                    ' data-fg="' + TicketDesigner.escapeHtml(bcFg) + '"' +
                    ' data-bg="' + TicketDesigner.escapeHtml(bcBg) + '"></div>';
            }

            // Image-type groups (event_image, logo, sponsor_logo, custom_image)
            // show a representative image/placeholder graphic rather than the raw
            // machine type string.
            return TicketDesigner.renderGroupVisual(data, sampleData, left, top, width, height, transform);
        }
        
        // Image
        if (type === 'image') {
            var style = 'position: absolute;';
            style += 'left: ' + left + 'px;';
            style += 'top: ' + top + 'px;';
            style += 'width: ' + Math.round(obj.width * obj.scaleX) + 'px;';
            style += 'height: ' + Math.round(obj.height * obj.scaleY) + 'px;';

            if (obj.getSrc && obj.getSrc()) {
                // Use object-fit:fill so the preview matches the canvas exactly:
                // on the canvas fabric scales the image to the box via
                // scaleX/scaleY (independent X/Y stretch = "fill"), and the PDF
                // renderer likewise stretches the image to the element box. All
                // three contexts therefore show the image filling the same box,
                // pixel-for-pixel.
                return '<img src="' + obj.getSrc() + '" style="' + style + transform + 'object-fit: fill;">';
            }

            style += 'background: #f0f0f0;';
            style += 'border: 1px dashed #ccc;';
            style += transform;
            return '<div style="' + style + '"></div>';
        }

        return '';
    };

    /**
     * Build the rotation + opacity inline style for an element.
     *
     * Rotation is degrees applied around the top-left origin (matching the
     * canvas, HTML and PDF contract). Opacity is a 0-1 float. The live fabric
     * object's `angle`/`opacity` win; the saved elementData values are the
     * fallback so this stays consistent with the serialized contract.
     *
     * @param {fabric.Object} obj  Fabric object.
     * @param {Object}        data Element data.
     * @return {string} CSS snippet (possibly empty).
     */
    TicketDesigner.getTransformStyle = function(obj, data) {
        data = data || {};
        var style = '';

        var rotation = (obj && typeof obj.angle === 'number') ? obj.angle : (data.rotation || 0);
        if (rotation) {
            style += 'transform: rotate(' + rotation + 'deg);';
            style += 'transform-origin: top left;';
        }

        var opacity = (obj && typeof obj.opacity === 'number') ? obj.opacity : data.opacity;
        if (typeof opacity === 'number' && opacity < 1) {
            style += 'opacity: ' + opacity + ';';
        }

        return style;
    };

    /**
     * Render a representative visual for a generic group element.
     *
     * Image-type groups (event_image, logo, sponsor_logo, custom_image) show a
     * picture-style placeholder. Never prints the raw machine type string.
     *
     * @param {Object} data       Element data.
     * @param {Object} sampleData Sample data.
     * @param {number} left       Left position.
     * @param {number} top        Top position.
     * @param {number} width      Width.
     * @param {number} height     Height.
     * @param {string} transform  Rotation/opacity style snippet.
     * @return {string} HTML string.
     */
    TicketDesigner.renderGroupVisual = function(data, sampleData, left, top, width, height, transform) {
        var type = data.type || '';
        var style = 'position: absolute;';
        style += 'left: ' + left + 'px;';
        style += 'top: ' + top + 'px;';
        style += 'width: ' + width + 'px;';
        style += 'height: ' + height + 'px;';
        style += (transform || '');

        // Image-type groups: representative picture placeholder graphic.
        style += 'background: #f0f0f0;';
        style += 'border: 1px dashed #ccc;';
        style += 'display: flex; align-items: center; justify-content: center; overflow: hidden;';
        return '<div style="' + style + '">' + this.imagePlaceholderSvg() + '</div>';
    };

    /**
     * Inline SVG for an image placeholder graphic.
     *
     * @return {string} SVG markup.
     */
    TicketDesigner.imagePlaceholderSvg = function() {
        return '<svg width="40" height="40" viewBox="0 0 24 24" fill="none" stroke="#bbb" '
            + 'stroke-width="1.5" xmlns="http://www.w3.org/2000/svg">'
            + '<rect x="3" y="3" width="18" height="18" rx="2"/>'
            + '<circle cx="8.5" cy="8.5" r="1.5"/>'
            + '<path d="M21 15l-5-5L5 21"/></svg>';
    };

    /**
     * Render barcodes in preview.
     */
    TicketDesigner.renderPreviewBarcodes = function() {
        if (typeof JsBarcode === 'undefined') {
            return;
        }

        $('.preview-barcode').each(function() {
            var $container = $(this);
            var value    = $container.data('value');
            var format   = $container.data('format') || 'CODE128';
            var showText = $container.attr('data-show-text') !== '0';
            var fg       = $container.attr('data-fg') || '#000000';
            var bg       = $container.attr('data-bg') || '#ffffff';

            // Create SVG element
            var svg = document.createElementNS('http://www.w3.org/2000/svg', 'svg');
            $container.append(svg);

            try {
                // Use the SHARED render options so the SVG produced here is
                // structurally identical to the bitmap the canvas creator
                // generates — same bar width, height, font, margin, etc.
                var opts = TicketDesigner.BaseElements.getBarcodeRenderOptions(format, fg, bg, showText);
                JsBarcode(svg, String(value), opts);

                // Stretch the rendered SVG to fill its absolutely-positioned
                // container so the preview matches what the user laid out on
                // the canvas pixel-for-pixel. Without this the SVG keeps its
                // intrinsic dimensions and sits offset inside the container,
                // creating the visible canvas-vs-preview gap.
                svg.setAttribute('preserveAspectRatio', 'none');
                svg.style.width   = '100%';
                svg.style.height  = '100%';
                svg.style.display = 'block';
            } catch (e) {
                $container.html('<span style="font-size:10px;color:#999;">Barcode: ' + value + '</span>');
            }
        });
    };

    /**
     * Escape HTML.
     * 
     * @param {string} text Text to escape.
     * @return {string} Escaped text.
     */
    TicketDesigner.escapeHtml = function(text) {
        if (!text) return '';
        return text
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#039;');
    };

})(jQuery);

