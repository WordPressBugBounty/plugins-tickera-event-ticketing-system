( function( $ ) {

    'use strict';

    var tcDivi5 = {
        topWindow: window.top || window,
        topDocument: ( window.top || window ).document,
        activeField: null,
        activeEditor: null,
        editorBookmark: null,
        textSelection: null,
        observer: null,
        searchTimers: {},

        init: function() {

            if ( ! tcDivi5.isTopWindow() ) {
                return;
            }

            tcDivi5.prepareModal();
            tcDivi5.prepareForm();
            tcDivi5.addButtons();
            tcDivi5.bindEvents();
            tcDivi5.observeBuilder();
        },

        isTopWindow: function() {
            return window === tcDivi5.topWindow;
        },

        prepareModal: function() {

            var $body = $( tcDivi5.topDocument.body ),
                $builderWrap = $body.find( '#tc-shortcode-builder-wrap' ).first();

            if ( ! $builderWrap.length ) {
                return;
            }

            if ( ! $body.find( '#tc-modal-overlay' ).length ) {
                $body.append( '<div id="tc-modal-overlay" aria-hidden="true"></div>' );
            }

            if ( ! $body.find( '#tc-modal' ).length ) {
                $body.append( '<div id="tc-modal" role="dialog" aria-modal="true" aria-hidden="true" aria-label="' + tcDivi5.escapeAttribute( tc_divi5_shortcode_builder_vars.buttonTitle ) + '"></div>' );
            }

            $builderWrap
                .attr( 'id', 'tc-shortcode-builder-divi5-wrap' )
                .appendTo( $body );
        },

        prepareForm: function() {

            var $form = $( tcDivi5.topDocument ).find( '#tc-shortcode-builder' );

            if ( ! $form.length ) {
                return;
            }

            tcDivi5.showHideShortcodes( $form.find( '[name="shortcode-select"]' ) );
            tcDivi5.initConditionals();
            tcDivi5.initSearchSelect( '.tc-event-filter', 'search_event_filter', 'event' );
            tcDivi5.initSearchSelect( '.tc-ticket-type-filter', 'search_ticket_type_filter', 'ticket' );
        },

        addButtons: function() {

            $( tcDivi5.topDocument ).find( '.et-vb-field-richtext-buttons' ).each( function() {

                var $buttonRow = $( this );

                if ( $buttonRow.find( '.tc-divi5-shortcode-builder-button' ).length ) {
                    return;
                }

                var $addMedia = $buttonRow.find( '.et-vb-tinymce-add-media-button' ).first(),
                    $button = $( '<button type="button" class="et-vb-button et-vb-button--secondary et-vb-settings-option-upload-button tc-shortcode-builder-button tc-divi5-shortcode-builder-button"><span class="dashicons dashicons-tickets-alt" aria-hidden="true"></span><span class="tc-divi5-button-label">Tickera</span></button>' );

                $button.attr( 'title', tc_divi5_shortcode_builder_vars.buttonTitle );
                $button.attr( 'aria-label', tc_divi5_shortcode_builder_vars.buttonTitle );

                if ( $addMedia.length ) {
                    $button.insertAfter( $addMedia );
                } else {
                    $buttonRow.prepend( $button );
                }
            } );
        },

        bindEvents: function() {

            var $document = $( tcDivi5.topDocument );

            $document.off( '.tcDivi5ShortcodeBuilder' );

            $document.on( 'mousedown.tcDivi5ShortcodeBuilder', '.tc-divi5-shortcode-builder-button', function( event ) {
                event.preventDefault();
                event.stopPropagation();
            } );

            $document.on( 'click.tcDivi5ShortcodeBuilder', '.tc-divi5-shortcode-builder-button', function( event ) {
                event.preventDefault();
                event.stopPropagation();
                tcDivi5.openModal( $( this ).closest( '.et-vb-field-richtext' ) );
            } );

            $document.on( 'click.tcDivi5ShortcodeBuilder', '#tc-modal .tc-close, #tc-modal-overlay', function( event ) {
                event.preventDefault();
                tcDivi5.closeModal();
            } );

            $document.on( 'keydown.tcDivi5ShortcodeBuilder', function( event ) {
                if ( 'Escape' === event.key && $( tcDivi5.topDocument ).find( '#tc-modal.tc-modal-open' ).length ) {
                    tcDivi5.closeModal();
                }
            } );

            $document.on( 'change.tcDivi5ShortcodeBuilder', '#tc-shortcode-select', function() {
                tcDivi5.showHideShortcodes( $( this ) );
            } );

            $document.on( 'change.tcDivi5ShortcodeBuilder', '#tc-modal .has_conditional', function() {
                tcDivi5.updateConditionals();
            } );

            $document.on( 'submit.tcDivi5ShortcodeBuilder', '#tc-modal #tc-shortcode-builder', function( event ) {
                event.preventDefault();
                event.stopImmediatePropagation();

                tcDivi5.insertShortcode( tcDivi5.buildShortcode( $( this ) ) );
                tcDivi5.closeModal();
            } );
        },

        observeBuilder: function() {

            if ( ! window.MutationObserver || tcDivi5.observer ) {
                return;
            }

            tcDivi5.observer = new window.MutationObserver( function( mutations ) {

                var shouldScan = mutations.some( function( mutation ) {
                    return mutation.addedNodes && mutation.addedNodes.length;
                } );

                if ( shouldScan ) {
                    tcDivi5.addButtons();
                }
            } );

            tcDivi5.observer.observe( tcDivi5.topDocument.body, {
                childList: true,
                subtree: true
            } );
        },

        openModal: function( $field ) {

            var $document = $( tcDivi5.topDocument ),
                $form = $document.find( '#tc-shortcode-builder' ).first(),
                $modal = $document.find( '#tc-modal' );

            if ( ! $form.length || ! $modal.length ) {
                return;
            }

            tcDivi5.captureEditor( $field );
            $form.appendTo( $modal );
            tcDivi5.showHideShortcodes( $form.find( '[name="shortcode-select"]' ) );
            tcDivi5.updateConditionals();
            $document.find( '#tc-modal, #tc-modal-overlay' ).addClass( 'tc-modal-open' ).attr( 'aria-hidden', 'false' );

            window.setTimeout( function() {
                $form.find( '#tc-shortcode-select' ).trigger( 'focus' );
            }, 0 );
        },

        closeModal: function() {
            $( tcDivi5.topDocument )
                .find( '#tc-modal, #tc-modal-overlay' )
                .removeClass( 'tc-modal-open' )
                .attr( 'aria-hidden', 'true' );
        },

        captureEditor: function( $field ) {

            var textarea = $field.find( '.et-vb-tinymce-html-input' ).get( 0 ),
                editorId = '',
                iframeId = '';

            tcDivi5.activeField = $field;
            tcDivi5.activeEditor = null;
            tcDivi5.editorBookmark = null;
            tcDivi5.textSelection = null;

            if ( textarea && $field.find( '.et-vb-switch-editor-mode__tab--html.et-vb-switch-editor-mode__tab--active' ).length ) {
                tcDivi5.textSelection = {
                    element: textarea,
                    start: textarea.selectionStart,
                    end: textarea.selectionEnd
                };
                return;
            }

            editorId = $field.find( 'textarea[id]' ).first().attr( 'id' ) || '';
            iframeId = $field.find( '.mce-edit-area iframe[id], .tox-edit-area iframe[id]' ).first().attr( 'id' ) || '';

            if ( ! editorId && iframeId ) {
                editorId = iframeId.replace( /_ifr$/, '' );
            }

            if ( tcDivi5.topWindow.tinymce ) {
                tcDivi5.activeEditor = ( editorId && tcDivi5.topWindow.tinymce.get( editorId ) ) || tcDivi5.topWindow.tinymce.activeEditor;
            }

            if ( tcDivi5.activeEditor && tcDivi5.activeEditor.selection ) {
                try {
                    tcDivi5.editorBookmark = tcDivi5.activeEditor.selection.getBookmark( 2, true );
                } catch ( error ) {
                    tcDivi5.editorBookmark = null;
                }
            }
        },

        insertShortcode: function( shortcode ) {

            if ( ! shortcode ) {
                return;
            }

            if ( tcDivi5.textSelection && tcDivi5.textSelection.element ) {
                tcDivi5.insertIntoTextarea( shortcode );
                return;
            }

            if ( tcDivi5.activeEditor && ( ! tcDivi5.activeEditor.isHidden || ! tcDivi5.activeEditor.isHidden() ) ) {
                tcDivi5.insertIntoTinyMCE( shortcode );
                return;
            }

            if ( 'function' === typeof tcDivi5.topWindow.send_to_editor ) {
                tcDivi5.topWindow.send_to_editor( shortcode );
            }
        },

        insertIntoTextarea: function( shortcode ) {

            var selection = tcDivi5.textSelection,
                textarea = selection.element,
                value = textarea.value,
                start = Number.isInteger( selection.start ) ? selection.start : value.length,
                end = Number.isInteger( selection.end ) ? selection.end : start,
                nextValue = value.slice( 0, start ) + shortcode + value.slice( end ),
                valueSetter = Object.getOwnPropertyDescriptor( tcDivi5.topWindow.HTMLTextAreaElement.prototype, 'value' );

            if ( valueSetter && valueSetter.set ) {
                valueSetter.set.call( textarea, nextValue );
            } else {
                textarea.value = nextValue;
            }

            textarea.dispatchEvent( new tcDivi5.topWindow.Event( 'input', { bubbles: true } ) );
            textarea.focus();
            textarea.setSelectionRange( start + shortcode.length, start + shortcode.length );
        },

        insertIntoTinyMCE: function( shortcode ) {

            var editor = tcDivi5.activeEditor,
                insert = function() {

                    if ( tcDivi5.editorBookmark ) {
                        try {
                            editor.selection.moveToBookmark( tcDivi5.editorBookmark );
                        } catch ( error ) {
                            // Insert at TinyMCE's current selection if the bookmark is stale.
                        }
                    }

                    editor.insertContent( shortcode );
                };

            editor.focus();

            if ( editor.undoManager && 'function' === typeof editor.undoManager.transact ) {
                editor.undoManager.transact( insert );
            } else {
                insert();
            }

            editor.fire( 'input' );
            editor.fire( 'change' );
            editor.nodeChanged();
        },

        buildShortcode: function( $form ) {

            var shortcodeName = $form.find( '[name="shortcode-select"]' ).val(),
                shortcode = '[' + shortcodeName,
                attributes = '';

            $form.find( '.shortcode-table:visible' ).find( 'input, select, textarea' ).filter( '[name]' ).each( function() {

                var $field = $( this ),
                    value = $.trim( $field.val() );

                if ( ! value ) {
                    return;
                }

                if ( 'add_to_cart' !== shortcodeName && undefined !== $field.attr( 'data-default-value' ) && $field.attr( 'data-default-value' ) === value ) {
                    return;
                }

                if ( ( $field.is( ':radio' ) || $field.is( ':checkbox' ) ) && ! $field.is( ':checked' ) ) {
                    return;
                }

                attributes += ' ' + $field.attr( 'name' ) + '="' + value + '"';
            } );

            return shortcode + attributes + ']';
        },

        showHideShortcodes: function( $select ) {

            if ( ! $select.length ) {
                return;
            }

            var $form = $select.closest( '#tc-shortcode-builder' ),
                $table = $form.find( '#' + String( $select.val() ).replace( /_/g, '-' ) + '-shortcode' );

            $form.find( '.shortcode-table' ).hide();
            $table.show();
        },

        initConditionals: function() {

            $( tcDivi5.topDocument ).find( '.tc_conditional' ).each( function() {
                var fieldName = $( this ).attr( 'data-condition-field_name' );
                $( tcDivi5.topDocument ).find( '.' + fieldName ).addClass( 'has_conditional' );
            } );

            tcDivi5.updateConditionals();
        },

        updateConditionals: function() {

            $( tcDivi5.topDocument ).find( '#tc-shortcode-builder .tc_conditional' ).each( function() {

                var $row = $( this ),
                    fieldName = $row.attr( 'data-condition-field_name' ),
                    fieldType = $row.attr( 'data-condition-field_type' ),
                    expectedValue = $row.attr( 'data-condition-value' ),
                    action = $row.attr( 'data-condition-action' ),
                    $source = $( tcDivi5.topDocument ).find( '#tc-shortcode-builder .' + fieldName ),
                    selectedValue = '';

                if ( 'radio' === fieldType ) {
                    selectedValue = $source.filter( ':checked' ).val();
                } else if ( 'select' === fieldType ) {
                    selectedValue = $source.find( 'option:selected' ).val();
                } else {
                    selectedValue = $source.val();
                }

                var conditionMatches = expectedValue === selectedValue,
                    shouldShow = ( 'show' === action && conditionMatches ) || ( 'hide' === action && ! conditionMatches );

                $row.toggle( shouldShow ).attr( 'disabled', ! shouldShow );

                if ( ! shouldShow && $row.attr( 'id' ) ) {
                    $( tcDivi5.topDocument ).find( '#' + $row.attr( 'id' ) + '-error' ).remove();
                }
            } );
        },

        initSearchSelect: function( selector, action, timerKey ) {

            var $select = $( tcDivi5.topDocument ).find( '#tc-shortcode-builder ' + selector );

            if ( ! $select.length || ! $.fn.chosen ) {
                return;
            }

            $select.each( function() {

                var $current = $( this );

                if ( $current.data( 'tc-divi5-chosen' ) ) {
                    return;
                }

                $current.data( 'tc-divi5-chosen', true ).chosen( { width: 'auto' } );

                var $search = $current.next( '.chosen-container' ).find( '.chosen-search' );
                $search.prepend( '<div class="tc-loader"></div>' );
                $search.find( 'input' )
                    .attr( 'placeholder', tc_divi5_shortcode_builder_vars.please_enter_at_least_3_characters )
                    .on( 'keyup.tcDivi5ShortcodeBuilder', function() {

                        var keyword = $( this ).val();

                        window.clearTimeout( tcDivi5.searchTimers[ timerKey ] );

                        if ( keyword.length < 3 ) {
                            return;
                        }

                        tcDivi5.searchTimers[ timerKey ] = window.setTimeout( function() {

                            $search.find( '.tc-loader' ).show();

                            $.post( tc_divi5_shortcode_builder_vars.ajaxUrl, {
                                action: action,
                                s: keyword,
                                nonce: tc_divi5_shortcode_builder_vars.ajaxNonce,
                                excluded: [ 0 ]
                            }, function( response ) {
                                if ( response.count ) {
                                    $current.empty().append( response.options_html ).trigger( 'chosen:updated' );
                                }
                            } ).always( function() {
                                $search.find( '.tc-loader' ).hide();
                            } );
                        }, 1000 );
                    } );
            } );
        },

        escapeAttribute: function( value ) {
            return String( value )
                .replace( /&/g, '&amp;' )
                .replace( /"/g, '&quot;' )
                .replace( /</g, '&lt;' )
                .replace( />/g, '&gt;' );
        }
    };

    $( function() {
        tcDivi5.init();
    } );

} )( jQuery );
