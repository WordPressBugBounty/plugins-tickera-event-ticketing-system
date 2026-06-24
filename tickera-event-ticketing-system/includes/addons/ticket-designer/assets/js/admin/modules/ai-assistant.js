/**
 * AI Assistant Module (Ticket Designer)
 *
 * Adds an "AI Ticket Assistant" button to the floating toolbar. The user
 * describes a ticket layout (or a change) in plain language; the request goes
 * to the server-side WordPress 7.0 AI Client endpoint, which returns a
 * high-level "spec" of operations. This module executes those operations
 * against the existing Ticket Designer builders (TicketDesigner.addElement)
 * and the property updater (TicketDesigner.updateElementProperty) — so the AI
 * never has to emit raw Fabric.js JSON.
 */

(function($) {
    'use strict';

    if (typeof window.TicketDesigner === 'undefined') {
        console.error('TicketDesigner must be loaded before modules');
        return;
    }

    var TD = window.TicketDesigner;

    function cfg() {
        return (typeof venueraTicketDesigner !== 'undefined' && venueraTicketDesigner.ai) ? venueraTicketDesigner.ai : null;
    }

    function str(key, fallback) {
        var c = cfg();
        if (c && c.strings && typeof c.strings[key] !== 'undefined') {
            return c.strings[key];
        }
        return fallback;
    }

    function seeConsole() {
        return str('seeConsole', '(open the browser console — press F12 — for the full reason)');
    }

    function esc(s) {
        return (typeof TD.escapeHtml === 'function') ? TD.escapeHtml(s) : String(s);
    }

    $.extend(TD, {

        /* ----------------------------- UI ----------------------------- */

        initAiAssistant: function() {
            var c = cfg();
            if (c && c.enabled) {
                $('#venuera-td-ai-group').show();
            }
        },

        openTdAiModal: function() {
            var c = cfg();
            if (!c || !c.enabled) {
                this.showNotification(str('unavailable', 'AI is unavailable.'), 'warning');
                return;
            }
            if ($('#venuera-td-ai-modal').length) {
                $('#venuera-td-ai-modal').show();
                $('#venuera-td-ai-prompt').focus();
                return;
            }

            var examples = (c.strings && c.strings.examples) ? c.strings.examples : [];
            var examplesHtml = '';
            if (examples.length) {
                examplesHtml += '<div class="venuera-ai-examples"><span class="venuera-ai-examples-title">' +
                    esc(str('examplesTitle', 'Try:')) + '</span><ul>';
                examples.forEach(function(ex) {
                    examplesHtml += '<li><button type="button" class="venuera-ai-example">' + esc(ex) + '</button></li>';
                });
                examplesHtml += '</ul></div>';
            }

            var html =
                '<div class="venuera-ai-modal" id="venuera-td-ai-modal">' +
                  '<div class="venuera-ai-dialog" role="dialog" aria-modal="true">' +
                    '<div class="venuera-ai-header">' +
                      '<h2>' + esc(str('title', 'AI Ticket Assistant')) + '</h2>' +
                      '<button type="button" class="venuera-ai-close" aria-label="Close">&times;</button>' +
                    '</div>' +
                    '<p class="venuera-ai-subtitle">' + esc(str('subtitle', '')) + '</p>' +
                    '<textarea id="venuera-td-ai-prompt" rows="4" placeholder="' + esc(str('placeholder', '')) + '"></textarea>' +
                    examplesHtml +
                    '<label class="venuera-ai-clear"><input type="checkbox" id="venuera-td-ai-clear-first"> ' +
                      esc(str('clearFirst', 'Clear the current design before generating')) + '</label>' +
                    '<div class="venuera-ai-error" id="venuera-td-ai-error" style="display:none;"></div>' +
                    '<div class="venuera-ai-footer">' +
                      '<button type="button" class="button venuera-ai-cancel">' + esc(str('cancel', 'Cancel')) + '</button>' +
                      '<button type="button" class="button button-primary" id="venuera-td-ai-generate-btn">' + esc(str('generate', 'Generate')) + '</button>' +
                    '</div>' +
                  '</div>' +
                '</div>';

            $('body').append(html);
            $('#venuera-td-ai-prompt').focus();
        },

        closeTdAiModal: function() {
            $('#venuera-td-ai-modal').remove();
        },

        setTdAiBusy: function(busy) {
            var $btn = $('#venuera-td-ai-generate-btn');
            if (busy) {
                $btn.data('label', $btn.text()).prop('disabled', true).addClass('is-busy').text(str('generating', 'Thinking…'));
            } else {
                $btn.prop('disabled', false).removeClass('is-busy').text($btn.data('label') || str('generate', 'Generate'));
            }
        },

        showTdAiError: function(msg) {
            $('#venuera-td-ai-error').text(msg).show();
        },

        /* --------------------------- Request --------------------------- */

        // Template size (points) + existing elements, so the model can place new
        // elements within bounds and perform modifications.
        buildTdAiContext: function() {
            var items = [];
            if (this.canvas) {
                this.canvas.getObjects().forEach(function(o) {
                    if (!o || !o.elementData) {
                        return;
                    }
                    var d = o.elementData;
                    items.push({
                        type: d.type,
                        dataField: d.dataField || null,
                        text: d.staticText || (o.type === 'textbox' || o.type === 'i-text' ? o.text : '') || '',
                        x: Math.round(o.left),
                        y: Math.round(o.top)
                    });
                });
            }
            // Full list of element types the editor actually knows (base types
            // PLUS the pre-styled / pre-bound presets registered in JS), so the
            // AI is aware of every element it can place — not just the base set.
            var available = [];
            if (this.ElementRegistry && typeof this.ElementRegistry.getAll === 'function') {
                var all = this.ElementRegistry.getAll();
                Object.keys(all).forEach(function(id) {
                    var cfg = all[id] || {};
                    available.push({ id: id, label: cfg.label || id, category: cfg.category || '' });
                });
            }

            return {
                templateWidth: this.templateWidth,
                templateHeight: this.templateHeight,
                unit: 'pt',
                availableElements: available,
                elements: items
            };
        },

        runTdAiGenerate: function() {
            var self = this;
            var c = cfg();
            var prompt = ($('#venuera-td-ai-prompt').val() || '').trim();
            var clearFirst = $('#venuera-td-ai-clear-first').is(':checked');

            $('#venuera-td-ai-error').hide();

            if (!prompt) {
                self.showTdAiError(str('empty', 'Please describe what you want.'));
                return;
            }
            if (typeof venueraTicketDesigner === 'undefined') {
                self.showTdAiError(str('error', 'Something went wrong.'));
                return;
            }

            self.setTdAiBusy(true);

            $.ajax({
                url: venueraTicketDesigner.ajaxUrl,
                type: 'POST',
                data: {
                    action: (c && c.action) ? c.action : 'tc_designer_ticket_ai_generate',
                    nonce: venueraTicketDesigner.nonce,
                    prompt: prompt,
                    context: JSON.stringify(self.buildTdAiContext())
                },
                success: function(response) {
                    if (response && response.success && response.data && response.data.spec) {
                        try {
                            self.executeTdAiSpec(response.data.spec, clearFirst);
                            self.closeTdAiModal();
                            self.showNotification(response.data.spec.summary || str('done', 'Done'), 'success');
                        } catch (e) {
                            console.error('[Ticket AI] spec execution failed:', e, '\nspec:', response.data.spec);
                            self.showTdAiError(str('error', 'Something went wrong.') + ' — ' + (e && e.message ? e.message : e));
                        }
                    } else {
                        var msg = (response && response.data && response.data.message) ? response.data.message : str('error', 'Something went wrong.');
                        if (response && response.data && response.data.raw) {
                            console.error('[Ticket AI] unparseable model output:', response.data.raw);
                            msg += ' ' + seeConsole();
                        }
                        console.error('[Ticket AI] request returned no spec:', response);
                        self.showTdAiError(msg);
                    }
                },
                error: function(xhr) {
                    var msg;
                    if (xhr && xhr.responseJSON && xhr.responseJSON.data && xhr.responseJSON.data.message) {
                        msg = xhr.responseJSON.data.message;
                    } else if (xhr && xhr.status) {
                        msg = str('error', 'Something went wrong.') + ' (HTTP ' + xhr.status + (xhr.statusText ? ' ' + xhr.statusText : '') + ')';
                    } else {
                        msg = str('error', 'Something went wrong.') + ' ' + seeConsole();
                    }
                    console.error('[Ticket AI] AJAX error:', xhr && xhr.status, xhr && xhr.statusText, '\nresponse:', xhr && xhr.responseText);
                    self.showTdAiError(msg);
                },
                complete: function() {
                    self.setTdAiBusy(false);
                }
            });
        },

        /* -------------------------- Execution -------------------------- */

        executeTdAiSpec: function(spec, clearFirst) {
            var self = this;
            var ops = (spec && spec.operations) ? spec.operations : [];

            if (clearFirst) {
                self.tdAiClear();
            }

            ops.forEach(function(op) {
                try {
                    self.applyTdAiOperation(op);
                } catch (e) {
                    console.error('[Ticket AI] operation failed:', op, e);
                }
            });

            if (this.canvas) {
                this.canvas.discardActiveObject();
                this.canvas.renderAll();
            }
            if (typeof this.markDirty === 'function') {
                this.markDirty();
            }
            if (typeof this.saveToHistory === 'function') {
                this.saveToHistory();
            }
            if (typeof this.zoomToFit === 'function') {
                this.zoomToFit();
            }
        },

        applyTdAiOperation: function(op) {
            if (!op || !op.op) {
                return;
            }
            var type = String(op.op).toLowerCase();

            switch (type) {
                case 'clear':
                    this.tdAiClear();
                    break;

                case 'add_element':
                case 'add':
                    this.tdAiAddElement(op);
                    break;

                case 'update':
                case 'modify':
                case 'recolor':
                    this.tdAiUpdate(op);
                    break;

                case 'delete':
                    this.tdAiDelete(op.target);
                    break;

                default:
                    console.warn('[Ticket AI] unknown operation:', type);
            }
        },

        tdAiAddElement: function(op) {
            var elementType = op.elementType || op.type || op.element;
            if (!elementType) {
                console.warn('[Ticket AI] add_element without elementType:', op);
                return;
            }
            // Everything except the routing keys is treated as an element option.
            var options = {};
            Object.keys(op).forEach(function(k) {
                if (k === 'op' || k === 'elementType' || k === 'type' || k === 'element' || k === 'target' || k === 'props') {
                    return;
                }
                var v = op[k];
                if (v !== undefined && v !== null && v !== '') {
                    options[k] = v;
                }
            });
            // addElement builds the fabric object, adds it, selects it, and saves history.
            this.addElement(elementType, options);
        },

        // Find elements by type / static text / dataField (case-insensitive,
        // substring), or "all".
        tdAiFindElements: function(target) {
            var out = [];
            if (!this.canvas) {
                return out;
            }
            var all = (target === undefined || target === null || target === '' ||
                       String(target).toLowerCase() === 'all' || target === '*');
            var needle = all ? '' : String(target).toLowerCase();

            this.canvas.getObjects().forEach(function(o) {
                if (!o || !o.elementData) {
                    return;
                }
                if (all) {
                    out.push(o);
                    return;
                }
                var d = o.elementData;
                var hay = [
                    d.type || '',
                    d.dataField || '',
                    d.staticText || '',
                    (o.text || '')
                ].join(' ').toLowerCase();
                if (hay.indexOf(needle) !== -1) {
                    out.push(o);
                }
            });
            return out;
        },

        tdAiUpdate: function(op) {
            var self = this;
            // Accept either {props:{...}} or color shorthands at the top level.
            var props = (op.props && typeof op.props === 'object') ? op.props : {};
            if (op.color && props.fill === undefined) { props.fill = op.color; }
            if (op.fill && props.fill === undefined) { props.fill = op.fill; }

            var keys = Object.keys(props);
            if (!keys.length) {
                return;
            }
            this.tdAiFindElements(op.target).forEach(function(obj) {
                keys.forEach(function(k) {
                    try {
                        self.updateElementProperty(obj, k, props[k]);
                    } catch (e) {
                        console.error('[Ticket AI] update prop failed:', k, e);
                    }
                });
            });
        },

        tdAiDelete: function(target) {
            var self = this;
            this.tdAiFindElements(target).forEach(function(obj) {
                self.canvas.remove(obj);
            });
        },

        tdAiClear: function() {
            var self = this;
            this.canvas.getObjects().slice().forEach(function(obj) {
                self.canvas.remove(obj);
            });
            this.canvas.discardActiveObject();
        }
    });

    /* ------------------------- Event binding ------------------------- */

    $(function() {
        TD.initAiAssistant();
    });

    $(document)
        .on('click', '#venuera-td-ai-open', function(e) {
            e.preventDefault();
            TD.openTdAiModal();
        })
        .on('click', '.venuera-ai-close, .venuera-ai-cancel', function(e) {
            if ($(this).closest('#venuera-td-ai-modal').length) {
                e.preventDefault();
                TD.closeTdAiModal();
            }
        })
        .on('click', '#venuera-td-ai-generate-btn', function(e) {
            e.preventDefault();
            TD.runTdAiGenerate();
        })
        .on('click', '#venuera-td-ai-modal .venuera-ai-example', function() {
            $('#venuera-td-ai-prompt').val($(this).text()).focus();
        })
        .on('click', '#venuera-td-ai-modal', function(e) {
            if (e.target === this) {
                TD.closeTdAiModal();
            }
        })
        .on('keydown', '#venuera-td-ai-prompt', function(e) {
            if ((e.metaKey || e.ctrlKey) && e.key === 'Enter') {
                e.preventDefault();
                TD.runTdAiGenerate();
            }
        });

})(jQuery);
