/**
 * Tickera Ticket Designer - Data Elements
 *
 * Data-bound text fields. These are GENERATED from the Tickera field list
 * (venueraTicketDesigner.dataFields) provided by TC_Ticket_Designer_Fields, so
 * the palette always reflects the real Tickera fields — core event/ticket/
 * attendee/order data, Seating, WooCommerce (Bridge), and anything an add-on
 * registers via tickera_register_template_element(). No fields are hardcoded.
 */

(function($) {
    'use strict';

    if (typeof TicketDesigner === 'undefined') {
        return;
    }

    var Registry = TicketDesigner.ElementRegistry;

    // ===== GENERIC DYNAMIC TEXT =====

    /**
     * Dynamic Text
     *
     * A generic data-bound text element. Carries a `dataField` that the
     * renderer resolves as ticket_data[dataField]. The field can be changed in
     * the properties panel via the grouped "Field" picker.
     */
    Registry.register('dynamic_text', {
        label: 'Dynamic Text',
        category: 'ticket',
        icon: 'editor-textcolor',
        description: 'Text bound to a ticket data field',
        baseType: 'text',
        defaults: {
            x: 50,
            y: 50,
            dataField: 'event_name',
            fontSize: 14,
            fontFamily: 'Arial',
            fontWeight: 'normal',
            fontStyle: 'normal',
            fill: '#333333',
            textAlign: 'left',
            label: '',
            labelPosition: 'before',
            conditional: false
        },

        create: function(options) {
            if (!options.dataField) {
                options.dataField = 'event_name';
            }
            if (!options.text) {
                options.text = TicketDesigner.getSampleData(options.dataField);
            }

            var text = TicketDesigner.BaseElements.createText('dynamic_text', options);
            text.elementData.type = 'dynamic_text';
            return text;
        }
    });

    // ===== GENERATED TICKERA DATA FIELDS =====

    // Default icon per Tickera field group, used for the palette chips.
    var iconByGroup = {
        event:    'calendar-alt',
        ticket:   'tickets-alt',
        attendee: 'admin-users',
        order:    'clipboard',
        seating:  'screenoptions',
        woo:      'cart',
        addons:   'admin-plugins'
    };

    /**
     * Register one quick-add element per Tickera data field. Each is a
     * dynamic_text preset with a fixed dataField, so it renders exactly like a
     * dynamic text element bound to that field.
     *
     * @param {string} fieldKey  ticket_data key (e.g. "event_name", "el_xxx").
     * @param {string} label     Human label.
     * @param {string} groupKey  Field group / palette category.
     */
    function registerFieldElement(fieldKey, label, groupKey) {
        if (!fieldKey || fieldKey === 'dynamic_text') {
            return;
        }
        // Never override an already-registered element (e.g. a native visual
        // element id). Tickera text fields don't collide with those.
        if (Registry.get(fieldKey)) {
            return;
        }

        var category = (Registry.categories && Registry.categories[groupKey]) ? groupKey : 'ticket';

        Registry.register(fieldKey, {
            label: label || fieldKey,
            category: category,
            icon: iconByGroup[groupKey] || 'editor-textcolor',
            description: label || fieldKey,
            baseType: 'text',
            defaults: {
                x: 50,
                y: 50,
                dataField: fieldKey,
                fontSize: 14,
                fontFamily: 'Arial',
                fontWeight: 'normal',
                fontStyle: 'normal',
                fill: '#333333',
                textAlign: 'left',
                label: '',
                labelPosition: 'before',
                conditional: false
            },

            create: (function(boundField) {
                return function(options) {
                    if (!options.dataField) {
                        options.dataField = boundField;
                    }
                    if (!options.text) {
                        options.text = TicketDesigner.getSampleData(options.dataField);
                    }
                    var text = TicketDesigner.BaseElements.createText(boundField, options);
                    // Treat every generated field as a dynamic text binding so
                    // the renderer/properties panel handle it uniformly.
                    text.elementData.type = 'dynamic_text';
                    text.elementData.dataField = boundField;
                    return text;
                };
            })(fieldKey)
        });
    }

    var groups = (window.venueraTicketDesigner && venueraTicketDesigner.dataFields) || {};

    Object.keys(groups).forEach(function(groupKey) {
        var group = groups[groupKey] || {};
        var fields = group.fields || {};
        Object.keys(fields).forEach(function(fieldKey) {
            registerFieldElement(fieldKey, fields[fieldKey], groupKey);
        });
    });

})(jQuery);
