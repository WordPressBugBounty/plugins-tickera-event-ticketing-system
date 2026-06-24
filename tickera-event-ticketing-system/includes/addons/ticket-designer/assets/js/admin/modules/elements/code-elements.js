/**
 * Venuera Ticket Designer - Code Elements
 * 
 * QR codes and barcodes for ticket validation.
 */

(function($) {
    'use strict';

    if (typeof TicketDesigner === 'undefined') {
        return;
    }

    var Registry = TicketDesigner.ElementRegistry;

    /**
     * QR Code
     */
    Registry.register('qr_code', {
        label: 'QR Code',
        category: 'codes',
        icon: 'grid-view',
        description: 'Scannable QR code for ticket validation',
        baseType: 'qrcode',
        defaults: {
            x: 480,
            y: 80,
            size: 100,
            dataField: 'ticket_id',
            errorCorrectionLevel: 'M',
            foreground: '#000000',
            background: '#ffffff',
            // Quiet zone (white space) around the QR modules, in the same
            // pixel units as `size`.
            padding: 5,
            // Optional rounded border around the QR's outer box. `border`
            // is off by default; corner radius defaults to a subtle 2px
            // so even without a visible border the QR card has a soft edge.
            borderWidth: 0,
            borderColor: '#000000',
            borderRadius: 2
        },

        getProperties: function(element) {
            var data = element.elementData || {};
            return {
                dataField: data.dataField || 'ticket_id',
                size: data.size || 100,
                errorCorrectionLevel: data.errorCorrectionLevel || 'M',
                foreground: data.foreground || '#000000',
                background: data.background || '#ffffff',
                padding: data.padding != null ? data.padding : 5,
                borderWidth: data.borderWidth != null ? data.borderWidth : 0,
                borderColor: data.borderColor || '#000000',
                borderRadius: data.borderRadius != null ? data.borderRadius : 2
            };
        },

        updateFromProperties: function(element, props) {
            if (props.size && props.size !== element.elementData.size) {
                // Resize QR code
                var scale = props.size / element.elementData.size;
                element.scale(element.scaleX * scale);
                element.elementData.size = props.size;
            }

            element.elementData.dataField = props.dataField;
            element.elementData.errorCorrectionLevel = props.errorCorrectionLevel;
            element.elementData.foreground = props.foreground;
            element.elementData.background = props.background;
            element.elementData.padding = props.padding != null ? props.padding : 5;
            element.elementData.borderWidth = props.borderWidth != null ? props.borderWidth : 0;
            element.elementData.borderColor = props.borderColor || '#000000';
            element.elementData.borderRadius = props.borderRadius != null ? props.borderRadius : 2;
        }
    });

    /**
     * Barcode
     */
    Registry.register('barcode', {
        label: 'Barcode',
        category: 'codes',
        icon: 'minus',
        description: 'Scannable barcode for ticket validation',
        baseType: 'barcode',
        defaults: {
            x: 400,
            y: 200,
            width: 150,
            height: 50,
            dataField: 'ticket_id',
            format: 'CODE128',
            showText: true,
            foreground: '#000000',
            background: '#ffffff',
            // Same quiet-zone + optional border treatment as the QR
            // element. Barcodes are usually wider than tall so the
            // default padding is a touch smaller than the QR default.
            padding: 3,
            borderWidth: 0,
            borderColor: '#000000',
            borderRadius: 0
        },

        getProperties: function(element) {
            var data = element.elementData || {};
            return {
                dataField: data.dataField || 'ticket_id',
                format: data.format || 'CODE128',
                width: data.width || 150,
                height: data.height || 50,
                showText: data.showText !== false,
                foreground: data.foreground || '#000000',
                background: data.background || '#ffffff',
                padding:      data.padding      != null ? data.padding      : 3,
                borderWidth:  data.borderWidth  != null ? data.borderWidth  : 0,
                borderColor:  data.borderColor  || '#000000',
                borderRadius: data.borderRadius != null ? data.borderRadius : 0
            };
        }
    });

    // Removed: 'ticket_number_formatted' ("Ticket Number") — not a classic
    // Tickera element. Ticket code/number are available as the "Ticket Code"
    // and "Ticket ID" data fields, and as the QR/Barcode elements above.

})(jQuery);

