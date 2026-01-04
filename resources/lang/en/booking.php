<?php

return [
    //////////////////////////////////////////offer apartment///////////////////////////////////////////////////////////////////
    // General
    'not_found_apartment' => 'Apartment does not exist.',

    // Authorization
    'own_apartment_forbidden' => 'You cannot rent your own apartment.',

    // Availability
    'not_available_dates' => 'The apartment is not available for the selected dates. Please choose alternative dates.',

    // Payment check (card status)
    'payment_check_failed' => 'Payment failed.',
    'payment_check_details' => 'Payment could not be completed due to an issue with the owner’s payment system. Please try again later.',

    // Payment transfer (actual transfer)
    'payment_transfer_failed' => 'Payment failed.',
    'payment_transfer_details' => 'Payment failed due to an issue with the owner’s payment system. Please try again later.',

    // Offer processing
    'offer_process_failed' => 'Failed to process the reservation offer for this apartment.',

    // Notifications
    'notification_new_offer_title' => 'New offer for your apartment.',
    'notification_offer_submitted_title' => 'Your offer has been submitted successfully. Please wait for the owner’s approval.',

    // Success
    'offer_submitted_success' => 'Your offer has been successfully submitted to the apartment owner. Please wait for their approval.',
    //////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////

    //////////////////////////////////////////show reservations////////////////////////////////////////////////////////////////
    'show_reservations_apartment_not_found' => 'Apartment not found.',
    'show_reservations_unauthorized' => 'You are not authorized to view these reservations.',
    'show_reservations_empty' => 'No reservations found for this apartment.',
    'show_reservations_success' => 'Reservations retrieved successfully.',
    //////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////

    //////////////////////////////////////////show all reservations history///////////////////////////////////////////////////
    'show_all_reservations_empty' => 'No reservations have been made yet.',
    'show_all_reservations_success' => 'Reservations retrieved successfully.',
    //////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////

    //////////////////////////////////////////show all pending reservations///////////////////////////////////////////////////
    'show_all_pending_reservations_empty' => 'No pending reservations found.',
    'show_all_pending_reservations_success' => 'Pending reservations retrieved successfully.',
    //////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////

    //////////////////////////////////////////show one pending reservation////////////////////////////////////////////////////
    'show_one_pending_reservation_not_found' => 'Pending reservation not found.',
    'show_one_pending_reservation_unauthorized' => 'You are not authorized to view this reservation.',
    'show_one_pending_reservation_success' => 'Pending reservation retrieved successfully.',
    //////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////

    //////////////////////////////////////////evaluate apartment//////////////////////////////////////////////////////////////
    'evaluate_apartment_not_found' => 'Apartment not found.',
    'evaluate_apartment_no_accepted_reservation' => 'You do not have an accepted reservation for this apartment.',
    'evaluate_apartment_invalid_rate' => 'Please enter a valid rating value between 1 and 5.',
    'evaluate_apartment_too_early' => 'Thank you for your support. You can leave a review for the apartment once at least half of the reservation period has passed.',
    'evaluate_apartment_notification_title' => 'New evaluation for your apartment.',
    'evaluate_apartment_success' => 'Your evaluation has been submitted successfully.',
    //////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////

    //////////////////////////////////////////mark reservation awaiting payment//////////////////////////////////////////////
    'mark_reservation_not_found' => 'Reservation not found.',
    'mark_reservation_unauthorized' => 'You are not authorized to update this reservation.',
    'mark_reservation_notification_title' => 'Your reservation has been approved. Please complete the payment to finalize.',
    'mark_reservation_success' => 'Reservation approved and now awaiting payment.',
    //////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////

    //////////////////////////////////////////show reservations awaiting payment/////////////////////////////////////////////
    'show_reservations_awaiting_payment_empty' => 'No reservations awaiting payment were found.',
    'show_reservations_awaiting_payment_success' => 'Reservations awaiting payment retrieved successfully.',
    //////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////

    //////////////////////////////////////////user cancel reservation/////////////////////////////////////////////////////////
    'user_cancel_reservation_not_found' => 'Reservation not found or cancellation failed.',
    'user_cancel_reservation_already_cancelled' => 'This reservation has already been cancelled.',
    'user_cancel_reservation_unable' => 'Unable to process cancellation for this reservation status.',
    //////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////

    //////////////////////////////////////////cancel pending or awaiting payment reservation//////////////////////////////////
    'cancel_pending_or_awaiting_refund_failed' => 'Refund process failed. Please try again later.',
    'cancel_pending_or_awaiting_notification_renter' => 'Your reservation for apartment #:apartmentId has been canceled. Any paid deposit has been refunded to your card.',
    'cancel_pending_or_awaiting_notification_owner' => 'A pending/awaiting payment reservation on your apartment #:apartmentId has been canceled by the renter.',
    'cancel_pending_or_awaiting_success' => 'Reservation canceled successfully.',
    ////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////

    //////////////////////////////////////////cancel accepted reservation/////////////////////////////////////////////////////
    'cancel_accepted_refund_failed' => 'Refund process failed. Please try again later.',
    'cancel_accepted_active_no_refund' => 'Reservation canceled during active period - no refund.',
    'cancel_accepted_within_3_days' => 'Reservation canceled within 3 days before start - deposit not refunded.',
    'cancel_accepted_in_advance' => 'Reservation canceled in advance - deposit not refunded.',
    'cancel_accepted_notification_renter' => 'Your accepted reservation for apartment #:apartmentId has been canceled. Any paid deposit has been refunded to your card.',
    'cancel_accepted_notification_owner' => 'An accepted reservation on your apartment #:apartmentId has been canceled by the renter.',
    'cancel_accepted_success' => 'Reservation canceled successfully.',
    ////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////

    //////////////////////////////////////////final process payment///////////////////////////////////////////////////////////
    'final_payment_reservation_not_found' => 'Payment failed. Reservation not found.',
    'final_payment_apartment_not_found' => 'Payment failed. Apartment not found.',
    'final_payment_card_invalid_or_insufficient' => 'Either the card number is invalid or the card does not have sufficient funds.',
    'final_payment_failed_owner_system' => 'Payment failed due to an issue with the owner payment system. Please try again later.',
    'final_payment_notification_renter' => 'Payment completed successfully.',
    'final_payment_notification_owner' => 'The renter has completed the payment for your apartment.',
    'final_payment_success' => 'Payment completed successfully. You can now receive the apartment at any time.',
    //////////////////////////////////////////show reservations (owner)////////////////////////////////////////////////////////////
    'owner_pending_empty'   => 'There are no pending or awaiting payment reservations.',
    'owner_pending_success' => 'Pending and awaiting payment reservations retrieved successfully.',

    'owner_active_empty'    => 'There are no active accepted reservations at the moment.',
    'owner_active_success'  => 'Active accepted reservations retrieved successfully.',

    'owner_history_empty'   => 'There are no cancelled or finished reservations.',
    'owner_history_success' => 'Reservation history retrieved successfully.',
    /////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////

    //////////////////////////////////////////show reservations (renter)///////////////////////////////////////////////////////////////////////////////////////////////
    'renter_pending_empty'  => 'You have no pending or awaiting payment reservations.',
    'renter_pending_success' => 'Pending reservations retrieved successfully.',

    'renter_active_empty'   => 'You have no active reservations.',
    'renter_active_success' => 'Active reservations retrieved successfully.',

    'renter_history_empty'  => 'You have no past reservations.',
    'renter_history_success' => 'Reservation history retrieved successfully.',
    ///////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////


];
