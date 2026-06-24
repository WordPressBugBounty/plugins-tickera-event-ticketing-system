/**
 * Venuera Ticket Designer - Text Elements
 * 
 * Static and custom text elements.
 */

(function($) {
    'use strict';

    if (typeof TicketDesigner === 'undefined') {
        return;
    }

    var Registry = TicketDesigner.ElementRegistry;

    /**
     * Static Text (custom text)
     */
    Registry.register('static_text', {
        label: 'Custom Text',
        category: 'text',
        icon: 'editor-textcolor',
        description: 'Add custom static text',
        baseType: 'text',
        defaults: {
            x: 50,
            y: 50,
            text: 'Enter your text here',
            dataField: null,
            fontSize: 14,
            fontFamily: 'Arial',
            fontWeight: 'normal',
            fontStyle: 'normal',
            fill: '#333333',
            textAlign: 'left'
        },
        
        create: function(options) {
            var text = new fabric.Textbox(options.text || 'Enter your text here', {
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

            text.elementData = {
                id: options.id,
                type: 'static_text',
                baseType: 'text',
                staticText: options.text || 'Enter your text here',
                width: options.width || 200,
                textAlign: options.textAlign || 'left',
                rotation: options.rotation || 0,
                opacity: options.opacity != null ? options.opacity : 1
            };

            return text;
        },
        
        getProperties: function(element) {
            return {
                text: element.text,
                fontSize: element.fontSize,
                fontFamily: element.fontFamily,
                fontWeight: element.fontWeight,
                fontStyle: element.fontStyle,
                fill: element.fill,
                textAlign: element.textAlign
            };
        },
        
        updateFromProperties: function(element, props) {
            if (props.text !== undefined) {
                element.set('text', props.text);
                element.elementData.staticText = props.text;
            }
            if (props.fontSize !== undefined) element.set('fontSize', props.fontSize);
            if (props.fontFamily !== undefined) element.set('fontFamily', props.fontFamily);
            if (props.fontWeight !== undefined) element.set('fontWeight', props.fontWeight);
            if (props.fontStyle !== undefined) element.set('fontStyle', props.fontStyle);
            if (props.fill !== undefined) element.set('fill', props.fill);
            if (props.textAlign !== undefined) element.set('textAlign', props.textAlign);
        }
    });

    /**
     * Title/Heading
     */
    Registry.register('title_text', {
        label: 'Title/Heading',
        category: 'text',
        icon: 'heading',
        description: 'Large heading text',
        baseType: 'text',
        defaults: {
            x: 50,
            y: 20,
            text: 'Heading',
            dataField: null,
            fontSize: 28,
            fontFamily: 'Arial',
            fontWeight: 'bold',
            fill: '#333333',
            textAlign: 'left'
        },
        
        create: function(options) {
            var text = new fabric.Textbox(options.text || 'Heading', {
                left: options.x != null ? options.x : 50,
                top: options.y != null ? options.y : 20,
                width: options.width || 250,
                fontSize: options.fontSize || 28,
                fontFamily: options.fontFamily || 'Arial',
                fontWeight: options.fontWeight || 'bold',
                fill: options.fill || '#333333',
                textAlign: options.textAlign || 'left',
                angle: options.rotation || 0,
                opacity: options.opacity != null ? options.opacity : 1,
                originX: 'left',
                originY: 'top',
            });

            text.elementData = {
                id: options.id,
                type: 'title_text',
                baseType: 'text',
                staticText: options.text || 'Heading',
                width: options.width || 250,
                textAlign: options.textAlign || 'left',
                rotation: options.rotation || 0,
                opacity: options.opacity != null ? options.opacity : 1
            };

            return text;
        }
    });

    /**
     * Subtitle
     */
    Registry.register('subtitle_text', {
        label: 'Subtitle',
        category: 'text',
        icon: 'editor-paragraph',
        description: 'Smaller subtitle text',
        baseType: 'text',
        defaults: {
            x: 50,
            y: 55,
            text: 'Subtitle',
            dataField: null,
            fontSize: 16,
            fontFamily: 'Arial',
            fontWeight: 'normal',
            fill: '#666666',
            textAlign: 'left'
        },
        
        create: function(options) {
            var text = new fabric.Textbox(options.text || 'Subtitle', {
                left: options.x != null ? options.x : 50,
                top: options.y != null ? options.y : 55,
                width: options.width || 220,
                fontSize: options.fontSize || 16,
                fontFamily: options.fontFamily || 'Arial',
                fontWeight: options.fontWeight || 'normal',
                fill: options.fill || '#666666',
                textAlign: options.textAlign || 'left',
                angle: options.rotation || 0,
                opacity: options.opacity != null ? options.opacity : 1,
                originX: 'left',
                originY: 'top',
            });

            text.elementData = {
                id: options.id,
                type: 'subtitle_text',
                baseType: 'text',
                staticText: options.text || 'Subtitle',
                width: options.width || 220,
                textAlign: options.textAlign || 'left',
                rotation: options.rotation || 0,
                opacity: options.opacity != null ? options.opacity : 1
            };

            return text;
        }
    });

    /**
     * Label (small text for labeling fields)
     */
    Registry.register('label_text', {
        label: 'Label',
        category: 'text',
        icon: 'tag',
        description: 'Small label text',
        baseType: 'text',
        defaults: {
            x: 50,
            y: 80,
            text: 'Label:',
            dataField: null,
            fontSize: 11,
            fontFamily: 'Arial',
            fontWeight: 'normal',
            fill: '#888888',
            textAlign: 'left'
        },
        
        create: function(options) {
            var text = new fabric.Textbox(options.text || 'Label:', {
                left: options.x != null ? options.x : 50,
                top: options.y != null ? options.y : 80,
                width: options.width || 120,
                fontSize: options.fontSize || 11,
                fontFamily: options.fontFamily || 'Arial',
                fontWeight: options.fontWeight || 'normal',
                fill: options.fill || '#888888',
                textAlign: options.textAlign || 'left',
                angle: options.rotation || 0,
                opacity: options.opacity != null ? options.opacity : 1,
                originX: 'left',
                originY: 'top',
            });

            text.elementData = {
                id: options.id,
                type: 'label_text',
                baseType: 'text',
                staticText: options.text || 'Label:',
                width: options.width || 120,
                textAlign: options.textAlign || 'left',
                rotation: options.rotation || 0,
                opacity: options.opacity != null ? options.opacity : 1
            };

            return text;
        }
    });

    /**
     * Terms/Fine Print
     */
    Registry.register('terms_text', {
        label: 'Terms/Fine Print',
        category: 'text',
        icon: 'info-outline',
        description: 'Small disclaimer or terms text',
        baseType: 'text',
        defaults: {
            x: 20,
            y: 230,
            text: 'This ticket is non-refundable. Please bring valid ID.',
            dataField: null,
            fontSize: 8,
            fontFamily: 'Arial',
            fontWeight: 'normal',
            fontStyle: 'italic',
            fill: '#999999',
            textAlign: 'left'
        },
        
        create: function(options) {
            var text = new fabric.Textbox(options.text || 'Terms and conditions apply.', {
                left: options.x != null ? options.x : 20,
                top: options.y != null ? options.y : 230,
                width: options.width || 300,
                fontSize: options.fontSize || 8,
                fontFamily: options.fontFamily || 'Arial',
                fontWeight: options.fontWeight || 'normal',
                fontStyle: options.fontStyle || 'italic',
                fill: options.fill || '#999999',
                textAlign: options.textAlign || 'left',
                angle: options.rotation || 0,
                opacity: options.opacity != null ? options.opacity : 1,
                originX: 'left',
                originY: 'top',
            });

            text.elementData = {
                id: options.id,
                type: 'terms_text',
                baseType: 'text',
                staticText: options.text || 'Terms and conditions apply.',
                width: options.width || 300,
                textAlign: options.textAlign || 'left',
                rotation: options.rotation || 0,
                opacity: options.opacity != null ? options.opacity : 1
            };

            return text;
        }
    });

})(jQuery);

