/**
 * Venuera Ticket Designer - Properties Module
 *
 * Handles properties panel for selected elements.
 * i18n: T() helpers are interpolated with ${...} inside template literals.
 */

(function($) {
	function T( k, fb ) { return ( (window.venueraTicketDesigner && window.venueraTicketDesigner.strings) && (window.venueraTicketDesigner && window.venueraTicketDesigner.strings)[ k ] ) || fb; }

    'use strict';

    if (typeof TicketDesigner === 'undefined') {
        return;
    }

    /**
     * Show properties panel for selected element.
     * 
     * @param {fabric.Object} obj Selected object.
     */
    /**
     * Layer / z-order controls, rendered inside the properties sidebar (these
     * used to live in the top toolbar). Buttons call the existing
     * TicketDesigner.bringToFront / bringForward / sendBackward / sendToBack.
     */
    TicketDesigner.renderArrangeSection = function() {
        return ''
            + '<div class="venuera-prop-section">'
            +   '<div class="venuera-prop-section-header">' + T('arrange', 'Arrange') + '</div>'
            +   '<div class="venuera-prop-group venuera-arrange-row">'
            +     '<button type="button" class="venuera-arrange-btn" data-arrange="front" aria-label="' + T('bringToFront', 'Bring to Front') + '" data-tip="' + T('bringToFront', 'Bring to Front') + '"><svg viewBox="0 0 24 24"><path d="M2 16h4v4H2v-4zm5-9h4v4H7V7zm0 5h4v4H7v-4zm-5 0h4v4H2v-4zm0-5h4v4H2V7zm14-5v4h-4V2h4zm0 5h4v4h-4V7zm-5 0h4v4h-4V7zm-2-5h4v4H9V2zM7 2h4v4H7V2zM2 2h4v4H2V2z" fill="currentColor"/></svg></button>'
            +     '<button type="button" class="venuera-arrange-btn" data-arrange="forward" aria-label="' + T('bringForward', 'Bring Forward') + '" data-tip="' + T('bringForward', 'Bring Forward') + '"><svg viewBox="0 0 24 24"><path d="M3 3h12v12H3V3zm14 6h4v12H9v-4h8V9z" fill="currentColor"/></svg></button>'
            +     '<button type="button" class="venuera-arrange-btn" data-arrange="backward" aria-label="' + T('sendBackward', 'Send Backward') + '" data-tip="' + T('sendBackward', 'Send Backward') + '"><svg viewBox="0 0 24 24"><path d="M9 9h12v12H9V9zM3 3h12v4H7v8H3V3z" fill="currentColor"/></svg></button>'
            +     '<button type="button" class="venuera-arrange-btn" data-arrange="back" aria-label="' + T('sendToBack', 'Send to Back') + '" data-tip="' + T('sendToBack', 'Send to Back') + '"><svg viewBox="0 0 24 24"><path d="M18 18h4v-4h-4v4zm-5-9h-4v4h4V9zm0 5H9v4h4v-4zm5 0h-4v4h4v-4zm0-5h-4v4h4V9zM4 22V18h4v4H4zm0-5h4v-4H4v4zm5 0h4v-4H9v4zm2 5h4v-4h-4v4zm-2 0h4v-4H9v4zm13-13h-4v4h4V9z" fill="currentColor"/></svg></button>'
            +   '</div>'
            + '</div>';
    };

    /** One-time delegated binding for the sidebar arrange buttons. */
    TicketDesigner.bindArrangeEvents = function() {
        if (this._arrangeBound) { return; }
        this._arrangeBound = true;
        var self = this;
        jQuery(document).on('click', '.venuera-arrange-btn', function() {
            var a = jQuery(this).data('arrange');
            if (a === 'front')         { self.bringToFront(); }
            else if (a === 'forward')  { self.bringForward(); }
            else if (a === 'backward') { self.sendBackward(); }
            else if (a === 'back')     { self.sendToBack(); }
        });
    };

    TicketDesigner.showProperties = function(obj) {
        if (!obj || !obj.elementData) {
            this.hideProperties();
            return;
        }

        var elementConfig = this.ElementRegistry.get(obj.elementData.type);
        var $content = $('#venuera-properties-content');

        // When editing an element, show only that element's properties (like the
        // Venue Designer). The ticket-level Background Color + the ready-made
        // templates gallery + export/import tools all live in the default
        // (no-selection) state, so they're hidden while editing.
        $('.venuera-ticket-settings').hide();
        $('#venuera-template-tools').hide();
        $content.show();

        $content.empty();
        
        // Element type header
        var label = elementConfig ? elementConfig.label : obj.elementData.type;
        $content.append('<div class="venuera-prop-header"><strong>' + label + '</strong></div>');
        
        // Position properties (common to all)
        $content.append(this.renderPositionProperties(obj));
        
        // Type-specific properties
        var baseType = obj.elementData.baseType || 'text';
        var elType = obj.elementData.type;

        // Special-cased element types whose panels differ from their baseType.
        if (elType === 'event_image' || elType === 'event_logo' || elType === 'sponsor_logo') {
            $content.append(this.renderDataImageProperties(obj));
        } else if (elType === 'google_map') {
            $content.append(this.renderGoogleMapProperties(obj));
        } else {
            switch (baseType) {
                case 'text':
                    $content.append(this.renderTextProperties(obj));
                    break;
                case 'image':
                    $content.append(this.renderImageProperties(obj));
                    break;
                case 'qrcode':
                    $content.append(this.renderQRCodeProperties(obj));
                    break;
                case 'barcode':
                    $content.append(this.renderBarcodeProperties(obj));
                    break;
                case 'rectangle':
                    $content.append(this.renderRectangleProperties(obj));
                    break;
                case 'line':
                    $content.append(this.renderLineProperties(obj));
                    break;
            }
        }
        
        // Arrange / layer order (moved here from the top toolbar).
        $content.append(this.renderArrangeSection(obj));
        this.bindArrangeEvents();

        // Bind property change events
        this.bindPropertyEvents(obj);

        // Initialize color pickers
        this.initColorPickers();

        // If this element is data-bound text, make sure the field picker reflects
        // any custom fields already fetched for the current event/product context.
        if (baseType === 'text' && obj.elementData && obj.elementData.dataField) {
            this.refreshCustomFieldOptions(obj.elementData.dataField);
        }
    };

    /**
     * Cached custom (attendee) fields for the current context.
     * Each entry: { id: 'attendee_field_<fieldId>', label: '<Label>' }.
     *
     * Pre-seeded with labels resolved server-side at bootstrap for any
     * attendee_field bindings already present on the loaded template, so the
     * canvas shows friendly labels like "Iskustvo" on first paint instead of
     * the raw "attendee_field_<uuid>" key.
     */
    TicketDesigner.customFields = (function() {
        var seed = (window.venueraTicketDesigner && venueraTicketDesigner.preResolvedCustomFields) || [];
        return Array.isArray(seed) ? seed.slice() : [];
    })();

    /**
     * Currently picked field-context (event/product) that drives custom-field
     * fetches. Persists across element selections so the user only picks once
     * per editing session.
     *
     * Value shape mirrors the picker option values: "event:<id>" or
     * "product:<id>" (empty string when nothing is picked yet).
     */
    TicketDesigner.currentFieldContext = '';

    /**
     * Build the inline "Custom fields for" picker shown inside the properties
     * panel for data-bound elements. The picker drives the loadCustomFields
     * AJAX so the field dropdown can show event/product-specific attendee
     * fields. Populated from the localized fieldContexts list.
     *
     * @param {string} selected Currently selected context value (e.g. "event:42").
     * @return {string} HTML for the picker group.
     */
    TicketDesigner.buildFieldContextPicker = function(selected) {
        var contexts = (window.venueraTicketDesigner && venueraTicketDesigner.fieldContexts) || [];
        if (!contexts.length) {
            return '';
        }

        var current = selected || this.currentFieldContext || '';
        var options = '<option value="">-- Select event / ticket type --</option>';
        contexts.forEach(function(ctx) {
            if (!ctx || !ctx.id) { return; }
            var value = ctx.type + ':' + ctx.id;
            var isSel = value === current ? ' selected' : '';
            options += '<option value="' + value + '"' + isSel + '>' + (ctx.label || value) + '</option>';
        });

        return (
            '<div class="venuera-prop-group venuera-field-context-group">'
            + '<label>' + T('customFieldsFor', 'Custom fields for') + '</label>'
            + '<select name="venuera-field-context" class="venuera-prop-select venuera-field-context-inline">'
            + options
            + '</select>'
            + '<p class="venuera-prop-hint" style="margin:4px 0 0;color:#6b7280;font-size:11px;">'
            + 'Pick the event or ticket type whose attendee fields should appear in the Field dropdown below.'
            + '</p>'
            + '<div class="venuera-field-context-status" data-state="" style="display:none;margin:6px 0 0;padding:6px 10px;border-radius:4px;font-size:12px;"></div>'
            + '</div>'
        );
    };

    /**
     * Build the grouped <option> markup for the data-field picker.
     *
     * Standard groups come from the localized get_data_fields() data
     * (Event / Ticket / Seat / Attendee). The "Custom fields" group is
     * appended from the cached, dynamically-fetched custom fields.
     *
     * @param {string} selectedField Currently selected dataField key.
     * @return {string} <optgroup>/<option> HTML.
     */
    TicketDesigner.buildFieldOptions = function(selectedField) {
        var dataFields = venueraTicketDesigner.dataFields || {};
        var fieldOptions = '';

        for (var groupKey in dataFields) {
            var group = dataFields[groupKey];
            fieldOptions += '<optgroup label="' + group.label + '">';
            for (var fieldKey in group.fields) {
                var selected = selectedField === fieldKey ? 'selected' : '';
                fieldOptions += '<option value="' + fieldKey + '" ' + selected + '>' + group.fields[fieldKey] + '</option>';
            }
            fieldOptions += '</optgroup>';
        }

        fieldOptions += this.buildCustomFieldOptionsGroup(selectedField);

        return fieldOptions;
    };

    /**
     * Build the "Custom fields" optgroup from the cached custom fields.
     *
     * @param {string} selectedField Currently selected dataField key.
     * @return {string} <optgroup> HTML (empty string when no custom fields).
     */
    TicketDesigner.buildCustomFieldOptionsGroup = function(selectedField) {
        var fields = this.customFields || [];
        var seen = false;
        var html = '<optgroup label="Custom fields" class="venuera-custom-fields-group">';

        fields.forEach(function(field) {
            if (!field || !field.id) {
                return;
            }
            var selected = selectedField === field.id ? 'selected' : '';
            html += '<option value="' + field.id + '" ' + selected + '>' + (field.label || field.id) + '</option>';
            seen = true;
        });

        // If the element is bound to a custom field that has not been fetched
        // yet (e.g. loaded template before context was chosen), keep it visible
        // so the binding is not silently lost.
        if (selectedField && selectedField.indexOf('attendee_field_') === 0) {
            var present = fields.some(function(f) { return f && f.id === selectedField; });
            if (!present) {
                html += '<option value="' + selectedField + '" selected>' + selectedField + '</option>';
                seen = true;
            }
        }

        html += '</optgroup>';

        return seen ? html : '';
    };

    /**
     * Refresh the "Custom fields" optgroup inside the currently rendered
     * data-field picker without rebuilding the whole panel.
     *
     * @param {string} selectedField Field key to keep selected.
     */
    TicketDesigner.refreshCustomFieldOptions = function(selectedField) {
        var $select = $('#venuera-properties-content .venuera-datafield-select');
        if (!$select.length) {
            return;
        }

        var current = selectedField || $select.val();

        $select.find('optgroup.venuera-custom-fields-group').remove();
        var groupHtml = this.buildCustomFieldOptionsGroup(current);
        if (groupHtml) {
            $select.append(groupHtml);
        }

        if (current) {
            $select.val(current);
        }
    };

    /**
     * Fetch custom (attendee) fields for an event/product context via AJAX and
     * refresh any open data-field picker.
     *
     * Consumes the shared contract action `tc_designer_get_custom_fields`, which
     * returns { success:true, data:{ fields:[ { id:'attendee_field_<id>', label }, ... ] } }.
     *
     * @param {Object}   context  { eventId, productId } (either/both optional).
     * @param {Function} [cb]     Optional callback(fields) after load.
     */
    TicketDesigner.loadCustomFields = function(context, cb) {
        var self = this;
        context = context || {};

        // Show "loading…" hint while the request is in flight so the user
        // sees feedback the moment they pick a context.
        self.setFieldContextStatus('loading', '');

        $.ajax({
            url: venueraTicketDesigner.ajaxUrl,
            type: 'POST',
            data: {
                action: 'tc_designer_get_custom_fields',
                nonce: venueraTicketDesigner.nonce,
                event_id: context.eventId || 0,
                product_id: context.productId || 0,
                ticket_type_id: context.productId || 0
            },
            success: function(response) {
                var fields = (response && response.success && response.data && Array.isArray(response.data.fields))
                    ? response.data.fields
                    : [];

                // MERGE the fetched fields into the cache (de-duplicate by id)
                // instead of replacing it. This way: labels learned at bootstrap
                // (preResolvedCustomFields) and on previous context picks are
                // never lost, so the canvas keeps showing friendly labels even
                // after switching to a context that doesn't include some of the
                // template's existing bindings.
                self.mergeCustomFields(fields);
                self.refreshCustomFieldOptions();

                // Surface a notice when the picked context has no custom fields
                // — better UX than silently showing an empty dropdown.
                if (fields.length === 0) {
                    self.setFieldContextStatus('empty', 'No custom fields are configured for this selection.');
                } else {
                    self.setFieldContextStatus('ok',
                        fields.length === 1
                            ? '1 custom field loaded.'
                            : fields.length + ' custom fields loaded.');
                }

                if (typeof cb === 'function') {
                    cb(self.customFields);
                }
            },
            error: function() {
                self.refreshCustomFieldOptions();
                self.setFieldContextStatus('error', 'Could not load custom fields. Please try again.');
                if (typeof cb === 'function') {
                    cb([]);
                }
            }
        });
    };

    /**
     * Merge a fetched list of custom fields into the cache without dropping
     * already-known entries (notably the preResolvedCustomFields seed). New
     * entries are appended; existing entries (matched by id) are refreshed
     * with the latest label.
     *
     * @param {Array<{id:string,label:string}>} fields Fetched fields.
     */
    TicketDesigner.mergeCustomFields = function(fields) {
        if (!Array.isArray(fields) || !fields.length) {
            return;
        }
        if (!Array.isArray(this.customFields)) {
            this.customFields = [];
        }
        var byId = {};
        this.customFields.forEach(function(f) { if (f && f.id) { byId[f.id] = f; } });
        fields.forEach(function(f) {
            if (!f || !f.id) { return; }
            if (byId[f.id]) {
                if (f.label) { byId[f.id].label = f.label; }
            } else {
                byId[f.id] = { id: f.id, label: f.label || f.id };
                this.customFields.push(byId[f.id]);
            }
        }.bind(this));
    };

    /**
     * Update the small status line under the inline "Custom fields for"
     * picker. The DOM node is rendered as part of the picker; we just set
     * its text + state class so CSS can style it.
     *
     * @param {string} state One of: '', 'loading', 'ok', 'empty', 'error'.
     * @param {string} text  Message text (empty hides the line).
     */
    TicketDesigner.setFieldContextStatus = function(state, text) {
        var $status = $('#venuera-properties-content .venuera-field-context-status');
        if (!$status.length) { return; }
        $status
            .attr('data-state', state || '')
            .text(text || '')
            .toggle(!!text);
    };

    /**
     * Hide properties panel.
     */
    TicketDesigner.hideProperties = function() {
        // Bring back the ticket-level settings (Background Color) and the
        // template tools (ready-made gallery + export/import) — they're the
        // "no element selected" panel state. The element-properties content
        // is hidden until something is selected again.
        $('.venuera-ticket-settings').show();
        $('#venuera-template-tools').show();
        $('#venuera-properties-content').hide().html(
            '<p class="venuera-no-selection">' + venueraTicketDesigner.strings.noElements + '</p>'
        );
    };

    /**
     * Render position properties.
     * 
     * @param {fabric.Object} obj Object.
     * @return {string} HTML.
     */
    TicketDesigner.renderPositionProperties = function(obj) {
        return this.renderTransformProperties(obj);
    };

    /**
     * Render shared Position + Transform properties (X/Y, Width/Height,
     * Rotation, Opacity). Common to every element type.
     *
     * Rotation is stored on obj.angle + elementData.rotation (degrees).
     * Opacity slider is 0-100 (%) mapped to obj.opacity / elementData.opacity (0-1).
     *
     * @param {fabric.Object} obj Object.
     * @return {string} HTML.
     */
    TicketDesigner.renderTransformProperties = function(obj) {
        var data = obj.elementData || {};
        var rotation = Math.round(obj.angle || data.rotation || 0);
        var opacityPct = Math.round((obj.opacity != null ? obj.opacity : 1) * 100);
        var dims = this.getDisplayDimensions(obj);

        // QR codes deliberately omit the Width/Height inputs, the Rotation
        // slider and the Opacity slider — width/height are driven by the
        // dedicated "Size" slider in the QR section (or by dragging a
        // corner handle on canvas), and rotation/opacity don't make sense
        // for a scannable code. Keeping the panel focused removes
        // controls that would either break scanning or feel redundant.
        var baseType = data.baseType || 'text';
        var isQrCode = baseType === 'qrcode';

        var sizeRow = isQrCode ? '' : `
                <div class="venuera-prop-row">
                    <div class="venuera-prop-group venuera-prop-half">
                        <label>${T('width', 'Width')}</label>
                        <input type="number" name="width" class="venuera-prop-input" value="${dims.width}" min="1">
                    </div>
                    <div class="venuera-prop-group venuera-prop-half">
                        <label>${T('height', 'Height')}</label>
                        <input type="number" name="height" class="venuera-prop-input" value="${dims.height}" min="1">
                    </div>
                </div>`;

        var rotationRow = isQrCode ? '' : `
                <div class="venuera-prop-group">
                    <label>${T('rotation', 'Rotation:')} <span class="venuera-range-value">${rotation}</span>°</label>
                    <input type="range" name="rotation" class="venuera-prop-range" value="${rotation}" min="0" max="360" step="1">
                </div>`;

        var opacityRow = isQrCode ? '' : `
                <div class="venuera-prop-group">
                    <label>${T('opacity', 'Opacity:')} <span class="venuera-range-value">${opacityPct}</span>%</label>
                    <input type="range" name="opacity" class="venuera-prop-range" value="${opacityPct}" min="0" max="100" step="1">
                </div>`;

        return `
            <div class="venuera-prop-section">
                <div class="venuera-prop-section-header">${T('positionTransform', 'Position & Transform')}</div>
                <div class="venuera-prop-row">
                    <div class="venuera-prop-group venuera-prop-half">
                        <label>X</label>
                        <input type="number" name="x" class="venuera-prop-input" value="${Math.round(obj.left)}">
                    </div>
                    <div class="venuera-prop-group venuera-prop-half">
                        <label>Y</label>
                        <input type="number" name="y" class="venuera-prop-input" value="${Math.round(obj.top)}">
                    </div>
                </div>
                ${sizeRow}
                ${rotationRow}
                ${opacityRow}
            </div>
        `;
    };

    /**
     * Compute the current on-canvas display width/height for any element.
     *
     * Lines report their length on a single axis; groups/images use the
     * scaled bounding box; rect/textbox use width/height * scale.
     *
     * @param {fabric.Object} obj Object.
     * @return {Object} { width, height }.
     */
    TicketDesigner.getDisplayDimensions = function(obj) {
        var data = obj.elementData || {};

        if (obj.type === 'line') {
            var len = data.length || Math.abs(obj.x2 - obj.x1) || Math.abs(obj.y2 - obj.y1) || 0;
            if (data.orientation === 'vertical') {
                return { width: Math.round(obj.strokeWidth || 1), height: Math.round(len) };
            }
            return { width: Math.round(len), height: Math.round(obj.strokeWidth || 1) };
        }

        return {
            width: Math.round((obj.width || 0) * (obj.scaleX || 1)),
            height: Math.round((obj.height || 0) * (obj.scaleY || 1))
        };
    };

    /**
     * Render text properties.
     * 
     * @param {fabric.Object} obj Object.
     * @return {string} HTML.
     */
    TicketDesigner.renderTextProperties = function(obj) {
        var data = obj.elementData || {};
        // Data-bound element types always show the field picker, even before a
        // field is chosen (their dataField starts empty). Everything else without
        // a dataField is treated as literal static text.
        var dataBoundTypes = ['custom_attendee_field', 'dynamic_text'];
        var isStatic = !data.dataField && dataBoundTypes.indexOf(data.type) === -1;
        var fonts = venueraTicketDesigner.fonts || {};
        
        var fontOptions = Object.keys(fonts).map(function(font) {
            var selected = obj.fontFamily === font ? 'selected' : '';
            return '<option value="' + font + '" style="font-family: ' + font + ';" ' + selected + '>' + fonts[font] + '</option>';
        }).join('');
        
        var html = '<div class="venuera-prop-section">';
        html += '<div class="venuera-prop-section-header">' + T('text', 'Text') + '</div>';
        
        // Static text input
        if (isStatic) {
            html += `
                <div class="venuera-prop-group">
                    <label>${T('text2', 'Text')}</label>
                    <textarea name="text" class="venuera-prop-input" rows="3">${obj.text || ''}</textarea>
                </div>
            `;
        } else {
            // Inline "Custom fields for" picker — only meaningful for
            // data-bound elements. Drives loadCustomFields so the Field
            // dropdown below can list event/product-specific attendee fields.
            html += this.buildFieldContextPicker();

            // Data field selector (standard groups + dynamic "Custom fields").
            var fieldOptions = this.buildFieldOptions(data.dataField);

            html += `
                <div class="venuera-prop-group">
                    <label>${T('field', 'Field')}</label>
                    <select name="dataField" class="venuera-prop-select venuera-datafield-select">${fieldOptions}</select>
                </div>
                <div class="venuera-prop-group">
                    <label>${T('labelPrefix', 'Label Prefix')}</label>
                    <input type="text" name="label" class="venuera-prop-input" value="${data.label || ''}" placeholder="e.g., Date:">
                </div>
                <div class="venuera-prop-group">
                    <label>
                        <input type="checkbox" name="conditional" ${data.conditional ? 'checked' : ''}>
                        Hide if empty
                    </label>
                </div>
            `;
        }
        
        // Font properties
        html += `
            <div class="venuera-prop-group">
                <label>${T('fontFamily', 'Font Family')}</label>
                <select name="fontFamily" class="venuera-prop-select">${fontOptions}</select>
            </div>
            <div class="venuera-prop-group">
                <label>${T('fontSize', 'Font Size')}</label>
                <input type="number" name="fontSize" class="venuera-prop-input" value="${obj.fontSize}" min="8" max="72">
            </div>
            <div class="venuera-prop-row">
                <div class="venuera-prop-group venuera-prop-half">
                    <label>${T('weight', 'Weight')}</label>
                    <select name="fontWeight" class="venuera-prop-select">
                        <option value="normal" ${obj.fontWeight === 'normal' ? 'selected' : ''}>${T('normal', 'Normal')}</option>
                        <option value="bold" ${obj.fontWeight === 'bold' ? 'selected' : ''}>${T('bold', 'Bold')}</option>
                    </select>
                </div>
                <div class="venuera-prop-group venuera-prop-half">
                    <label>${T('style', 'Style')}</label>
                    <select name="fontStyle" class="venuera-prop-select">
                        <option value="normal" ${obj.fontStyle === 'normal' ? 'selected' : ''}>${T('normal2', 'Normal')}</option>
                        <option value="italic" ${obj.fontStyle === 'italic' ? 'selected' : ''}>${T('italic', 'Italic')}</option>
                    </select>
                </div>
            </div>
            <div class="venuera-prop-group">
                <label>${T('textColor', 'Text Color')}</label>
                <input type="text" name="fill" class="venuera-color-picker" value="${obj.fill || '#333333'}">
            </div>
            <div class="venuera-prop-group">
                <label>${T('textAlign', 'Text Align')}</label>
                <div class="venuera-button-group">
                    <button type="button" class="venuera-align-btn ${obj.textAlign === 'left' ? 'active' : ''}" data-align="left">
                        <span class="dashicons dashicons-editor-alignleft"></span>
                    </button>
                    <button type="button" class="venuera-align-btn ${obj.textAlign === 'center' ? 'active' : ''}" data-align="center">
                        <span class="dashicons dashicons-editor-aligncenter"></span>
                    </button>
                    <button type="button" class="venuera-align-btn ${obj.textAlign === 'right' ? 'active' : ''}" data-align="right">
                        <span class="dashicons dashicons-editor-alignright"></span>
                    </button>
                </div>
            </div>
        `;
        
        html += '</div>';
        return html;
    };

    /**
     * Render image properties.
     * 
     * @param {fabric.Object} obj Object.
     * @return {string} HTML.
     */
    TicketDesigner.renderImageProperties = function(obj) {
        var data = obj.elementData || {};
        
        return `
            <div class="venuera-prop-section">
                <div class="venuera-prop-section-header">${T('image', 'Image')}</div>
                <div class="venuera-prop-group">
                    <label>${T('image2', 'Image')}</label>
                    <div class="venuera-image-upload">
                        <input type="hidden" name="src" class="venuera-image-src" value="${data.src || ''}">
                        <div class="venuera-image-preview">${data.src ? '<img src="' + data.src + '">' : ''}</div>
                        <button type="button" class="button venuera-select-image">${venueraTicketDesigner.strings.selectImage}</button>
                        <button type="button" class="button venuera-remove-image" ${!data.src ? 'style="display:none"' : ''}>${T('remove', 'Remove')}</button>
                    </div>
                </div>
                <div class="venuera-prop-group">
                    <label>${T('fit', 'Fit')}</label>
                    <select name="fit" class="venuera-prop-select">
                        <option value="contain" ${data.fit === 'contain' ? 'selected' : ''}>${T('contain', 'Contain')}</option>
                        <option value="cover" ${data.fit === 'cover' ? 'selected' : ''}>${T('cover', 'Cover')}</option>
                        <option value="fill" ${data.fit === 'fill' ? 'selected' : ''}>${T('stretch', 'Stretch')}</option>
                    </select>
                </div>
            </div>
        `;
    };

    /**
     * Render data-bound image (event_image) properties.
     *
     * Event image pulls from the event's featured image at render time, so the
     * editor only exposes the fit mode (the bitmap is resolved server-side).
     *
     * @param {fabric.Object} obj Object.
     * @return {string} HTML.
     */
    /**
     * Google Map element settings panel (address / zoom / map type). Mirrors the
     * classic Google Map element. An empty address falls back to the event
     * location at render time. Requires a Google Maps API key in Tickera settings.
     */
    TicketDesigner.renderGoogleMapProperties = function(obj) {
        var data = obj.elementData || {};
        var addr = data.map_address || '';
        var zoom = (data.map_zoom != null) ? data.map_zoom : 14;
        var type = data.map_maptype || 'roadmap';
        function sel(v, cur) { return v === cur ? 'selected' : ''; }

        return `
            <div class="venuera-prop-section">
                <div class="venuera-prop-section-header">${T('googleMap', 'Google Map')}</div>
                <div class="venuera-prop-group">
                    <label>${T('mapAddress', 'Address / Coordinates')}</label>
                    <input type="text" name="map_address" class="venuera-prop-input" value="${$('<div>').text(addr).html()}" placeholder="${T('mapAddressPlaceholder', 'Leave empty to use event location')}" />
                </div>
                <div class="venuera-prop-group">
                    <label>${T('mapZoom', 'Zoom')}</label>
                    <input type="number" name="map_zoom" class="venuera-prop-input" min="1" max="21" value="${parseInt(zoom, 10) || 14}" />
                </div>
                <div class="venuera-prop-group">
                    <label>${T('mapType', 'Map Type')}</label>
                    <select name="map_maptype" class="venuera-prop-select">
                        <option value="roadmap" ${sel('roadmap', type)}>${T('mapRoadmap', 'Roadmap')}</option>
                        <option value="terrain" ${sel('terrain', type)}>${T('mapTerrain', 'Terrain')}</option>
                        <option value="satellite" ${sel('satellite', type)}>${T('mapSatellite', 'Satellite')}</option>
                        <option value="hybrid" ${sel('hybrid', type)}>${T('mapHybrid', 'Hybrid')}</option>
                    </select>
                </div>
                <p class="venuera-prop-hint">${T('mapHint', 'The map image is generated on the ticket PDF. Requires a Google Maps API key in Tickera settings.')}</p>
            </div>
        `;
    };

    TicketDesigner.renderDataImageProperties = function(obj) {
        var data = obj.elementData || {};

        return `
            <div class="venuera-prop-section">
                <div class="venuera-prop-section-header">${T('eventImage', 'Event Image')}</div>
                <div class="venuera-prop-group">
                    <p class="venuera-prop-hint">Shows the event's featured image on generated tickets.</p>
                </div>
                <div class="venuera-prop-group">
                    <label>${T('fit2', 'Fit')}</label>
                    <select name="fit" class="venuera-prop-select">
                        <option value="contain" ${data.fit === 'contain' ? 'selected' : ''}>${T('contain2', 'Contain')}</option>
                        <option value="cover" ${data.fit === 'cover' ? 'selected' : ''}>${T('cover2', 'Cover')}</option>
                        <option value="fill" ${data.fit === 'fill' ? 'selected' : ''}>${T('stretch2', 'Stretch')}</option>
                    </select>
                </div>
            </div>
        `;
    };

    /**
     * Render QR code properties.
     *
     * @param {fabric.Object} obj Object.
     * @return {string} HTML.
     */
    TicketDesigner.renderQRCodeProperties = function(obj) {
        var data = obj.elementData || {};
        var errorLevels = venueraTicketDesigner.qrErrorLevels || {};
        
        var errorOptions = Object.keys(errorLevels).map(function(level) {
            var selected = data.errorCorrectionLevel === level ? 'selected' : '';
            return '<option value="' + level + '" ' + selected + '>' + errorLevels[level] + '</option>';
        }).join('');
        
        var padding      = data.padding      != null ? data.padding      : 8;
        var borderWidth  = data.borderWidth  != null ? data.borderWidth  : 0;
        var borderColor  = data.borderColor  || '#000000';
        var borderRadius = data.borderRadius != null ? data.borderRadius : 0;
        var foreground   = data.foreground   || '#000000';
        var background   = data.background   || '#ffffff';

        return `
            <div class="venuera-prop-section">
                <div class="venuera-prop-section-header">${T('qrCode', 'QR Code')}</div>
                <div class="venuera-prop-group">
                    <label>${T('dataSource', 'Data Source')}</label>
                    <select name="dataField" class="venuera-prop-select">
                        <option value="ticket_id" ${data.dataField === 'ticket_id' || data.dataField === 'qr_code' ? 'selected' : ''}>${T('ticketId', 'Ticket ID')}</option>
                        <option value="order_id" ${data.dataField === 'order_id' ? 'selected' : ''}>${T('orderId', 'Order ID')}</option>
                    </select>
                </div>
                <div class="venuera-prop-group">
                    <label>${T('errorCorrection', 'Error Correction')}</label>
                    <select name="errorCorrectionLevel" class="venuera-prop-select">${errorOptions}</select>
                </div>
                <div class="venuera-prop-group">
                    <label>${T('foreground', 'Foreground')}</label>
                    <input type="text" name="foreground" class="venuera-color-picker" value="${foreground}">
                </div>
                <div class="venuera-prop-group">
                    <label>${T('background', 'Background')}</label>
                    <input type="text" name="background" class="venuera-color-picker" value="${background}">
                </div>
                <div class="venuera-prop-group">
                    <label>${T('padding', 'Padding:')} <span class="venuera-range-value">${padding}</span>px</label>
                    <input type="range" name="padding" class="venuera-prop-range" value="${padding}" min="0" max="40" step="1">
                </div>
                <div class="venuera-prop-group">
                    <label>
                        <input type="checkbox" name="border" ${borderWidth > 0 ? 'checked' : ''}>
                        Border
                    </label>
                </div>
                <div class="venuera-prop-group">
                    <label>${T('cornerRadius', 'Corner radius:')} <span class="venuera-range-value">${borderRadius}</span>px</label>
                    <input type="range" name="borderRadius" class="venuera-prop-range" value="${borderRadius}" min="0" max="10" step="1">
                </div>
            </div>
        `;
    };

    /**
     * Render barcode properties.
     * 
     * @param {fabric.Object} obj Object.
     * @return {string} HTML.
     */
    TicketDesigner.renderBarcodeProperties = function(obj) {
        var data = obj.elementData || {};
        var formats = venueraTicketDesigner.barcodeFormats || {};

        var formatOptions = Object.keys(formats).map(function(format) {
            var selected = data.format === format ? 'selected' : '';
            return '<option value="' + format + '" ' + selected + '>' + formats[format] + '</option>';
        }).join('');

        var padding      = data.padding      != null ? data.padding      : 6;
        var borderWidth  = data.borderWidth  != null ? data.borderWidth  : 0;
        var borderColor  = data.borderColor  || '#000000';
        var borderRadius = data.borderRadius != null ? data.borderRadius : 0;
        var foreground   = data.foreground   || '#000000';
        var background   = data.background   || '#ffffff';

        return `
            <div class="venuera-prop-section">
                <div class="venuera-prop-section-header">${T('barcode', 'Barcode')}</div>
                <div class="venuera-prop-group">
                    <label>${T('dataSource2', 'Data Source')}</label>
                    <select name="dataField" class="venuera-prop-select">
                        <option value="ticket_id" ${data.dataField === 'ticket_id' ? 'selected' : ''}>${T('ticketId2', 'Ticket ID')}</option>
                        <option value="order_id" ${data.dataField === 'order_id' ? 'selected' : ''}>${T('orderId2', 'Order ID')}</option>
                    </select>
                </div>
                <div class="venuera-prop-group">
                    <label>${T('format', 'Format')}</label>
                    <select name="format" class="venuera-prop-select">${formatOptions}</select>
                </div>
                <div class="venuera-prop-group">
                    <label>${T('foreground2', 'Foreground')}</label>
                    <input type="text" name="foreground" class="venuera-color-picker" value="${foreground}">
                </div>
                <div class="venuera-prop-group">
                    <label>${T('background2', 'Background')}</label>
                    <input type="text" name="background" class="venuera-color-picker" value="${background}">
                </div>
                <div class="venuera-prop-group">
                    <label>
                        <input type="checkbox" name="showText" ${data.showText !== false ? 'checked' : ''}>
                        Show text below
                    </label>
                </div>
                <div class="venuera-prop-group">
                    <label>${T('padding2', 'Padding:')} <span class="venuera-range-value">${padding}</span>px</label>
                    <input type="range" name="padding" class="venuera-prop-range" value="${padding}" min="0" max="20" step="1">
                </div>
                <div class="venuera-prop-group">
                    <label>
                        <input type="checkbox" name="border" ${borderWidth > 0 ? 'checked' : ''}>
                        Border
                    </label>
                </div>
                <div class="venuera-prop-group">
                    <label>${T('cornerRadius2', 'Corner radius:')} <span class="venuera-range-value">${borderRadius}</span>px</label>
                    <input type="range" name="borderRadius" class="venuera-prop-range" value="${borderRadius}" min="0" max="10" step="1">
                </div>
            </div>
        `;
    };

    /**
     * Render rectangle properties.
     * 
     * @param {fabric.Object} obj Object.
     * @return {string} HTML.
     */
    TicketDesigner.renderRectangleProperties = function(obj) {
        return `
            <div class="venuera-prop-section">
                <div class="venuera-prop-section-header">${T('style2', 'Style')}</div>
                <div class="venuera-prop-group">
                    <label>${T('fillColor', 'Fill Color')}</label>
                    <input type="text" name="fill" class="venuera-color-picker" value="${obj.fill || '#f0f0f0'}">
                </div>
                <div class="venuera-prop-group">
                    <label>${T('borderColor', 'Border Color')}</label>
                    <input type="text" name="stroke" class="venuera-color-picker" value="${obj.stroke || '#cccccc'}">
                </div>
                <div class="venuera-prop-group">
                    <label>${T('borderWidth', 'Border Width')}</label>
                    <input type="number" name="strokeWidth" class="venuera-prop-input" value="${obj.strokeWidth || 0}" min="0" max="10">
                </div>
                <div class="venuera-prop-group">
                    <label>${T('cornerRadius3', 'Corner Radius')}</label>
                    <input type="number" name="rx" class="venuera-prop-input" value="${obj.rx || 0}" min="0" max="50">
                </div>
            </div>
        `;
    };

    /**
     * Render line properties.
     * 
     * @param {fabric.Object} obj Object.
     * @return {string} HTML.
     */
    TicketDesigner.renderLineProperties = function(obj) {
        var data = obj.elementData || {};
        var orientation = data.orientation || 'horizontal';

        return `
            <div class="venuera-prop-section">
                <div class="venuera-prop-section-header">${T('line', 'Line')}</div>
                <div class="venuera-prop-group">
                    <label>${T('orientation', 'Orientation')}</label>
                    <select name="orientation" class="venuera-prop-select">
                        <option value="horizontal" ${orientation === 'horizontal' ? 'selected' : ''}>${T('horizontal', 'Horizontal')}</option>
                        <option value="vertical" ${orientation === 'vertical' ? 'selected' : ''}>${T('vertical', 'Vertical')}</option>
                    </select>
                </div>
                <div class="venuera-prop-group">
                    <label>${T('length', 'Length')}</label>
                    <input type="number" name="length" class="venuera-prop-input" value="${data.length || 200}" min="10" max="1000">
                </div>
                <div class="venuera-prop-group">
                    <label>${T('color', 'Color')}</label>
                    <input type="text" name="stroke" class="venuera-color-picker" value="${obj.stroke || '#cccccc'}">
                </div>
                <div class="venuera-prop-group">
                    <label>${T('thickness', 'Thickness')}</label>
                    <input type="number" name="strokeWidth" class="venuera-prop-input" value="${obj.strokeWidth || 1}" min="1" max="10">
                </div>
            </div>
        `;
    };

    /**
     * Bind property change events.
     * 
     * @param {fabric.Object} obj Selected object.
     */
    TicketDesigner.bindPropertyEvents = function(obj) {
        var self = this;
        var $content = $('#venuera-properties-content');

        // Remove any handlers bound by a previous selection. bindPropertyEvents
        // runs on every selection; without this, the delegated click/change
        // handlers ACCUMULATE — each closing over a previously-selected object —
        // so e.g. clicking a text-align button would re-fire for every element
        // ever selected (changing all of them). The `.tdprop` namespace scopes
        // the removal to our own handlers.
        $content.off('.tdprop');

        // Input changes. Color pickers are handled separately via the
        // wpColorPicker `change` callback (initColorPickers) so they fire once
        // per committed color against the live active object -- NOT per keystroke
        // (which would regenerate/swap bitmap elements on every character).
        $content.on('change.tdprop input.tdprop', '.venuera-prop-input, .venuera-prop-select, .venuera-prop-range', function() {
            var name = $(this).attr('name');
            var value = $(this).val();
            
            // Handle checkbox
            if ($(this).attr('type') === 'checkbox') {
                value = $(this).is(':checked');
            }
            
            // Update range display (span may live inside the label, so search
            // the surrounding group rather than only siblings).
            if ($(this).hasClass('venuera-prop-range')) {
                $(this).closest('.venuera-prop-group').find('.venuera-range-value').text(value);
            }

            self.updateElementProperty(obj, name, value);
        });
        
        // Checkbox changes
        $content.on('change.tdprop', 'input[type="checkbox"]', function() {
            var name = $(this).attr('name');
            var value = $(this).is(':checked');
            self.updateElementProperty(obj, name, value);
        });

        // Text align buttons
        $content.on('click.tdprop', '.venuera-align-btn', function() {
            var align = $(this).data('align');
            $content.find('.venuera-align-btn').removeClass('active');
            $(this).addClass('active');
            self.updateElementProperty(obj, 'textAlign', align);
        });

        // Image select
        $content.on('click.tdprop', '.venuera-select-image', function() {
            self.openMediaLibrary(obj);
        });

        // Image remove
        $content.on('click.tdprop', '.venuera-remove-image', function() {
            $content.find('.venuera-image-src').val('');
            $content.find('.venuera-image-preview').empty();
            $(this).hide();
            self.updateElementProperty(obj, 'src', '');
        });
    };

    /**
     * Re-bind the property-panel events to a new fabric object.
     *
     * Used after a QR/barcode/image element is regenerated and swapped on the
     * canvas: the existing delegated handlers close over the old (removed)
     * object, so we re-register them against the replacement. The panel markup
     * is left untouched to preserve the user's current input focus.
     *
     * @param {fabric.Object} obj The replacement object.
     */
    TicketDesigner.rebindPropertyEvents = function(obj) {
        this.bindPropertyEvents(obj);
    };

    /**
     * Update element property.
     * 
     * @param {fabric.Object} obj   Object.
     * @param {string}        name  Property name.
     * @param {*}             value Property value.
     */
    TicketDesigner.updateElementProperty = function(obj, name, value) {
        // Convert numeric values.
        if (['x', 'y', 'width', 'height', 'fontSize', 'strokeWidth', 'rx', 'size', 'length', 'rotation', 'opacity', 'zoom'].includes(name)) {
            value = parseInt(value, 10);
            if (isNaN(value)) { value = 0; }
        }

        var data = obj.elementData || {};
        var baseType = data.baseType;

        // ---- Shared transform: rotation / opacity (all element types) ----
        if (name === 'rotation') {
            obj.set('angle', value);
            if (obj.elementData) obj.elementData.rotation = value;
            obj.setCoords();
            this.canvas.renderAll();
            this.markDirty();
            return;
        }
        if (name === 'opacity') {
            var op = Math.max(0, Math.min(100, value)) / 100;
            obj.set('opacity', op);
            if (obj.elementData) obj.elementData.opacity = op;
            this.canvas.renderAll();
            this.markDirty();
            return;
        }

        // ---- Position (all element types) ----
        if (name === 'x') {
            obj.set('left', value);
            obj.setCoords();
            this.canvas.renderAll();
            this.markDirty();
            return;
        }
        if (name === 'y') {
            obj.set('top', value);
            obj.setCoords();
            this.canvas.renderAll();
            this.markDirty();
            return;
        }

        // ---- Generated bitmap/group elements: regenerate in place ----
        if (baseType === 'qrcode') {
            this.applyQRProperty(obj, name, value);
            return;
        }
        if (baseType === 'barcode') {
            this.applyBarcodeProperty(obj, name, value);
            return;
        }
        if (baseType === 'image') {
            this.applyImageProperty(obj, name, value);
            return;
        }

        // ---- Width / Height for shapes & textboxes ----
        if (name === 'width') {
            if (obj.type === 'line') {
                this.setLineLength(obj, value);
            } else {
                // Rect & Textbox: set real width and reset scale (so the value
                // is the literal box width that drives wrapping / text-align).
                obj.set('width', value);
                obj.set('scaleX', 1);
                if (obj.elementData) obj.elementData.width = value;
            }
            obj.setCoords();
            this.canvas.renderAll();
            this.markDirty();
            return;
        }
        if (name === 'height') {
            if (obj.type === 'line') {
                // For horizontal lines the meaningful axis is width; height is
                // strokeWidth and not directly editable here.
                obj.set('strokeWidth', value);
                if (obj.elementData) obj.elementData.height = value;
            } else {
                obj.set('height', value);
                obj.set('scaleY', 1);
                if (obj.elementData) obj.elementData.height = value;
            }
            obj.setCoords();
            this.canvas.renderAll();
            this.markDirty();
            return;
        }

        // ---- Line-specific ----
        if (name === 'orientation') {
            this.setLineOrientation(obj, value);
            this.canvas.renderAll();
            this.markDirty();
            return;
        }
        if (name === 'length') {
            this.setLineLength(obj, value);
            this.canvas.renderAll();
            this.markDirty();
            return;
        }

        // ---- Text properties ----
        if (name === 'text') {
            obj.set('text', value);
            if (obj.elementData) obj.elementData.staticText = value;
        } else if (['fontSize', 'fontFamily', 'fontWeight', 'fontStyle'].includes(name)) {
            obj.set(name, value);
            if (obj.elementData) { obj.elementData[name] = value; }
            // Make sure the (web) font is loaded before measuring/painting,
            // and once it is, FORCE fabric to recompute the text layout —
            // without re-running initDimensions() fabric paints with the
            // cached character bounds from the previous font, so the visible
            // glyphs don't change on the first click and the user has to
            // toggle to another font and back to see the new one apply.
            if (name === 'fontFamily' || name === 'fontWeight' || name === 'fontStyle') {
                var targetObj = obj;
                this.ensureFontLoaded(targetObj.fontFamily, function() {
                    try {
                        // Drop fabric's internal text-measurement caches so
                        // the next paint re-runs them with the now-loaded
                        // font metrics.
                        if (typeof targetObj._clearCache === 'function') {
                            targetObj._clearCache();
                        }
                        if (typeof targetObj.initDimensions === 'function') {
                            targetObj.initDimensions();
                        }
                        if (typeof targetObj.setCoords === 'function') {
                            targetObj.setCoords();
                        }
                    } catch (e) {}
                    if (TicketDesigner.canvas) { TicketDesigner.canvas.requestRenderAll(); }
                });
            }
        } else if (name === 'textAlign') {
            obj.set('textAlign', value);
            if (obj.elementData) obj.elementData.textAlign = value;
        }
        // ---- Color properties ----
        else if (['fill', 'stroke'].includes(name)) {
            obj.set(name, value);
        }
        // ---- Border ----
        else if (name === 'strokeWidth') {
            obj.set('strokeWidth', value);
        }
        // ---- Corner radius ----
        else if (name === 'rx') {
            obj.set('rx', value);
            obj.set('ry', value);
        }
        // ---- Element data properties (data fields, labels, etc.) ----
        else if (obj.elementData) {
            obj.elementData[name] = value;

            // Update display for data field changes (text elements).
            if (name === 'dataField') {
                var newText = this.getSampleData(value);
                // Custom attendee fields have no sample value; show their friendly
                // label on the canvas instead of the raw "attendee_field_<id>" key.
                if (newText === '[' + value + ']' && value && value.indexOf('attendee_field_') === 0 && this.customFields) {
                    var cf = this.customFields.filter(function(f) { return f.id === value; })[0];
                    if (cf) { newText = '[' + cf.label + ']'; }
                }
                if (obj.elementData.label) {
                    newText = obj.elementData.label + ' ' + newText;
                }
                obj.set('text', newText);
            } else if (name === 'label') {
                var fieldValue = this.getSampleData(obj.elementData.dataField);
                if (value) {
                    obj.set('text', value + ' ' + fieldValue);
                } else {
                    obj.set('text', fieldValue);
                }
            }
        }

        this.canvas.renderAll();
        this.markDirty();
    };

    /**
     * Set the length of a line on its current orientation axis.
     *
     * @param {fabric.Object} obj   Line object.
     * @param {number}        value New length.
     */
    TicketDesigner.setLineLength = function(obj, value) {
        var data = obj.elementData || {};
        if (data.orientation === 'vertical') {
            obj.set({ x1: 0, y1: 0, x2: 0, y2: value });
        } else {
            obj.set({ x1: 0, y1: 0, x2: value, y2: 0 });
        }
        if (obj.elementData) obj.elementData.length = value;
        obj.setCoords();
    };

    /**
     * Flip a line between horizontal and vertical, keeping its length.
     *
     * @param {fabric.Object} obj         Line object.
     * @param {string}        orientation 'horizontal' | 'vertical'.
     */
    TicketDesigner.setLineOrientation = function(obj, orientation) {
        var data = obj.elementData || {};
        var length = data.length || Math.abs(obj.x2 - obj.x1) || Math.abs(obj.y2 - obj.y1) || 200;

        if (orientation === 'vertical') {
            obj.set({ x1: 0, y1: 0, x2: 0, y2: length });
        } else {
            obj.set({ x1: 0, y1: 0, x2: length, y2: 0 });
        }
        if (obj.elementData) {
            obj.elementData.orientation = orientation;
            obj.elementData.length = length;
        }
        obj.setCoords();
    };

    /**
     * Apply a QR-code property change and regenerate the QR bitmap live.
     *
     * @param {fabric.Object} obj   QR fabric object.
     * @param {string}        name  Property name.
     * @param {*}             value New value.
     */
    TicketDesigner.applyQRProperty = function(obj, name, value) {
        if (!obj.elementData) return;
        // QR codes are square: a Width/Height edit maps to the QR size.
        if (name === 'width' || name === 'height') {
            obj.elementData.size = value;
        } else if (name === 'border') {
            // Sidebar exposes Border as a simple on/off toggle; the visual
            // pipeline still works in pixel-precision via `borderWidth`,
            // so flip 1 ↔ 0 here. The corner radius is independent.
            obj.elementData.borderWidth = value ? 1 : 0;
        } else {
            obj.elementData[name] = value;
        }
        TicketDesigner.BaseElements.regenerateQRCode(obj);
        this.markDirty();
    };

    /**
     * Apply a barcode property change and regenerate the placeholder live.
     *
     * @param {fabric.Object} obj   Barcode fabric object.
     * @param {string}        name  Property name.
     * @param {*}             value New value.
     */
    TicketDesigner.applyBarcodeProperty = function(obj, name, value) {
        if (!obj.elementData) return;
        if (name === 'border') {
            // Sidebar Border toggle maps to a fixed 1px border in the
            // underlying model (or 0 = none). Keeps the visual code path
            // unchanged while giving the user a simple on/off control.
            obj.elementData.borderWidth = value ? 1 : 0;
        } else {
            obj.elementData[name] = value;
        }
        // Color/format/data/size changes all require a fresh placeholder.
        TicketDesigner.BaseElements.regenerateBarcode(obj);
        this.markDirty();
    };

    /**
     * Apply an image-element property change.
     *
     * - src: load a fabric.Image and swap the placeholder in place (or restore
     *   the dashed placeholder when cleared).
     * - width/height: resize via scale on the real image, or width/height on
     *   the placeholder rect.
     * - fit / dataField / mapType / zoom: store on elementData.
     *
     * @param {fabric.Object} obj   Image fabric object (image or placeholder).
     * @param {string}        name  Property name.
     * @param {*}             value New value.
     */
    TicketDesigner.applyImageProperty = function(obj, name, value) {
        var self = this;
        var data = obj.elementData || {};

        if (name === 'src') {
            this.swapImageSource(obj, value);
            return;
        }

        if (name === 'width' || name === 'height') {
            var targetW = name === 'width' ? value : Math.round((obj.width || 0) * (obj.scaleX || 1));
            var targetH = name === 'height' ? value : Math.round((obj.height || 0) * (obj.scaleY || 1));

            if (obj.type === 'image') {
                if (obj.width) obj.set('scaleX', targetW / obj.width);
                if (obj.height) obj.set('scaleY', targetH / obj.height);
            } else {
                // Placeholder rect / group.
                obj.set('width', targetW);
                obj.set('height', targetH);
                obj.set('scaleX', 1);
                obj.set('scaleY', 1);
            }
            obj.setCoords();
            if (obj.elementData) {
                obj.elementData.width = targetW;
                obj.elementData.height = targetH;
            }
            this.canvas.renderAll();
            this.markDirty();
            return;
        }

        // fit / dataField / mapType / zoom and any other metadata.
        if (obj.elementData) obj.elementData[name] = value;
        this.canvas.renderAll();
        this.markDirty();
    };

    /**
     * Swap an image element's source: load a real picture or restore the
     * dashed placeholder, preserving position/size/id/selection.
     *
     * @param {fabric.Object} obj Current image or placeholder object.
     * @param {string}        src New image URL ('' to clear).
     */
    TicketDesigner.swapImageSource = function(obj, src) {
        var self = this;
        var data = obj.elementData || {};
        var width = data.width || Math.round((obj.width || 100) * (obj.scaleX || 1));
        var height = data.height || Math.round((obj.height || 100) * (obj.scaleY || 1));
        var left = obj.left;
        var top = obj.top;
        var angle = obj.angle || 0;
        var opacity = obj.opacity != null ? obj.opacity : 1;

        if (src) {
            fabric.Image.fromURL(src, function(img) {
                img.set({
                    left: left,
                    top: top,
                    scaleX: width / img.width,
                    scaleY: height / img.height,
                    angle: angle,
                    opacity: opacity
                });
                img.elementData = {
                    id: data.id,
                    type: data.type || 'custom_image',
                    baseType: 'image',
                    src: src,
                    fit: data.fit || 'contain',
                    dataField: data.dataField || null,
                    width: width,
                    height: height,
                    rotation: angle,
                    opacity: opacity
                };
                TicketDesigner.BaseElements.swapCanvasObject(obj, img);
                self.markDirty();
            });
            return;
        }

        // Restore the dashed placeholder.
        var placeholder = new fabric.Rect({
            left: left,
            top: top,
            width: width,
            height: height,
            fill: '#f0f0f0',
            stroke: '#cccccc',
            strokeWidth: 1,
            strokeDashArray: [5, 5],
            angle: angle,
            opacity: opacity
        });
        placeholder.elementData = {
            id: data.id,
            type: data.type || 'custom_image',
            baseType: 'image',
            src: '',
            fit: data.fit || 'contain',
            dataField: data.dataField || null,
            width: width,
            height: height,
            rotation: angle,
            opacity: opacity
        };
        TicketDesigner.BaseElements.swapCanvasObject(obj, placeholder);
        self.markDirty();
    };

    /**
     * Open media library.
     * 
     * @param {fabric.Object} obj Object to update.
     */
    TicketDesigner.openMediaLibrary = function(obj) {
        var self = this;
        
        var frame = wp.media({
            title: venueraTicketDesigner.strings.selectImage,
            button: { text: venueraTicketDesigner.strings.useImage },
            multiple: false
        });
        
        frame.on('select', function() {
            var attachment = frame.state().get('selection').first().toJSON();
            var url = attachment.url;
            
            // Update property panel
            $('#venuera-properties-content .venuera-image-src').val(url);
            $('#venuera-properties-content .venuera-image-preview').html('<img src="' + url + '">');
            $('#venuera-properties-content .venuera-remove-image').show();
            
            // Update element
            self.updateElementProperty(obj, 'src', url);
        });

        frame.open();
    };

    /**
     * Delegated change handler for the inline "Custom fields for" picker
     * (lives inside the properties panel for data-bound elements). Updates
     * the persisted currentFieldContext and fetches the matching attendee
     * fields via the same loadCustomFields helper the (now-removed) header
     * selector used to call.
     */
    $(document).on('change', '.venuera-field-context-inline', function() {
        var value = String($(this).val() || '');
        TicketDesigner.currentFieldContext = value;

        // The picker selection is part of the template's settings, so flag
        // unsaved-changes whenever the user changes it.
        if (typeof TicketDesigner.markDirty === 'function') {
            TicketDesigner.markDirty();
        }

        if (!value) {
            // No context picked — leave the cached labels in place (we want
            // existing canvas labels to keep their friendly names), just clear
            // the live status notice.
            if (typeof TicketDesigner.setFieldContextStatus === 'function') {
                TicketDesigner.setFieldContextStatus('', '');
            }
            return;
        }

        var parts = value.split(':');
        var type  = parts[0];
        var id    = parseInt(parts[1], 10) || 0;

        TicketDesigner.loadCustomFields({
            eventId:   type === 'event' ? id : 0,
            productId: type === 'product' ? id : 0
        });
    });

})(jQuery);

