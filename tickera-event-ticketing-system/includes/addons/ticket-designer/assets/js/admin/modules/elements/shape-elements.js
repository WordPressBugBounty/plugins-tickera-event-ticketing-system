/**
 * Venuera Ticket Designer - Shape Elements
 * 
 * Rectangles, lines, dividers and other shapes.
 */

(function($) {
    'use strict';

    if (typeof TicketDesigner === 'undefined') {
        return;
    }

    var Registry = TicketDesigner.ElementRegistry;

    /**
     * Rectangle
     */
    Registry.register('rectangle', {
        label: 'Rectangle',
        category: 'shapes',
        // 'marker' is the Dashicons map-pin, which reads as a circle —
        // hence the confusion. 'image-crop' is a clean rectangle outline.
        icon: 'image-crop',
        description: 'Add a rectangular shape',
        baseType: 'rectangle',
        defaults: {
            x: 50,
            y: 50,
            width: 200,
            height: 100,
            fill: '#f0f0f0',
            stroke: '#cccccc',
            strokeWidth: 1,
            rx: 0,
            ry: 0
        },
        
        getProperties: function(element) {
            return {
                width: Math.round(element.width * element.scaleX),
                height: Math.round(element.height * element.scaleY),
                fill: element.fill,
                stroke: element.stroke,
                strokeWidth: element.strokeWidth,
                rx: element.rx || 0,
                ry: element.ry || 0
            };
        },
        
        updateFromProperties: function(element, props) {
            if (props.width !== undefined) {
                element.set('width', props.width);
                element.set('scaleX', 1);
            }
            if (props.height !== undefined) {
                element.set('height', props.height);
                element.set('scaleY', 1);
            }
            if (props.fill !== undefined) element.set('fill', props.fill);
            if (props.stroke !== undefined) element.set('stroke', props.stroke);
            if (props.strokeWidth !== undefined) element.set('strokeWidth', props.strokeWidth);
            if (props.rx !== undefined) {
                element.set('rx', props.rx);
                element.set('ry', props.rx);
            }
        }
    });

    /**
     * Header Background
     */
    Registry.register('header_bg', {
        label: 'Header Background',
        category: 'shapes',
        icon: 'align-full-width',
        description: 'Full-width header background',
        baseType: 'rectangle',
        defaults: {
            x: 0,
            y: 0,
            width: 600,
            height: 60,
            fill: '#3659E3',
            stroke: 'transparent',
            strokeWidth: 0,
            rx: 0,
            ry: 0
        },
        
        create: function(options) {
            var rect = new fabric.Rect({
                left: options.x != null ? options.x : 0,
                top: options.y != null ? options.y : 0,
                width: options.width || TicketDesigner.templateWidth,
                height: options.height || 60,
                fill: options.fill || '#3659E3',
                stroke: 'transparent',
                strokeWidth: 0,
                angle: options.rotation || 0,
                opacity: options.opacity != null ? options.opacity : 1,
                selectable: true,
                evented: true
            });

            rect.elementData = {
                id: options.id,
                type: 'header_bg',
                baseType: 'rectangle',
                rotation: options.rotation || 0,
                opacity: options.opacity != null ? options.opacity : 1
            };

            return rect;
        }
    });

    /**
     * Footer Background
     */
    Registry.register('footer_bg', {
        label: 'Footer Background',
        category: 'shapes',
        icon: 'align-full-width',
        description: 'Full-width footer background',
        baseType: 'rectangle',
        defaults: {
            x: 0,
            y: 220,
            width: 600,
            height: 30,
            fill: '#f5f5f5',
            stroke: '#e0e0e0',
            strokeWidth: 1,
            rx: 0,
            ry: 0
        },
        
        create: function(options) {
            var height = options.height || 30;
            var rect = new fabric.Rect({
                left: options.x != null ? options.x : 0,
                top: options.y != null ? options.y : (TicketDesigner.templateHeight - height),
                width: options.width || TicketDesigner.templateWidth,
                height: height,
                fill: options.fill || '#f5f5f5',
                stroke: options.stroke || '#e0e0e0',
                strokeWidth: options.strokeWidth || 1,
                angle: options.rotation || 0,
                opacity: options.opacity != null ? options.opacity : 1,
                selectable: true,
                evented: true
            });

            rect.elementData = {
                id: options.id,
                type: 'footer_bg',
                baseType: 'rectangle',
                rotation: options.rotation || 0,
                opacity: options.opacity != null ? options.opacity : 1
            };

            return rect;
        }
    });

    /**
     * Sidebar/Panel
     */
    Registry.register('sidebar_panel', {
        label: 'Side Panel',
        category: 'shapes',
        icon: 'align-right',
        description: 'Vertical side panel for codes',
        baseType: 'rectangle',
        defaults: {
            x: 450,
            y: 0,
            width: 150,
            height: 250,
            fill: '#fafafa',
            stroke: '#e0e0e0',
            strokeWidth: 1,
            rx: 0,
            ry: 0
        },
        
        create: function(options) {
            var rect = new fabric.Rect({
                left: options.x != null ? options.x : (TicketDesigner.templateWidth - 150),
                top: options.y != null ? options.y : 0,
                width: options.width || 150,
                height: options.height || TicketDesigner.templateHeight,
                fill: options.fill || '#fafafa',
                stroke: options.stroke || '#e0e0e0',
                strokeWidth: options.strokeWidth || 1,
                angle: options.rotation || 0,
                opacity: options.opacity != null ? options.opacity : 1,
                selectable: true,
                evented: true
            });

            rect.elementData = {
                id: options.id,
                type: 'sidebar_panel',
                baseType: 'rectangle',
                rotation: options.rotation || 0,
                opacity: options.opacity != null ? options.opacity : 1
            };

            return rect;
        }
    });

    /**
     * Horizontal Line/Divider
     */
    Registry.register('horizontal_line', {
        label: 'Horizontal Line',
        category: 'shapes',
        icon: 'minus',
        description: 'Horizontal divider line',
        baseType: 'line',
        defaults: {
            x: 20,
            y: 100,
            width: 200,
            orientation: 'horizontal',
            stroke: '#e0e0e0',
            strokeWidth: 1
        },
        
        getProperties: function(element) {
            var data = element.elementData || {};
            return {
                width: data.length || 200,
                stroke: element.stroke,
                strokeWidth: element.strokeWidth
            };
        },
        
        updateFromProperties: function(element, props) {
            if (props.width !== undefined) {
                element.set('x2', props.width);
                element.elementData.length = props.width;
            }
            if (props.stroke !== undefined) element.set('stroke', props.stroke);
            if (props.strokeWidth !== undefined) element.set('strokeWidth', props.strokeWidth);
        }
    });

    /**
     * Vertical Line/Divider
     */
    Registry.register('vertical_line', {
        label: 'Vertical Line',
        category: 'shapes',
        icon: 'editor-insertmore',
        description: 'Vertical divider line',
        baseType: 'line',
        defaults: {
            x: 440,
            y: 10,
            width: 230,
            orientation: 'vertical',
            stroke: '#e0e0e0',
            strokeWidth: 1
        }
    });

    /**
     * Dashed Line
     */
    Registry.register('dashed_line', {
        label: 'Dashed Line',
        category: 'shapes',
        icon: 'ellipsis',
        description: 'Dashed divider or tear line',
        baseType: 'line',
        defaults: {
            x: 20,
            y: 200,
            width: 560,
            orientation: 'horizontal',
            stroke: '#cccccc',
            strokeWidth: 1
        },
        
        create: function(options) {
            var length = options.length || options.width || 560;
            var isVertical = options.orientation === 'vertical';
            var coords = isVertical ? [0, 0, 0, length] : [0, 0, length, 0];

            var line = new fabric.Line(coords, {
                left: options.x != null ? options.x : 20,
                top: options.y != null ? options.y : 200,
                stroke: options.stroke || '#cccccc',
                strokeWidth: options.strokeWidth || 1,
                strokeDashArray: [5, 5],
                angle: options.rotation || 0,
                opacity: options.opacity != null ? options.opacity : 1
            });

            line.elementData = {
                id: options.id,
                type: 'dashed_line',
                baseType: 'line',
                orientation: options.orientation || 'horizontal',
                length: length,
                rotation: options.rotation || 0,
                opacity: options.opacity != null ? options.opacity : 1
            };

            return line;
        }
    });

    /**
     * Rounded Box
     */
    Registry.register('rounded_box', {
        label: 'Rounded Box',
        category: 'shapes',
        icon: 'button',
        description: 'Rectangle with rounded corners',
        baseType: 'rectangle',
        defaults: {
            x: 50,
            y: 50,
            width: 150,
            height: 80,
            fill: '#ffffff',
            stroke: '#3659E3',
            strokeWidth: 2,
            rx: 10,
            ry: 10
        }
    });

    /**
     * Border/Frame
     */
    Registry.register('ticket_border', {
        label: 'Ticket Border',
        category: 'shapes',
        icon: 'editor-table',
        description: 'Border around entire ticket',
        baseType: 'rectangle',
        defaults: {
            x: 0,
            y: 0,
            width: 600,
            height: 250,
            fill: 'transparent',
            stroke: '#333333',
            strokeWidth: 2,
            rx: 0,
            ry: 0
        },
        
        create: function(options) {
            var rect = new fabric.Rect({
                left: options.x != null ? options.x : 0,
                top: options.y != null ? options.y : 0,
                width: options.width || TicketDesigner.templateWidth,
                height: options.height || TicketDesigner.templateHeight,
                fill: 'transparent',
                stroke: options.stroke || '#333333',
                strokeWidth: options.strokeWidth || 2,
                angle: options.rotation || 0,
                opacity: options.opacity != null ? options.opacity : 1,
                selectable: true,
                evented: true
            });

            rect.elementData = {
                id: options.id,
                type: 'ticket_border',
                baseType: 'rectangle',
                rotation: options.rotation || 0,
                opacity: options.opacity != null ? options.opacity : 1
            };

            return rect;
        }
    });

})(jQuery);

