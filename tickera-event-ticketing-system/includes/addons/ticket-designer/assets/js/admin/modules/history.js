/**
 * Venuera Ticket Designer - History Module
 * 
 * Handles undo/redo functionality.
 */

(function($) {
    'use strict';

    if (typeof TicketDesigner === 'undefined') {
        return;
    }

    /**
     * Max history states.
     */
    TicketDesigner.maxHistoryStates = 50;

    /**
     * Save current state to history.
     */
    TicketDesigner.saveToHistory = function() {
        // Get current state
        var state = JSON.stringify(this.canvas.toJSON(['elementData', 'isGrid']));
        
        // Don't save if same as last state
        if (this.undoStack.length > 0 && this.undoStack[this.undoStack.length - 1] === state) {
            return;
        }
        
        // Add to undo stack
        this.undoStack.push(state);
        
        // Limit stack size
        if (this.undoStack.length > this.maxHistoryStates) {
            this.undoStack.shift();
        }
        
        // Clear redo stack
        this.redoStack = [];
        
        // Update buttons
        this.updateHistoryButtons();
    };

    /**
     * Undo last action.
     */
    TicketDesigner.undo = function() {
        if (this.undoStack.length <= 1) {
            return;
        }
        
        // Move current state to redo stack
        var currentState = this.undoStack.pop();
        this.redoStack.push(currentState);
        
        // Restore previous state
        var previousState = this.undoStack[this.undoStack.length - 1];
        this.restoreState(previousState);
        
        this.updateHistoryButtons();
    };

    /**
     * Redo last undone action.
     */
    TicketDesigner.redo = function() {
        if (this.redoStack.length === 0) {
            return;
        }
        
        // Move state from redo to undo stack
        var state = this.redoStack.pop();
        this.undoStack.push(state);
        
        // Restore state
        this.restoreState(state);
        
        this.updateHistoryButtons();
    };

    /**
     * Restore canvas state.
     * 
     * @param {string} stateJson JSON state string.
     */
    TicketDesigner.restoreState = function(stateJson) {
        var self = this;
        
        try {
            var state = JSON.parse(stateJson);
            
            this.canvas.loadFromJSON(state, function() {
                // Restore element data
                self.canvas.getObjects().forEach(function(obj) {
                    // Skip grid lines
                    if (obj.isGrid) return;
                    
                    // Ensure elementData is preserved
                    if (obj.elementData) {
                        // Re-enable selection
                        obj.set({
                            selectable: true,
                            evented: true
                        });
                    }
                });
                
                self.canvas.renderAll();
                self.markDirty();

                // Guides are excluded from history; rebuild them after a restore
                // so the off-canvas view stays consistent.
                if (self.onionView && typeof self.refreshOnionGuides === 'function') {
                    self.refreshOnionGuides();
                    self.updateOnionStatus();
                }
            });
        } catch (e) {
            console.error('Failed to restore state:', e);
        }
    };

    /**
     * Update undo/redo buttons state.
     */
    TicketDesigner.updateHistoryButtons = function() {
        $('#venuera-undo').prop('disabled', this.undoStack.length <= 1);
        $('#venuera-redo').prop('disabled', this.redoStack.length === 0);
    };

    /**
     * Clear history.
     */
    TicketDesigner.clearHistory = function() {
        this.undoStack = [];
        this.redoStack = [];
        this.updateHistoryButtons();
    };

})(jQuery);

