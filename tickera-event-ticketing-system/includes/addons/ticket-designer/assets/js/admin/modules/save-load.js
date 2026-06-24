/**
 * Venuera Ticket Designer - Save/Load Module
 * 
 * Handles saving and loading templates.
 */

(function($) {
	function T( k, fb ) { return ( (window.venueraTicketDesigner && window.venueraTicketDesigner.strings) && (window.venueraTicketDesigner && window.venueraTicketDesigner.strings)[ k ] ) || fb; }

    'use strict';

    if (typeof TicketDesigner === 'undefined') {
        return;
    }

    /**
     * Load template data from hidden input.
     */
    TicketDesigner.loadTemplateData = function() {
        var dataJson = $('#venuera-template-data').val();
        var settingsJson = $('#venuera-template-settings').val();
        
        // Build element panel from registry
        this.buildElementPanel();
        
        if (!dataJson) {
            return;
        }
        
        try {
            var data = JSON.parse(dataJson);
            var settings = settingsJson ? JSON.parse(settingsJson) : {};
            
            // Set canvas size
            if (data.width) this.templateWidth = data.width;
            if (data.height) this.templateHeight = data.height;
            if (data.background) this.templateBackground = data.background;
            
            this.setCanvasSize(this.templateWidth, this.templateHeight);
            this.setCanvasBackground(this.templateBackground);
            
            // Update size selector
            this.updateSizeSelector();
            
            // Load elements
            if (data.elements && Array.isArray(data.elements)) {
                this.loadElements(data.elements);
            }

            // Restore the previously-saved "Custom fields for" context so the
            // inline picker stays selected after a reload, and kick off the
            // matching custom-fields fetch so the Field dropdown populates
            // without the user having to re-pick anything.
            if (settings && typeof settings.fieldContext === 'string' && settings.fieldContext) {
                this.currentFieldContext = settings.fieldContext;
                var ctxParts = settings.fieldContext.split(':');
                var ctxType  = ctxParts[0];
                var ctxId    = parseInt(ctxParts[1], 10) || 0;
                if (ctxId && typeof this.loadCustomFields === 'function') {
                    this.loadCustomFields({
                        eventId:   ctxType === 'event' ? ctxId : 0,
                        productId: ctxType === 'product' ? ctxId : 0
                    });
                }
            }

            this.markClean();
            this.saveToHistory();
        } catch (e) {
            console.error('Failed to load template data:', e);
        }
    };

    /**
     * Update size selector based on current dimensions.
     */
    TicketDesigner.updateSizeSelector = function() {
        var $select = $('#venuera-ticket-size');
        var matched = false;
        
        for (var preset in this.sizePresets) {
            if (this.sizePresets[preset].width === this.templateWidth && 
                this.sizePresets[preset].height === this.templateHeight) {
                $select.val(preset);
                matched = true;
                break;
            }
        }
        
        if (!matched) {
            $select.val('custom');
            // Keep the legacy back-compat inputs populated, but do NOT reveal
            // them — the visible custom-size UI is the modal
            // (#venuera-custom-size-modal). Showing this unstyled legacy block
            // made it overlap the canvas on custom-size templates.
            $('#venuera-ticket-width').val(this.templateWidth);
            $('#venuera-ticket-height').val(this.templateHeight);
        }
    };

    /**
     * Load elements onto canvas.
     * 
     * @param {Array} elements Array of element data.
     */
    TicketDesigner.loadElements = function(elements) {
        var self = this;

        // Reserve an array slot per source element so async items (e.g.
        // images loaded via fabric.Image.fromURL) can be slotted at their
        // ORIGINAL z-order rather than dropped on top of everything else
        // once they finally resolve. Without this, a background image
        // defined as element[0] in the JSON ends up covering every other
        // element because it resolves last.
        var slots = new Array(elements.length);

        elements.forEach(function(elementData, idx) {
            var elementType = elementData.type;
            var options = $.extend({}, elementData);
            var config = self.ElementRegistry.get(elementType);

            var fabricObj;
            if (config) {
                fabricObj = self.ElementRegistry.createElement(elementType, options);
            } else {
                fabricObj = self.createGenericElement(elementData);
            }
            if (!fabricObj) { return; }

            if (fabricObj instanceof Promise) {
                fabricObj.then(function(obj) {
                    slots[idx] = obj;
                    self.canvas.add(obj);
                    // Send back to its z-order slot. moveTo expects the
                    // OBJECT INDEX in the canvas — we count how many slots
                    // before us have actually been added (some may be empty
                    // because their owning creator returned null).
                    var targetIndex = 0;
                    for (var i = 0; i < idx; i++) {
                        if (slots[i] && slots[i] !== obj) { targetIndex++; }
                    }
                    if (typeof obj.moveTo === 'function') {
                        obj.moveTo(targetIndex);
                    }
                    self.canvas.renderAll();
                });
            } else {
                slots[idx] = fabricObj;
                self.canvas.add(fabricObj);
            }
        });

        this.canvas.renderAll();

        // Re-resolve canvas text for any data-bound elements whose dataField
        // is a custom attendee field. Templates persist the rendered text
        // verbatim (e.g. "[attendee_field_field_6beca77c-…]" when the field
        // label wasn't yet cached at save time); now that the customFields
        // cache has been seeded with preResolvedCustomFields at bootstrap,
        // getSampleData() can swap that raw key for the friendly label
        // (e.g. "[Iskustvo]").
        //
        // Important: also re-apply elementData.label as a prefix, otherwise
        // a user-typed "Tip:" prefix disappears on reload (the cached `text`
        // would be overwritten with the value-only string).
        this.canvas.getObjects().forEach(function(obj) {
            var d = obj && obj.elementData;
            if (!d || !d.dataField || typeof d.dataField !== 'string') { return; }
            if (d.dataField.indexOf('attendee_field_') !== 0) { return; }
            if (typeof obj.set !== 'function') { return; }
            var newText = self.getSampleData(d.dataField);
            if (d.label) {
                newText = d.label + ' ' + newText;
            }
            if (newText && obj.text !== newText) {
                obj.set('text', newText);
            }
        });
        this.canvas.renderAll();

        // Load the (web) fonts the template uses, then reflow so text metrics are
        // correct (fabric measures with whatever font is available at paint time).
        var families = {};
        this.canvas.getObjects().forEach(function(o) {
            if (o.fontFamily) { families[o.fontFamily] = true; }
        });
        Object.keys(families).forEach(function(fam) {
            self.ensureFontLoaded(fam, function() { self.canvas.requestRenderAll(); });
        });
    };

    /**
     * Create generic element from saved data.
     * 
     * @param {Object} data Element data.
     * @return {fabric.Object|null} Fabric object.
     */
    TicketDesigner.createGenericElement = function(data) {
        var baseType = data.baseType || 'text';
        var angle = data.rotation || 0;
        var opacity = data.opacity != null ? data.opacity : 1;

        switch (baseType) {
            case 'text':
                // Resolve the display string. For data-bound types that are NOT
                // in the element registry (e.g. legacy "ticket_number_formatted"),
                // there's no literal text/staticText saved — pull the sample value
                // from the bound dataField so it renders on load instead of staying
                // blank until a property edit forces a re-resolve.
                var genericText = data.text || data.staticText || '';
                if (!genericText && data.dataField && typeof TicketDesigner.getSampleData === 'function') {
                    genericText = TicketDesigner.getSampleData(data.dataField) || '';
                    if (genericText && data.label) {
                        genericText = data.label + ' ' + genericText;
                    }
                }
                // Use Textbox so the explicit width drives wrapping + text-align.
                var text = new fabric.Textbox(genericText, {
                    left: data.x != null ? data.x : 50,
                    top: data.y != null ? data.y : 50,
                    width: data.width || 200,
                    fontSize: data.fontSize || 14,
                    fontFamily: data.fontFamily || 'Arial',
                    fontWeight: data.fontWeight || 'normal',
                    fontStyle: data.fontStyle || 'normal',
                    fill: data.fill || '#333333',
                    textAlign: data.textAlign || 'left',
                    angle: angle,
                    opacity: opacity
                });
                text.elementData = data;
                return text;

            case 'rectangle':
                var rect = new fabric.Rect({
                    left: data.x != null ? data.x : 50,
                    top: data.y != null ? data.y : 50,
                    width: data.width || 100,
                    height: data.height || 50,
                    fill: data.fill || '#f0f0f0',
                    stroke: data.stroke || '#cccccc',
                    strokeWidth: data.strokeWidth || 1,
                    rx: data.rx || 0,
                    ry: data.ry || 0,
                    angle: angle,
                    opacity: opacity
                });
                rect.elementData = data;
                return rect;

            case 'line':
                var length = data.length || data.width || 200;
                var isVertical = data.orientation === 'vertical';
                var coords = isVertical ? [0, 0, 0, length] : [0, 0, length, 0];

                var line = new fabric.Line(coords, {
                    left: data.x != null ? data.x : 50,
                    top: data.y != null ? data.y : 50,
                    stroke: data.stroke || '#cccccc',
                    strokeWidth: data.strokeWidth || 1,
                    angle: angle,
                    opacity: opacity
                });
                line.elementData = data;
                return line;

            default:
                return null;
        }
    };

    /**
     * Save template.
     */
    TicketDesigner.saveTemplate = function() {
        var self = this;
        // Normalise to a number — a new template renders the hidden field as the
        // string "0", which is truthy and would wrongly look like an existing id.
        var templateId = parseInt($('#venuera-template-id').val(), 10) || 0;
        var templateName = $('#venuera-template-name').val() || 'Untitled Template';
        
        // Collect template data
        var templateData = {
            width: this.templateWidth,
            height: this.templateHeight,
            background: this.templateBackground,
            elements: this.collectElements()
        };
        
        var settings = {
            orientation: this.templateWidth > this.templateHeight ? 'landscape' : 'portrait',
            size: 'custom',
            width: this.templateWidth,
            height: this.templateHeight,
            unit: 'pt', // Always save in points (PDF standard)
            // Persist the user's last-picked "Custom fields for" context so
            // the dropdown stays selected across reloads and the editor can
            // auto-fetch the matching custom fields on bootstrap.
            fieldContext: this.currentFieldContext || ''
        };
        
        // Generate thumbnail. snapshotThumbnail() yields a clean ticket-only
        // image even when the off-canvas (onion) view is active.
        var thumbnail = (typeof this.snapshotThumbnail === 'function')
            ? this.snapshotThumbnail()
            : this.canvas.toDataURL({ format: 'png', quality: 0.8, multiplier: 0.5 });
        
        // Send to server
        $.ajax({
            url: venueraTicketDesigner.ajaxUrl,
            type: 'POST',
            data: {
                action: 'tc_designer_save_ticket_template',
                nonce: venueraTicketDesigner.nonce,
                template_id: templateId,
                name: templateName,
                template_data: JSON.stringify(templateData),
                settings: JSON.stringify(settings),
                thumbnail: thumbnail
            },
            success: function(response) {
                if (response.success) {
                    self.showNotification(venueraTicketDesigner.strings.saved, 'success');
                    self.markClean();
                    
                    // Update template ID if new
                    if (response.data.template_id && !templateId) {
                        $('#venuera-template-id').val(response.data.template_id);
                        // Update URL without reload
                        var newUrl = window.location.href.replace('action=new', 'action=edit&template_id=' + response.data.template_id);
                        window.history.replaceState({}, '', newUrl);
                    }
                } else {
                    self.showNotification(response.data.message || venueraTicketDesigner.strings.saveError, 'error');
                }
            },
            error: function() {
                self.showNotification(venueraTicketDesigner.strings.saveError, 'error');
            }
        });
    };

    /**
     * Collect elements data for saving.
     * 
     * @return {Array} Array of element data.
     */
    TicketDesigner.collectElements = function() {
        var elements = [];
        
        this.canvas.getObjects().forEach(function(obj) {
            // Skip grid lines and off-canvas (onion) view guides.
            if (obj.isGrid || obj.isOnionGuide) return;

            var data = obj.elementData || {};

            // Add position
            data.x = Math.round(obj.left);
            data.y = Math.round(obj.top);

            // Add size if applicable (scaled bounding box). Lines override below.
            if (obj.width) {
                data.width = Math.round(obj.width * obj.scaleX);
            }
            if (obj.height) {
                data.height = Math.round(obj.height * obj.scaleY);
            }

            // Add text-specific properties (Textbox reports type 'textbox').
            if (obj.type === 'text' || obj.type === 'i-text' || obj.type === 'textbox') {
                data.fontSize = obj.fontSize;
                data.fontFamily = obj.fontFamily;
                data.fontWeight = obj.fontWeight;
                data.fontStyle = obj.fontStyle;
                data.fill = obj.fill;
                data.textAlign = obj.textAlign;

                // Textbox width IS the meaningful box width that drives wrapping
                // and text-align, so persist it explicitly.
                data.width = Math.round((obj.width || 0) * (obj.scaleX || 1));

                if (!data.dataField) {
                    // Static text: persist the literal text.
                    data.text = obj.text;
                    data.staticText = obj.text;
                } else {
                    // Data-bound text: persist the field binding so the renderer
                    // can resolve ticket_data[dataField]. Keep label/position/
                    // conditional which already live on elementData. The on-canvas
                    // text is only a sample placeholder, so drop it from the payload.
                    data.dataField = data.dataField;
                    data.label = data.label || '';
                    data.labelPosition = data.labelPosition || 'before';
                    data.conditional = !!data.conditional;
                    delete data.text;
                    delete data.staticText;
                }
            }

            // Add shape-specific properties
            if (obj.type === 'rect') {
                data.fill = obj.fill;
                data.stroke = obj.stroke;
                data.strokeWidth = obj.strokeWidth;
                data.rx = obj.rx;
                data.ry = obj.ry;
            }

            // Add line-specific properties
            if (obj.type === 'line') {
                data.stroke = obj.stroke;
                data.strokeWidth = obj.strokeWidth;
                data.orientation = data.orientation || 'horizontal';
                // Length on the line's axis is the meaningful dimension. Read
                // the LIVE fabric coords first (they reflect resize handles)
                // and only fall back to the cached length when the live values
                // are zero — fabric stores horizontal lines as
                // x1,y1,x2,y2 = 0,0,length,0 and resizing updates x2.
                // Honour scaleX/scaleY too so a scale-style resize is picked
                // up regardless of which path fabric chose.
                var liveLen = Math.max(
                    Math.abs((obj.x2 || 0) - (obj.x1 || 0)) * (obj.scaleX || 1),
                    Math.abs((obj.y2 || 0) - (obj.y1 || 0)) * (obj.scaleY || 1)
                );
                var lineLen = liveLen || data.length || 0;
                data.length = Math.round(lineLen);
                data.width = Math.round(lineLen);
            }

            // Add rotation (degrees, always serialized) and opacity (0-1).
            data.rotation = Math.round(obj.angle) || 0;
            data.opacity = obj.opacity != null ? obj.opacity : 1;

            elements.push(data);
        });
        
        return elements;
    };

    /**
     * Download PDF preview of current template.
     */
    TicketDesigner.downloadPDF = function() {
        var self = this;
        var templateId = $('#venuera-template-id').val();
        
        if (!templateId || templateId === '0') {
            this.showNotification( T('pleaseSaveTheTemplate', 'Please save the template first before downloading PDF.'), 'warning');
            return;
        }
        
        // Show loading state
        var $btn = $('#venuera-download-pdf');
        var originalText = $btn.html();
        $btn.prop('disabled', true).html('<span class="dashicons dashicons-update spin"></span> Generating...');
        
        $.ajax({
            url: venueraTicketDesigner.ajaxUrl,
            type: 'POST',
            data: {
                action: 'tc_designer_preview_pdf',
                nonce: venueraTicketDesigner.nonce,
                template_id: templateId
            },
            success: function(response) {
                if (response.success && response.data.pdf) {
                    // Convert base64 → blob → object URL, then open the PDF in
                    // a NEW BROWSER TAB instead of triggering a download. This
                    // makes side-by-side comparison with the canvas / preview
                    // dramatically easier (no trip through ~/Downloads), which
                    // is exactly how every other "Preview" button in WordPress
                    // already behaves.
                    var byteCharacters = atob(response.data.pdf);
                    var byteNumbers = new Array(byteCharacters.length);
                    for (var i = 0; i < byteCharacters.length; i++) {
                        byteNumbers[i] = byteCharacters.charCodeAt(i);
                    }
                    var byteArray = new Uint8Array(byteNumbers);
                    var blob = new Blob([byteArray], { type: 'application/pdf' });
                    var url  = window.URL.createObjectURL(blob);

                    var win = window.open(url, '_blank');
                    if (!win) {
                        // Pop-up blocker fallback: fall back to inline anchor
                        // click that browsers consistently honour.
                        var a = document.createElement('a');
                        a.href   = url;
                        a.target = '_blank';
                        a.rel    = 'noopener';
                        document.body.appendChild(a);
                        a.click();
                        document.body.removeChild(a);
                    }
                    // Release the object URL after the tab has had time to
                    // load the PDF (immediate revoke would break the new tab).
                    setTimeout(function() {
                        window.URL.revokeObjectURL(url);
                    }, 60000);

                    self.showNotification( T('pdfPreviewOpenedIn', 'PDF preview opened in a new tab.'), 'success');
                } else {
                    self.showNotification(response.data.message || 'Failed to generate PDF.', 'error');
                }
            },
            error: function() {
                self.showNotification( T('errorGeneratingPdfPlease', 'Error generating PDF. Please try again.'), 'error');
            },
            complete: function() {
                $btn.prop('disabled', false).html(originalText);
            }
        });
    };

    /* =========================================================
     * Export / Import / Ready-made templates
     * ========================================================= */

    /**
     * Build the same wrapped payload the export endpoint emits so we can
     * download it directly from the browser. This intentionally mirrors the
     * structure the ready-made starter templates use on disk so anything
     * exported here can be re-imported (or shared as a ready-made template).
     */
    TicketDesigner.buildExportPayload = function() {
        var name = ($('#venuera-template-name').val() || 'Untitled Template').trim();
        var templateData = {
            width:      this.templateWidth,
            height:     this.templateHeight,
            background: this.templateBackground,
            elements:   this.collectElements()
        };
        var settings = {
            orientation: this.templateWidth > this.templateHeight ? 'landscape' : 'portrait',
            size:        'custom',
            width:       this.templateWidth,
            height:      this.templateHeight,
            unit:        'pt'
        };
        return {
            version:      '1.0',
            name:         name,
            slug:         name.toLowerCase().replace(/[^a-z0-9]+/g, '-').replace(/^-+|-+$/g, '') || 'template',
            exportedAt:   new Date().toISOString(),
            templateData: templateData,
            settings:     settings
        };
    };

    /**
     * Download the current template as a JSON file.
     *
     * Uses a hidden anchor with the `download` attribute set via both the
     * DOM property and `setAttribute` (some browsers honour only one), and
     * dispatches a real MouseEvent rather than calling `.click()` — that
     * combination is the most reliable cross-browser download path and
     * avoids the case where Chrome navigates the page to the blob URL
     * instead of triggering a download.
     */
    TicketDesigner.exportTemplate = function() {
        try {
            var payload  = this.buildExportPayload();
            var filename = (payload.slug || 'template') + '.json';
            var json     = JSON.stringify(payload, null, 2);
            var blob     = new Blob([json], { type: 'application/json' });
            var url      = URL.createObjectURL(blob);

            var a = document.createElement('a');
            a.href = url;
            a.download = filename;
            a.setAttribute('download', filename);
            a.setAttribute('target', '_self');
            a.rel = 'noopener';
            // Keep it off-screen but in the DOM — some browsers require the
            // anchor to be in the document tree for `download` to fire.
            a.style.position = 'fixed';
            a.style.top = '-9999px';
            a.style.left = '-9999px';
            document.body.appendChild(a);

            // Dispatch a real MouseEvent so the download is recognised as
            // originating from a user gesture, even when the click was
            // forwarded through a jQuery wrapper.
            var evt = new MouseEvent('click', {
                view: window,
                bubbles: true,
                cancelable: true
            });
            a.dispatchEvent(evt);

            // Cleanup after a short delay so the browser has time to start
            // the download against the still-valid blob URL.
            setTimeout(function() {
                if (a.parentNode) { a.parentNode.removeChild(a); }
                URL.revokeObjectURL(url);
            }, 1500);

            this.showNotification( T('templateExportedAs', 'Template exported as ') + filename, 'success');
        } catch (e) {
            this.showNotification( T('failedToExportTemplate', 'Failed to export template: ') + e.message, 'error');
        }
    };

    /**
     * Apply a parsed template JSON to the editor — replaces canvas size,
     * background and elements. Confirms with the user first if the canvas
     * already has design content.
     *
     * @param {Object} payload  Either the wrapped { templateData: {...} }
     *                          shape, or a raw { width, height, background,
     *                          elements } object.
     * @param {string} sourceLabel  Friendly label used in the confirm dialog
     *                              and success toast ("Concert template",
     *                              "imported file", …).
     */
    TicketDesigner.applyTemplatePayload = function(payload, sourceLabel, opts) {
        var self = this;
        opts = opts || {};
        if (!payload) {
            this.showNotification( T('invalidTemplateData', 'Invalid template data.'), 'error');
            return;
        }

        // Unwrap — accept both the canonical { templateData: {...} } shape
        // (used by ready-made templates + exports) and a bare template
        // object (the old in-DB shape).
        var tdata = payload.templateData || payload;
        if (!tdata || !tdata.width || !tdata.height) {
            this.showNotification( T('templateIsMissingCanvas', 'Template is missing canvas size.'), 'error');
            return;
        }

        var hasContent = (this.canvas.getObjects() || []).some(function(o) {
            return !o.isGrid && !o.isOnionGuide;
        });

        var doApply = function() {
            // Clear existing elements (keep grid / onion guides — they'll be
            // rebuilt by setCanvasSize → resizeCanvas if relevant).
            self.canvas.getObjects().slice().forEach(function(o) {
                if (!o.isGrid && !o.isOnionGuide) {
                    self.canvas.remove(o);
                }
            });

            // Canvas size + background.
            self.setCanvasSize(tdata.width, tdata.height);
            self.setCanvasBackground(tdata.background || '#ffffff');

            // Load all elements from the payload.
            self.loadElements(tdata.elements || []);

            // Keep the template name from the imported file ONLY if the
            // editor's name input is empty (don't clobber a name the user
            // has typed).
            if (payload.name && !$('#venuera-template-name').val()) {
                $('#venuera-template-name').val(payload.name);
            }

            self.canvas.renderAll();
            self.markDirty();
            if (typeof self.saveToHistory === 'function') {
                self.saveToHistory();
            }
            if (typeof self.zoomToFit === 'function') {
                self.zoomToFit();
            }

            // Track which ready-made template (if any) is now loaded, so the
            // admin-only "Replace source template" button knows what to
            // overwrite. Custom JSON imports clear the context.
            if (opts.readySlug) {
                self.setReadyTemplateContext({
                    slug: opts.readySlug,
                    name: payload.name || opts.readySlug
                });
            } else {
                self.setReadyTemplateContext(null);
            }

            self.showNotification( T('loaded', 'Loaded ') + (sourceLabel || 'template') + '.', 'success');
        };

        if (!hasContent) {
            doApply();
            return;
        }

        // Fancy dialog (matches the Venue Designer / Ticket Designer style).
        // Fall back to the native confirm only if the shared dialog hasn't
        // been enqueued for some reason.
        if (window.VenueraDialog && typeof window.VenueraDialog.confirm === 'function') {
            window.VenueraDialog.confirm({
                title:       'Replace current design?',
                message:     'Loading a template will replace every element on the canvas. Any unsaved changes will be lost.',
                confirmText: 'Replace',
                cancelText:  'Keep current',
                type:        'danger'
            }).then(function(ok) {
                if (ok) { doApply(); }
            });
        } else if (window.confirm( T('thisWillReplaceYour', 'This will replace your current design.\n\nAny unsaved changes will be lost. Continue?'))) {
            doApply();
        }
    };

    /**
     * Import a template from a JSON file the user picked from disk.
     */
    TicketDesigner.importTemplate = function(file) {
        if (!file) { return; }
        var self = this;
        var reader = new FileReader();
        reader.onload = function(e) {
            try {
                var json = JSON.parse(e.target.result);
                self.applyTemplatePayload(json, 'imported template');
            } catch (err) {
                self.showNotification( T('failedToParseJson', 'Failed to parse JSON: ') + err.message, 'error');
            }
        };
        reader.onerror = function() {
            self.showNotification( T('failedToReadFile', 'Failed to read file.'), 'error');
        };
        reader.readAsText(file);
    };

    /**
     * Fetch the list of bundled ready-made templates and render their
     * thumbnails into the right sidebar. Each thumbnail is built live from
     * the metadata (canvas size + background) — no PNGs needed.
     */
    TicketDesigner.loadReadyTemplates = function() {
        var self = this;
        $.ajax({
            url:  venueraTicketDesigner.ajaxUrl,
            type: 'POST',
            data: {
                action: 'tc_designer_list_ready_templates',
                nonce:  venueraTicketDesigner.nonce
            },
            success: function(res) {
                if (!res || !res.success) {
                    $('#venuera-ready-templates').html(
                        '<p class="venuera-prop-hint">' + T('noStarterTemplatesAvailable', 'No starter templates available.') + '</p>'
                    );
                    return;
                }
                self.renderReadyTemplatesGallery(res.data.templates || []);
            },
            error: function() {
                $('#venuera-ready-templates').html(
                    '<p class="venuera-prop-hint">' + T('failedToLoadStarter', 'Failed to load starter templates.') + '</p>'
                );
            }
        });
    };

    /**
     * Build the gallery HTML once we have the metadata.
     *
     * Each card holds an <img> placeholder that we then fill with a REAL
     * fabric render of the template (pixel-identical to what the editor
     * shows on the main canvas, only scaled down). That way the user
     * sees exactly what they'll get on click — same QR pattern, same
     * barcode bars, same fonts.
     */
    TicketDesigner.renderReadyTemplatesGallery = function(items) {
        var $container = $('#venuera-ready-templates');
        if (!items.length) {
            $container.html('<p class="venuera-prop-hint">' + T('noStarterTemplatesAvailable2', 'No starter templates available.') + '</p>');
            return;
        }

        // Cache the rendered preview data URLs so the hover popup can reuse
        // them instantly without re-rendering, and so filter-as-you-type
        // doesn't trigger another render pass.
        TicketDesigner._readyTemplateCache = {};
        TicketDesigner._readyTemplateItems = items;

        // Sidebar is ~280px wide. Aim each thumb at ~140px wide.
        var THUMB_W = 140;
        var html = '';
        html += '<div class="venuera-ready-search">';
        html += '<svg class="venuera-ready-search-icon" viewBox="0 0 24 24" width="14" height="14" aria-hidden="true">';
        html += '<path fill="currentColor" d="M15.5 14h-.79l-.28-.27a6.5 6.5 0 1 0-.7.7l.27.28v.79l5 4.99L20.49 19l-4.99-5zm-6 0A4.5 4.5 0 1 1 14 9.5 4.5 4.5 0 0 1 9.5 14z"/>';
        html += '</svg>';
        html += '<input type="search" id="venuera-ready-search-input" placeholder="' + T('searchTemplates', 'Search templates…') + '" autocomplete="off">';
        html += '</div>';
        html += '<div class="venuera-ready-grid">';
        items.forEach(function(t, idx) {
            var aspect = (t.height && t.width) ? (t.height / t.width) : 0.5;
            var h = Math.max(40, Math.round(THUMB_W * aspect));
            var thumbId = 'venuera-ready-thumb-' + idx;
            var hay = [t.name, t.niche, t.description].filter(Boolean).join(' ').toLowerCase();
            html += '<button type="button" class="venuera-ready-card" data-slug="' + TicketDesigner.escapeHtml(t.slug) + '" data-idx="' + idx + '"';
            html += ' data-search="' + TicketDesigner.escapeHtml(hay) + '">';
            html += '<div class="venuera-ready-thumb" style="width:' + THUMB_W + 'px;height:' + h + 'px;background:' + TicketDesigner.escapeHtml(t.background || '#f9fafb') + ';">';
            html += '<img id="' + thumbId + '" alt="" style="width:100%;height:100%;display:block;object-fit:contain;">';
            html += '</div>';
            html += '<div class="venuera-ready-meta">';
            html += '<div class="venuera-ready-name">' + TicketDesigner.escapeHtml(t.name) + '</div>';
            if (t.niche) {
                html += '<div class="venuera-ready-niche">' + TicketDesigner.escapeHtml(t.niche) + '</div>';
            }
            html += '</div>';
            html += '</button>';
        });
        html += '</div>';
        html += '<p class="venuera-ready-empty" style="display:none;">' + T('noTemplatesMatchThat', 'No templates match that search.') + '</p>';
        $container.html(html);

        // Kick off a real fabric render per template (serialised — fabric +
        // QR/barcode internals share DOM helpers, easier to debug if they
        // run one at a time). Each render swaps the <img> src in place and
        // also stores the data URL in the cache for the hover popup.
        var queue = items.map(function(t, idx) {
            return { template: t, imgId: 'venuera-ready-thumb-' + idx };
        });
        function next() {
            if (!queue.length) { return; }
            var item = queue.shift();
            TicketDesigner.renderTemplateThumbnail(item.template, item.imgId).then(next, next);
        }
        next();
    };

    /**
     * Live-filter the ready-made template cards by the search input value.
     * Searches across name + niche + description (cached on data-search).
     */
    TicketDesigner.filterReadyTemplates = function(query) {
        query = (query || '').toLowerCase().trim();
        var matches = 0;
        $('#venuera-ready-templates .venuera-ready-card').each(function() {
            var hay = ($(this).data('search') || '').toString();
            var hit = !query || hay.indexOf(query) !== -1;
            this.style.display = hit ? '' : 'none';
            if (hit) { matches++; }
        });
        $('#venuera-ready-templates .venuera-ready-empty').toggle(matches === 0);
    };

    /**
     * Show a floating, larger preview of a template next to the hovered
     * card so the user can examine the design at a comfortable size.
     */
    TicketDesigner.showReadyPreviewPopup = function(idx, anchorEl) {
        var items = TicketDesigner._readyTemplateItems || [];
        var t = items[idx];
        if (!t || !anchorEl) { return; }

        var POPUP_W = 320; // px on screen
        var aspect = (t.height && t.width) ? (t.height / t.width) : 0.5;
        var popupH = Math.round(POPUP_W * aspect);

        var $popup = $('#venuera-ready-popup');
        if (!$popup.length) {
            $popup = $('<div id="venuera-ready-popup" class="venuera-ready-popup" role="tooltip"></div>').appendTo(document.body);
        }
        // Keep the popup CHROME always white so the title/description stay
        // readable even for templates with a dark background. The template's
        // own background is applied only to the thumbnail area below, where the
        // design preview lives.
        $popup.css({
            width:  POPUP_W + 'px',
            background: '#ffffff'
        });

        // Position: to the LEFT of the sidebar card (sidebar is on the right
        // edge of the screen, so the popup needs to fly leftward).
        var rect = anchorEl.getBoundingClientRect();
        var gap = 12;
        var left = Math.max(8, rect.left - POPUP_W - gap);
        // Vertical-center to the card, clamped to the viewport so the popup
        // doesn't sit off-screen for very tall portrait templates.
        var cardCenterY = rect.top + rect.height / 2;
        var top = Math.max(8, Math.min(window.innerHeight - popupH - 8, cardCenterY - popupH / 2));

        // Build content — title + image. Reuse the cached data URL or fall
        // back to the inline <img> src already produced by the thumbnail
        // render so the popup is always populated.
        var cached = (TicketDesigner._readyTemplateCache || {})[t.slug];
        var imgEl = document.getElementById('venuera-ready-thumb-' + idx);
        var src = cached || (imgEl && imgEl.src) || '';
        var html = '';
        html += '<div class="venuera-ready-popup-header">';
        html += '<div class="venuera-ready-popup-title">' + TicketDesigner.escapeHtml(t.name) + '</div>';
        if (t.niche) {
            html += '<div class="venuera-ready-popup-niche">' + TicketDesigner.escapeHtml(t.niche) + '</div>';
        }
        html += '</div>';
        html += '<div class="venuera-ready-popup-thumb" style="height:' + popupH + 'px;background:' + TicketDesigner.escapeHtml(t.background || '#fff') + ';">';
        if (src) {
            html += '<img src="' + TicketDesigner.escapeHtml(src) + '" alt="" style="width:100%;height:100%;display:block;object-fit:contain;">';
        }
        html += '</div>';
        if (t.description) {
            html += '<div class="venuera-ready-popup-desc">' + TicketDesigner.escapeHtml(t.description) + '</div>';
        }
        html += '<div class="venuera-ready-popup-meta">' + Math.round(t.width) + ' × ' + Math.round(t.height) + ' pt</div>';

        $popup
            .html(html)
            .css({ left: left + 'px', top: top + 'px' })
            .addClass('open');
    };

    TicketDesigner.hideReadyPreviewPopup = function() {
        $('#venuera-ready-popup').removeClass('open');
    };

    /**
     * Render a single template to PNG via a hidden fabric.StaticCanvas at
     * the template's native pt dimensions, then set the supplied <img>
     * element's src to the resulting data URL. The card's CSS scales the
     * image down to thumbnail size, so the preview is pixel-identical to
     * the editor canvas (just smaller).
     *
     * @returns {Promise}
     */
    TicketDesigner.renderTemplateThumbnail = function(template, imgId) {
        return new Promise(function(resolve) {
            var $img = document.getElementById(imgId);
            if (!$img) { resolve(); return; }
            if (typeof fabric === 'undefined' || !TicketDesigner.ElementRegistry) {
                resolve();
                return;
            }
            var W = Math.max(1, template.width  || 432);
            var H = Math.max(1, template.height || 180);

            var native = document.createElement('canvas');
            native.width  = W;
            native.height = H;
            // StaticCanvas: no interaction layer, just draw + export.
            var staticCanvas;
            try {
                staticCanvas = new fabric.StaticCanvas(native, {
                    backgroundColor: template.background || '#ffffff',
                    enableRetinaScaling: false
                });
                staticCanvas.setWidth(W);
                staticCanvas.setHeight(H);
            } catch (e) { resolve(); return; }

            var pending = [];
            // Z-order slot per source element so async-loaded images (e.g.
            // a full-canvas background image at index 0) settle at their
            // ORIGINAL position in the stack — otherwise they end up
            // covering every other element once the network resolves.
            var slots = new Array((template.elements || []).length);

            (template.elements || []).forEach(function(elData, idx) {
                var elementType = elData.type;
                var options = $.extend({}, elData);
                try {
                    var config = TicketDesigner.ElementRegistry.get(elementType);
                    var fabricObj;
                    if (config) {
                        fabricObj = TicketDesigner.ElementRegistry.createElement(elementType, options);
                    } else if (typeof TicketDesigner.createGenericElement === 'function') {
                        fabricObj = TicketDesigner.createGenericElement(elData);
                    }
                    if (!fabricObj) { return; }
                    if (fabricObj instanceof Promise) {
                        pending.push(fabricObj.then(function(o) {
                            if (!o) { return; }
                            slots[idx] = o;
                            staticCanvas.add(o);
                            // Slot back to original z-index.
                            var targetIndex = 0;
                            for (var i = 0; i < idx; i++) {
                                if (slots[i] && slots[i] !== o) { targetIndex++; }
                            }
                            if (typeof o.moveTo === 'function') {
                                o.moveTo(targetIndex);
                            }
                        }));
                    } else {
                        slots[idx] = fabricObj;
                        staticCanvas.add(fabricObj);
                    }
                } catch (e) {
                    // Skip the offending element rather than aborting the
                    // whole preview.
                }
            });

            function finalize() {
                try {
                    staticCanvas.renderAll();
                    var dataURL = native.toDataURL('image/png');
                    $img.src = dataURL;
                    // Cache so the hover popup can re-use without re-render.
                    if (!TicketDesigner._readyTemplateCache) {
                        TicketDesigner._readyTemplateCache = {};
                    }
                    if (template.slug) {
                        TicketDesigner._readyTemplateCache[template.slug] = dataURL;
                    }
                } catch (e) {}
                try { staticCanvas.dispose(); } catch (e) {}
                resolve();
            }

            if (pending.length) {
                Promise.all(pending).then(finalize, finalize);
            } else {
                // Give fabric a tick so any sync-but-deferred renders settle.
                setTimeout(finalize, 16);
            }
        });
    };

    /**
     * Build a low-detail SVG preview of a template from its element data.
     *
     * The preview shows the same shapes the real ticket has — coloured
     * rectangles (header / sidebar / dividers), text BLOCKS rather than
     * actual glyphs (browsers can't reliably draw script/system fonts in
     * a 120-px-wide thumb at scale), and stylised QR / barcode markers.
     * Rotation is honoured per element. It's intentionally NOT a full
     * fabric render — that would be heavy and pointless at this size.
     */
    TicketDesigner.renderTemplateSVGPreview = function(template, boxW, boxH) {
        var W = template.width  || 432;
        var H = template.height || 180;
        var bg = template.background || '#ffffff';
        var elements = template.elements || [];

        var parts = [];
        parts.push('<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 ' + W + ' ' + H + '"');
        parts.push(' preserveAspectRatio="xMidYMid meet" style="width:100%;height:100%;display:block;background:' + TicketDesigner.escapeHtml(bg) + ';">');

        elements.forEach(function(el) {
            var x = +el.x || 0;
            var y = +el.y || 0;
            var w = +el.width  || 0;
            var h = +el.height || 0;
            var size = +el.size || 0;
            var baseType = el.baseType || el.type || '';
            var rot = +el.rotation || 0;
            var op  = (typeof el.opacity === 'number') ? el.opacity : 1;
            // Fabric/CSS rotate around top-left when origin is left/top — match
            // the editor / PDF contract.
            var transform = rot ? (' transform="rotate(' + rot + ' ' + x + ' ' + y + ')"') : '';
            var opAttr    = (op < 1) ? (' opacity="' + op + '"') : '';

            if (baseType === 'rectangle' || baseType === 'rect') {
                var fill = (el.fill && el.fill !== 'transparent') ? el.fill : 'none';
                var stroke = (el.stroke && el.stroke !== 'transparent') ? el.stroke : 'none';
                var sw = +el.strokeWidth || 0;
                var rx = +el.rx || 0;
                parts.push('<rect x="' + x + '" y="' + y + '" width="' + w + '" height="' + h + '"' +
                    ' rx="' + rx + '" ry="' + rx + '"' +
                    ' fill="' + TicketDesigner.escapeHtml(fill) + '"' +
                    ' stroke="' + TicketDesigner.escapeHtml(stroke) + '"' +
                    ' stroke-width="' + sw + '"' + opAttr + transform + '/>');
                return;
            }

            if (baseType === 'line') {
                var ln  = +el.length || w || 0;
                var st  = el.stroke || '#cccccc';
                var sw2 = +el.strokeWidth || 1;
                var vertical = (el.orientation === 'vertical');
                var x2 = vertical ? x : x + ln;
                var y2 = vertical ? y + (+el.height || ln) : y;
                parts.push('<line x1="' + x + '" y1="' + y + '" x2="' + x2 + '" y2="' + y2 + '"' +
                    ' stroke="' + TicketDesigner.escapeHtml(st) + '" stroke-width="' + sw2 + '"' +
                    (el.type === 'dashed_line' ? ' stroke-dasharray="5,5"' : '') +
                    opAttr + transform + '/>');
                return;
            }

            if (baseType === 'qrcode') {
                var qs = size || w || 80;
                var fg = el.foreground || '#000000';
                var qbg = el.background || '#ffffff';
                var qr = +el.borderRadius || 0;
                // Card + a small checker pattern that reads as "QR" at preview scale.
                parts.push('<rect x="' + x + '" y="' + y + '" width="' + qs + '" height="' + qs + '"' +
                    ' rx="' + qr + '" ry="' + qr + '"' +
                    ' fill="' + TicketDesigner.escapeHtml(qbg) + '"' +
                    (el.borderWidth ? ' stroke="' + TicketDesigner.escapeHtml(el.borderColor || '#000') + '" stroke-width="' + el.borderWidth + '"' : '') +
                    opAttr + transform + '/>');
                // Stylised QR modules (5x5 simplified grid).
                var pad = +el.padding || 4;
                var grid = 5;
                var cell = (qs - pad * 2) / grid;
                var modules = '101011010110101100100110';
                for (var gy = 0; gy < grid; gy++) {
                    for (var gx = 0; gx < grid; gx++) {
                        if (modules[(gy * grid + gx) % modules.length] === '1') {
                            parts.push('<rect x="' + (x + pad + gx * cell) + '" y="' + (y + pad + gy * cell) + '"' +
                                ' width="' + cell + '" height="' + cell + '"' +
                                ' fill="' + TicketDesigner.escapeHtml(fg) + '"' + opAttr + transform + '/>');
                        }
                    }
                }
                return;
            }

            if (baseType === 'barcode') {
                var bfg = el.foreground || '#000000';
                var bbg = el.background || '#ffffff';
                var bw = w || 80;
                var bh = h || 30;
                var brd = +el.borderRadius || 0;
                // Background card.
                parts.push('<rect x="' + x + '" y="' + y + '" width="' + bw + '" height="' + bh + '"' +
                    ' rx="' + brd + '" ry="' + brd + '"' +
                    ' fill="' + TicketDesigner.escapeHtml(bbg) + '"' +
                    (el.borderWidth ? ' stroke="' + TicketDesigner.escapeHtml(el.borderColor || '#000') + '" stroke-width="' + el.borderWidth + '"' : '') +
                    opAttr + transform + '/>');
                // Stylised bars — a fixed dense pattern that reads as "barcode" at preview scale.
                var pad2 = +el.padding || 3;
                var innerX = x + pad2;
                var innerY = y + pad2;
                var innerW = Math.max(1, bw - pad2 * 2);
                var innerH = Math.max(1, bh - pad2 * 2);
                var barsH  = el.showText !== false ? innerH * 0.78 : innerH;
                var stripes = [1,2,1,3,1,1,2,1,2,3,1,2,1,1,3,1,2,1,2,1,3,1,2,1,1,2,1,3];
                var total = stripes.reduce(function(a, b) { return a + b; }, 0);
                var unit = innerW / total;
                var cx = innerX;
                for (var i = 0; i < stripes.length; i++) {
                    var sw3 = stripes[i] * unit;
                    if (i % 2 === 0) {
                        parts.push('<rect x="' + cx + '" y="' + innerY + '"' +
                            ' width="' + sw3 + '" height="' + barsH + '"' +
                            ' fill="' + TicketDesigner.escapeHtml(bfg) + '"' + opAttr + transform + '/>');
                    }
                    cx += sw3;
                }
                return;
            }

            // image placeholder
            if (baseType === 'image') {
                parts.push('<rect x="' + x + '" y="' + y + '" width="' + w + '" height="' + h + '"' +
                    ' fill="#f0f0f0" stroke="#cccccc" stroke-width="0.5"' + opAttr + transform + '/>');
                return;
            }

            // Default: text → a thin coloured bar standing in for the text
            // baseline. Approximates the visual weight without trying to
            // typeset the real glyphs at a tiny scale.
            var fs   = +el.fontSize || 12;
            var tw   = +el.width || (fs * 4);
            var tcol = el.fill || '#333333';
            // 60% of font size for the visible bar height — matches the
            // average glyph cap height ratio reasonably well at this scale.
            var barH = Math.max(1, fs * 0.55);
            // Slight horizontal align shift.
            var ax = x;
            if (el.textAlign === 'center') { ax = x; /* svg rect already at x with width tw */ }
            parts.push('<rect x="' + ax + '" y="' + y + '" width="' + tw + '" height="' + barH + '"' +
                ' rx="1" fill="' + TicketDesigner.escapeHtml(tcol) + '" opacity="' + (op * 0.85) + '"' + transform + '/>');
        });

        parts.push('</svg>');
        return parts.join('');
    };

    /**
     * Load one ready-made template's full JSON and hand it to applyTemplatePayload.
     */
    TicketDesigner.applyReadyTemplate = function(slug) {
        var self = this;
        $.ajax({
            url:  venueraTicketDesigner.ajaxUrl,
            type: 'POST',
            data: {
                action: 'tc_designer_get_ready_template',
                nonce:  venueraTicketDesigner.nonce,
                slug:   slug
            },
            success: function(res) {
                if (!res || !res.success) {
                    self.showNotification(
                        (res && res.data && res.data.message) || 'Could not load template.',
                        'error'
                    );
                    return;
                }
                self.applyTemplatePayload(
                    res.data,
                    (res.data && res.data.name) || 'template',
                    { readySlug: slug }
                );
            },
            error: function() {
                self.showNotification( T('couldNotLoadTemplate', 'Could not load template.'), 'error');
            }
        });
    };

    /**
     * Set (or clear) the "currently-loaded ready-made template" context.
     * This drives the admin-only "Replace source template" button — when a
     * bundled template is loaded the button appears with a label naming the
     * source file; when null is passed the button hides again.
     *
     * @param {?{slug:string,name:string}} meta
     */
    TicketDesigner.setReadyTemplateContext = function(meta) {
        this._currentReadyTemplate = meta || null;
        var $wrap = $('#venuera-replace-source');
        var $meta = $('#venuera-replace-source-meta');
        if (!$wrap.length) { return; } // markup hidden for non-admins
        if (meta && meta.slug) {
            // Render a tiny "source: <slug>.json (Name)" line so the operator
            // is never confused about which file they're about to overwrite.
            var safeSlug = String(meta.slug).replace(/[^a-z0-9_\-]/gi, '');
            var safeName = TicketDesigner.escapeHtml ? TicketDesigner.escapeHtml(meta.name || '') : String(meta.name || '');
            $meta.html(
                'Source: <strong>' + safeSlug + '.json</strong>' +
                (safeName ? ' &middot; ' + safeName : '')
            );
            $wrap.show();
        } else {
            $meta.html('');
            $wrap.hide();
        }
    };

    /**
     * Admin-only iteration tool: overwrite the bundled ready-made template's
     * .json file on disk with the editor's current canvas state. Confirms
     * first, then POSTs the buildExportPayload() shape to PHP.
     */
    TicketDesigner.saveReadyTemplate = function() {
        var self = this;
        var meta = this._currentReadyTemplate;
        if (!meta || !meta.slug) {
            this.showNotification( T('loadAReadymadeTemplate', 'Load a ready-made template first.'), 'warning');
            return;
        }

        var payload = this.buildExportPayload();
        // Force the export's slug AND name to match the source we're
        // replacing. buildExportPayload reads both from the editor's
        // `#venuera-template-name` input, which holds the document name
        // (e.g. "Minimal Ticket"), not the ready-made template's name.
        // Without these overrides the bundled .json file would lose its
        // identity ("Birthday Party" → "Minimal Ticket") after every
        // replace.
        payload.slug = meta.slug;
        if (meta.name) {
            payload.name = meta.name;
        }

        var doSave = function() {
            var $btn  = $('#venuera-replace-source-btn');
            var html  = $btn.html();
            $btn.prop('disabled', true).html('<span class="dashicons dashicons-update spin"></span> Saving…');

            $.ajax({
                url:  venueraTicketDesigner.ajaxUrl,
                type: 'POST',
                data: {
                    action:  'tc_designer_save_ready_template',
                    nonce:   venueraTicketDesigner.nonce,
                    slug:    meta.slug,
                    payload: JSON.stringify(payload)
                },
                success: function(res) {
                    if (res && res.success) {
                        self.showNotification(
                            (res.data && res.data.message) || 'Replaced source template.',
                            'success'
                        );
                        // Invalidate the cached ready-templates list so the
                        // sidebar gallery re-fetches updated previews next
                        // time it's rendered.
                        self._readyTemplateItems = null;
                        if (typeof self.loadReadyTemplates === 'function') {
                            self.loadReadyTemplates();
                        }
                    } else {
                        self.showNotification(
                            (res && res.data && res.data.message) || 'Failed to save source template.',
                            'error'
                        );
                    }
                },
                error: function() {
                    self.showNotification( T('failedToSaveSource', 'Failed to save source template (network).'), 'error');
                },
                complete: function() {
                    $btn.prop('disabled', false).html(html);
                }
            });
        };

        var msg = 'This overwrites <strong>' + meta.slug + '.json</strong> on disk with the current canvas.<br><br>This is an admin iteration tool and cannot be undone (the previous file content will be lost).';
        if (window.VenueraDialog && typeof window.VenueraDialog.confirm === 'function') {
            window.VenueraDialog.confirm({
                title:       'Replace source template?',
                message:     msg,
                confirmText: 'Replace file',
                cancelText:  'Cancel',
                type:        'danger'
            }).then(function(ok) {
                if (ok) { doSave(); }
            });
        } else if (window.confirm( T('overwrite', 'Overwrite ') + meta.slug + '.json on disk with the current canvas? This cannot be undone.')) {
            doSave();
        }
    };

    /* ===== Bindings ===== */

    $(document).ready(function() {
        $('#venuera-download-pdf').on('click', function() {
            TicketDesigner.downloadPDF();
        });

        $('#venuera-export-template').on('click', function() {
            TicketDesigner.exportTemplate();
        });

        // Admin-only: overwrite the bundled .json file on disk with the
        // current canvas. Only visible after a ready-made template has been
        // loaded (the markup itself is skipped for non-admins server-side).
        $('#venuera-replace-source-btn').on('click', function() {
            TicketDesigner.saveReadyTemplate();
        });

        $('#venuera-import-template').on('click', function() {
            $('#venuera-import-template-file').trigger('click');
        });

        $('#venuera-import-template-file').on('change', function(e) {
            var file = e.target.files && e.target.files[0];
            if (file) {
                TicketDesigner.importTemplate(file);
                $(this).val(''); // allow the same file to be re-imported
            }
        });

        // Delegated click for ready-made template cards (gallery is rendered
        // async after the AJAX list call returns).
        $(document).on('click', '.venuera-ready-card', function() {
            var slug = $(this).data('slug');
            if (slug) {
                TicketDesigner.applyReadyTemplate(slug);
            }
        });

        // Live search input.
        $(document).on('input', '#venuera-ready-search-input', function() {
            TicketDesigner.filterReadyTemplates(this.value);
        });

        // Larger preview popup on hover (mouseenter on card → show; leave → hide).
        // Use a small debounce so quickly skimming the gallery doesn't flash
        // the popup; 120 ms feels right (matches OS tooltip latency).
        var hoverTimer = null;
        $(document).on('mouseenter', '.venuera-ready-card', function() {
            var el  = this;
            var idx = parseInt($(this).data('idx'), 10);
            if (isNaN(idx)) { return; }
            clearTimeout(hoverTimer);
            hoverTimer = setTimeout(function() {
                TicketDesigner.showReadyPreviewPopup(idx, el);
            }, 120);
        });
        $(document).on('mouseleave', '.venuera-ready-card', function() {
            clearTimeout(hoverTimer);
            TicketDesigner.hideReadyPreviewPopup();
        });
        // Keep the popup pinned to the card if the user scrolls the sidebar
        // while hovering — drops the popup so it doesn't end up orphaned.
        $(document).on('scroll', '#venuera-properties-panel', function() {
            TicketDesigner.hideReadyPreviewPopup();
        });

        // Kick off the gallery load after the editor's bootstrap is done so
        // the AJAX nonce is definitely registered. Slight delay rather than
        // a hard dependency keeps this independent of init ordering.
        setTimeout(function() {
            if (TicketDesigner && typeof TicketDesigner.loadReadyTemplates === 'function') {
                TicketDesigner.loadReadyTemplates();
            }
        }, 300);
    });

})(jQuery);

