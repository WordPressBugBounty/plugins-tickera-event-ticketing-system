/**
 * Tickera Ticket Designer - Attendee Elements
 *
 * Intentionally registers NOTHING.
 *
 * Tickera's only built-in attendee field is the ticket owner's name
 * ("Ticket Owner Name"), which is generated from the Tickera field list in
 * data-elements.js. Any other attendee/custom field (email, phone, and the
 * Custom Forms fields) is surfaced automatically through the template-element
 * registry ("Add-on Fields") only when the relevant add-on is active — so we
 * never invent fields that the classic ticket templates didn't have.
 *
 * (The previous Venuera-specific hardcoded attendee_name / attendee_email /
 * attendee_phone / custom_attendee_field elements were removed.)
 */

(function($) {
    'use strict';

    if (typeof TicketDesigner === 'undefined') {
        return;
    }

    // No hardcoded attendee elements. See the file header.

})(jQuery);
