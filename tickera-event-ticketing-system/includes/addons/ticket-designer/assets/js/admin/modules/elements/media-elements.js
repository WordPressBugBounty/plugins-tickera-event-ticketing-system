/**
 * Venuera Ticket Designer - Media Elements
 * 
 * Images, logos, and maps.
 */

(function($) {
    'use strict';

    if (typeof TicketDesigner === 'undefined') {
        return;
    }

    var Registry = TicketDesigner.ElementRegistry;

    /**
     * Custom Image
     */
    Registry.register('custom_image', {
        label: 'Image',
        category: 'media',
        icon: 'format-image',
        description: 'Add a custom image or logo',
        baseType: 'image',
        defaults: {
            x: 50,
            y: 50,
            width: 100,
            height: 100,
            src: '',
            fit: 'contain'
        },
        
        getProperties: function(element) {
            var data = element.elementData || {};
            return {
                src: data.src || '',
                width: data.width || element.width * element.scaleX,
                height: data.height || element.height * element.scaleY,
                fit: data.fit || 'contain'
            };
        },
        
        updateFromProperties: function(element, props) {
            if (props.src && props.src !== element.elementData.src) {
                // Load new image
                fabric.Image.fromURL(props.src, function(img) {
                    var left = element.left;
                    var top = element.top;
                    
                    TicketDesigner.canvas.remove(element);
                    
                    img.set({
                        left: left,
                        top: top,
                        scaleX: props.width / img.width,
                        scaleY: props.height / img.height,
                    });
                    
                    img.elementData = {
                        id: element.elementData.id,
                        type: 'custom_image',
                        baseType: 'image',
                        src: props.src,
                        fit: props.fit,
                        width: props.width,
                        height: props.height
                    };
                    
                    TicketDesigner.canvas.add(img);
                    TicketDesigner.canvas.setActiveObject(img);
                    TicketDesigner.canvas.renderAll();
                });
            }
            
            element.elementData.width = props.width;
            element.elementData.height = props.height;
            element.elementData.fit = props.fit;
        }
    });

    /**
     * Logo
     */
    Registry.register('logo', {
        label: 'Logo',
        category: 'media',
        icon: 'admin-site',
        description: 'Add your company or event logo',
        baseType: 'image',
        defaults: {
            x: 20,
            y: 20,
            width: 80,
            height: 40,
            src: '',
            fit: 'contain'
        }
    });

    /**
     * Event Image
     */
    Registry.register('event_image', {
        label: 'Event Image',
        category: 'media',
        icon: 'calendar',
        description: 'Display the event featured image',
        baseType: 'image',
        defaults: {
            x: 50,
            y: 50,
            width: 150,
            height: 100,
            dataField: 'event_image',
            fit: 'cover'
        },
        
        create: function(options) {
            var width = options.width || 150;
            var height = options.height || 100;
            
            // Create placeholder for event image
            var rect = new fabric.Rect({
                left: options.x != null ? options.x : 50,
                top: options.y != null ? options.y : 50,
                width: width,
                height: height,
                fill: '#e8e8e8',
                stroke: '#cccccc',
                strokeWidth: 1,
            });
            
            var text = new fabric.Text('Event Image', {
                fontSize: 12,
                fontFamily: 'Arial',
                fill: '#999999',
                originX: 'center',
                originY: 'center',
                left: width / 2,
                top: height / 2
            });
            
            var group = new fabric.Group([rect, text], {
                left: options.x != null ? options.x : 50,
                top: options.y != null ? options.y : 50,
                angle: options.rotation || 0,
                opacity: options.opacity != null ? options.opacity : 1,
            });

            group.elementData = {
                id: options.id,
                type: 'event_image',
                baseType: 'image',
                dataField: 'event_image',
                fit: options.fit || 'cover',
                width: width,
                height: height,
                rotation: options.rotation || 0,
                opacity: options.opacity != null ? options.opacity : 1
            };

            return group;
        }
    });


    /**
     * Sponsor Logo Area
     */
    /**
     * Factory for a data-bound image placeholder (mirrors event_image). The
     * src is resolved at render time from ticket_data[dataField], so Tickera
     * elements like the Event Logo, Sponsor Logo and Google Map print the real
     * image without hardcoding a URL in the template.
     */
    function makeDataImage(type, dataField, labelText, def, extra) {
        def = def || {};
        extra = extra || {};
        return {
            label: labelText,
            category: 'media',
            icon: def.icon || 'format-image',
            description: def.description || labelText,
            baseType: 'image',
            defaults: $.extend({
                x: def.x != null ? def.x : 50,
                y: def.y != null ? def.y : 50,
                width: def.width || 120,
                height: def.height || 60,
                dataField: dataField,
                fit: def.fit || 'contain'
            }, extra),
            create: function(options) {
                var width = options.width || (def.width || 120);
                var height = options.height || (def.height || 60);

                var rect = new fabric.Rect({
                    left: 0,
                    top: 0,
                    width: width,
                    height: height,
                    fill: '#e8e8e8',
                    stroke: '#cccccc',
                    strokeWidth: 1
                });
                var text = new fabric.Text(labelText, {
                    fontSize: 12,
                    fontFamily: 'Arial',
                    fill: '#999999',
                    originX: 'center',
                    originY: 'center',
                    left: width / 2,
                    top: height / 2
                });
                var group = new fabric.Group([rect, text], {
                    left: options.x != null ? options.x : (def.x != null ? def.x : 50),
                    top: options.y != null ? options.y : (def.y != null ? def.y : 50),
                    angle: options.rotation || 0,
                    opacity: options.opacity != null ? options.opacity : 1
                });
                group.elementData = {
                    id: options.id,
                    type: type,
                    baseType: 'image',
                    dataField: dataField,
                    fit: options.fit || (def.fit || 'contain'),
                    width: width,
                    height: height,
                    rotation: options.rotation || 0,
                    opacity: options.opacity != null ? options.opacity : 1
                };
                // Carry any element-specific settings (e.g. Google Map address/zoom/type).
                Object.keys(extra).forEach(function(k) {
                    group.elementData[k] = (options[k] != null) ? options[k] : extra[k];
                });
                return group;
            }
        };
    }

    // Sponsor Logo — bound to the event's sponsors logo (classic "Sponsors Logos").
    Registry.register('sponsor_logo', makeDataImage('sponsor_logo', 'sponsors_logo', 'Sponsor Logo', { icon: 'groups', x: 500, y: 20, width: 80, height: 40 }));

    // Event Logo — bound to the event logo (classic "Event Logo").
    Registry.register('event_logo', makeDataImage('event_logo', 'event_logo', 'Event Logo', { icon: 'format-image', x: 50, y: 20, width: 120, height: 60 }));

    // Google Map — static map built from per-element settings (address/zoom/type),
    // defaulting to the event location. Requires a Google Maps API key in Tickera
    // settings. Matches the classic "Google Map" element.
    Registry.register('google_map', makeDataImage('google_map', 'google_map', 'Google Map',
        { icon: 'location-alt', x: 50, y: 50, width: 200, height: 120, fit: 'cover' },
        { map_address: '', map_zoom: 14, map_maptype: 'roadmap' }
    ));

})(jQuery);

