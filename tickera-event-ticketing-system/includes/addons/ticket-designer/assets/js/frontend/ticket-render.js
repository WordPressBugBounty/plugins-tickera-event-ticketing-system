/**
 * Venuera Ticket Designer - Frontend Rendering
 * 
 * Handles rendering barcodes and dynamic elements on frontend tickets.
 */

(function($) {
	function T( k, fb ) { return ( (window.venueraTicketDesigner && window.venueraTicketDesigner.strings) && (window.venueraTicketDesigner && window.venueraTicketDesigner.strings)[ k ] ) || fb; }

    'use strict';

    /**
     * Initialize ticket rendering.
     */
    function initTicketRender() {
        // Render all barcodes
        renderBarcodes();
        
        // Initialize print buttons
        initPrintButtons();
        
        // Initialize download buttons
        initDownloadButtons();
    }

    /**
     * Render barcodes using JsBarcode.
     */
    function renderBarcodes() {
        if (typeof JsBarcode === 'undefined') {
            console.warn('JsBarcode not loaded');
            return;
        }

        $('.ticket-barcode').each(function() {
            var $container = $(this);
            var value = $container.data('value');
            var format = $container.data('format') || 'CODE128';
            
            if (!value) return;
            
            // Create SVG element
            var svg = document.createElementNS('http://www.w3.org/2000/svg', 'svg');
            $container.html(svg);
            
            try {
                JsBarcode(svg, value, {
                    format: format,
                    width: 2,
                    height: parseInt($container.css('height')) || 40,
                    displayValue: $container.data('show-text') !== false,
                    fontSize: 10,
                    margin: 0,
                    background: 'transparent'
                });
            } catch (e) {
                console.error('Barcode generation failed:', e);
                $container.html('<span class="barcode-error">' + T('invalidBarcode', 'Invalid barcode') + '</span>');
            }
        });
    }

    /**
     * Initialize print buttons.
     */
    function initPrintButtons() {
        $(document).on('click', '.venuera-print-ticket', function(e) {
            e.preventDefault();
            
            var $ticket = $(this).closest('.venuera-ticket-wrapper').find('.venuera-ticket');
            
            if ($ticket.length) {
                printTicket($ticket[0]);
            }
        });
    }

    /**
     * Print a single ticket.
     * 
     * @param {HTMLElement} ticketElement Ticket element to print.
     */
    function printTicket(ticketElement) {
        var printWindow = window.open('', '_blank', 'width=800,height=600');
        
        if (!printWindow) {
            alert( T('pleaseAllowPopupsTo', 'Please allow popups to print tickets.'));
            return;
        }
        
        var styles = `
            <style>
                * { margin: 0; padding: 0; box-sizing: border-box; }
                body { font-family: Arial, sans-serif; padding: 20px; }
                .venuera-ticket { 
                    border: 1px solid #ddd; 
                    position: relative; 
                    margin: 0 auto;
                    overflow: hidden;
                }
                .ticket-element { position: absolute; }
                .ticket-qr-code img { max-width: 100%; max-height: 100%; }
                .ticket-barcode svg { max-width: 100%; max-height: 100%; }
                @media print {
                    body { padding: 0; }
                    .venuera-ticket { border: none; }
                }
            </style>
        `;
        
        var html = '<!DOCTYPE html><html><head><title>' + T('printTicket', 'Print Ticket') + '</title>' + styles + '</head><body>';
        html += ticketElement.outerHTML;
        html += '<script>window.onload = function() { window.print(); window.close(); }<\/script>';
        html += '</body></html>';
        
        printWindow.document.write(html);
        printWindow.document.close();
    }

    /**
     * Initialize download buttons.
     */
    function initDownloadButtons() {
        $(document).on('click', '.venuera-download-ticket', function(e) {
            e.preventDefault();
            
            var $ticket = $(this).closest('.venuera-ticket-wrapper').find('.venuera-ticket');
            var ticketId = $ticket.data('ticket-id') || 'ticket';
            
            if ($ticket.length) {
                downloadTicketAsImage($ticket[0], ticketId);
            }
        });
    }

    /**
     * Download ticket as PNG image.
     * 
     * @param {HTMLElement} ticketElement Ticket element.
     * @param {string}      filename      Filename without extension.
     */
    function downloadTicketAsImage(ticketElement, filename) {
        // Use html2canvas if available
        if (typeof html2canvas !== 'undefined') {
            html2canvas(ticketElement, {
                scale: 2,
                useCORS: true,
                allowTaint: true
            }).then(function(canvas) {
                var link = document.createElement('a');
                link.download = filename + '.png';
                link.href = canvas.toDataURL('image/png');
                link.click();
            });
        } else {
            // Fallback: open print dialog
            alert( T('imageDownloadRequiresHtml2ca', 'Image download requires html2canvas library. Please use the print option instead.'));
        }
    }

    /**
     * Initialize Apple Wallet buttons (if applicable).
     */
    function initWalletButtons() {
        $(document).on('click', '.venuera-add-to-wallet', function(e) {
            e.preventDefault();
            
            var passUrl = $(this).data('pass-url');
            
            if (passUrl) {
                window.location.href = passUrl;
            }
        });
    }

    // Initialize on document ready
    $(document).ready(function() {
        initTicketRender();
    });

    // Re-render barcodes after AJAX content load
    $(document).ajaxComplete(function() {
        renderBarcodes();
    });

})(jQuery);

