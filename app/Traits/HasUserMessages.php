<?php

namespace App\Traits;

trait HasUserMessages
{
    protected function getUserMessage($messageKey, $request = null)
    {
        $language = $this->detectUserLanguage($request);
        return $this->userMessages[$messageKey][$language] ?? $this->userMessages[$messageKey]['en'];
    }
    
    protected function detectUserLanguage($request = null)
    {
        if (!$request) {
            $request = request();
        }
        
        $acceptLanguage = $request->header('Accept-Language', 'en');
        return (strpos($acceptLanguage, 'mne') !== false || strpos($acceptLanguage, 'sr') !== false) ? 'mne' : 'en';
    }
    
    protected $userMessages = [
        // Slot rezervacija poruke (korisničke)
        'slot_not_available' => [
            'en' => 'Selected slots are not available. Please choose another time.',
            'mne' => 'Izabrani slotovi nisu dostupni. Molimo odaberite drugo vreme.'
        ],
        'slot_reserved_by_other' => [
            'en' => 'Slot is currently reserved by another user. Please try again in a few minutes.',
            'mne' => 'Slot je trenutno rezervisan od strane drugog korisnika. Molimo pokušajte ponovo za nekoliko minuta.'
        ],
        'slot_reserved_for_you' => [
            'en' => 'Slot reserved for you for 10 minutes. Please complete payment.',
            'mne' => 'Slot je rezervisan za vas na 10 minuta. Molimo završite plaćanje.'
        ],
        'slots_available' => [
            'en' => 'Slots are available.',
            'mne' => 'Slotovi su dostupni.'
        ],
        'slot_reservation_error' => [
            'en' => 'Error during slot reservation. Please try again.',
            'mne' => 'Greška pri rezervaciji slota. Molimo pokušajte ponovo.'
        ],
        'slot_reservation_timeout' => [
            'en' => 'Slot reservation has expired. Please try again.',
            'mne' => 'Rezervacija slota je istekla. Molimo pokušajte ponovo.'
        ],
        'server_communication_error' => [
            'en' => 'Server communication error. Please try again.',
            'mne' => 'Greška u komunikaciji sa serverom. Molimo pokušajte ponovo.'
        ],
        
        // Greške u rezervaciji (korisničke)
        'invalid_slot_order' => [
            'en' => 'Drop-off slot must be before pick-up slot.',
            'mne' => 'Drop-off slot mora biti pre pick-up slota (drop_off_time_slot_id < pick_up_time_slot_id).'
        ],
        'duplicate_reservation_dropoff' => [
            'en' => 'A reservation already exists for this license plate, slot and day (drop-off).',
            'mne' => 'Već postoji rezervacija za ovu registarsku oznaku, slot i dan (drop-off).'
        ],
        'duplicate_reservation_pickup' => [
            'en' => 'A reservation already exists for this license plate, slot and day (pick-up).',
            'mne' => 'Već postoji rezervacija za ovu registarsku oznaku, slot i dan (pick-up).'
        ],
        'slots_not_available_for_reservation' => [
            'en' => 'Selected slots are not available for reservation. Please choose another time.',
            'mne' => 'Izabrani slotovi nisu dostupni za rezervaciju. Molimo odaberite drugo vreme.'
        ],
        
        // Rezervacija uspešna
        'reservation_created_successfully' => [
            'en' => 'Reservation created successfully',
            'mne' => 'Rezervacija je uspješno kreirana'
        ],
        
        // Fiskalizacija poruke (korisničke)
        'fiscal_cancellation_started' => [
            'en' => 'Fiscal receipt cancellation successfully started.',
            'mne' => 'Storniranje fiskalnog računa je uspješno pokrenuto.'
        ],
        'fiscal_cancellation_error' => [
            'en' => 'Error during cancellation.',
            'mne' => 'Greška pri storniranju.'
        ],
        
        // Validacije i checkSlotReservation poruke
        'reservation_id_required' => [
            'en' => 'Reservation ID is required.',
            'mne' => 'ID rezervacije je obavezan.'
        ],
        'reservation_not_found' => [
            'en' => 'Reservation not found.',
            'mne' => 'Rezervacija ne postoji.'
        ],
        'reservation_expired' => [
            'en' => 'Reservation has expired.',
            'mne' => 'Rezervacija je istekla.'
        ],
        
        // storeFromTemp poruke
        'merchant_id_required' => [
            'en' => 'Merchant transaction ID is required.',
            'mne' => 'merchant_transaction_id je obavezan!'
        ],
        'reservation_already_created' => [
            'en' => 'Reservation already created.',
            'mne' => 'Rezervacija je već kreirana.'
        ],
        'temp_data_not_found' => [
            'en' => 'Temporary data not found.',
            'mne' => 'Privremeni podaci nisu pronađeni.'
        ],
        'temp_data_incomplete' => [
            'en' => 'Temporary data is incomplete.',
            'mne' => 'Privremeni podaci su nepotpuni.'
        ],
        'reservation_not_created' => [
            'en' => 'Reservation was not created.',
            'mne' => 'Rezervacija nije kreirana.'
        ],
        
        // Email poruke (korisničke) 
        'confirmation_sent_successfully' => [
            'en' => 'Confirmation successfully sent to email.',
            'mne' => 'Potvrda je uspješno poslana na email.'
        ],
        'email_already_sent' => [
            'en' => 'Email has already been sent.',
            'mne' => 'Email je već poslan.'
        ],
        'email_sending_error' => [
            'en' => 'Error sending email.',
            'mne' => 'Greška pri slanju email-a.'
        ],
        'reservation_not_free' => [
            'en' => 'Reservation is not free.',
            'mne' => 'Rezervacija nije besplatna.'
        ],
        
        // Generičke greške (korisničke)
        'save_error' => [
            'en' => 'Error saving reservation: ',
            'mne' => 'Greška pri čuvanju rezervacije: '
        ],
        'free_reservation_email_error' => [
            'en' => 'Free reservation was saved, but error sending email: ',
            'mne' => 'Besplatna rezervacija je upisana, ali greška pri slanju email-a: '
        ],
        
        // store() metoda poruke (admin i korisničke)
        'slots_not_available_store' => [
            'en' => 'Selected slots are not available for reservation. Please choose another time.',
            'mne' => 'Izabrani slotovi nisu dostupni za rezervaciju. Molimo odaberite drugo vreme.'
        ],
        'invalid_slot_order' => [
            'en' => 'Drop-off slot must be before pick-up slot (drop_off_time_slot_id < pick_up_time_slot_id).',
            'mne' => 'Drop-off slot mora biti prije pick-up slota (drop_off_time_slot_id < pick_up_time_slot_id).'
        ],
        'duplicate_reservation_dropoff' => [
            'en' => 'A reservation already exists for this license plate, slot, and day (drop-off).',
            'mne' => 'Već postoji rezervacija za ovu registarsku oznaku, slot i dan (drop-off).'
        ],
        'duplicate_reservation_pickup' => [
            'en' => 'A reservation already exists for this license plate, slot, and day (pick-up).',
            'mne' => 'Već postoji rezervacija za ovu registarsku oznaku, slot i dan (pick-up).'
        ],
        
        // Session-based zaštita poruke (korisničke)
        'active_reservation_exists' => [
            'en' => 'You already have an active reservation in another tab. Please complete that reservation before creating a new one.',
            'mne' => 'Već imate aktivnu rezervaciju u drugom tab-u. Molimo završite tu rezervaciju pre nego što kreirate novu.'
        ],
        'reservation_created_in_other_tab' => [
            'en' => 'Reservation has already been created in another tab. Please use that tab for payment.',
            'mne' => 'Rezervacija je već kreirana u drugom tab-u. Molimo koristite taj tab za plaćanje.'
        ],
        'active_reservation_in_other_tab' => [
            'en' => 'Active reservation in another tab',
            'mne' => 'Aktivna rezervacija u drugom tab-u'
        ],
        'reservation_in_other_tab' => [
            'en' => 'Reservation in other tab',
            'mne' => 'Rezervacija u drugom tab-u'
        ],
        'complete_other_reservation_first' => [
            'en' => 'You already have an active reservation. Please complete that reservation before creating a new one.',
            'mne' => 'Već imate aktivnu rezervaciju. Molimo završite tu rezervaciju pre nego što kreirate novu.'
        ],
        
        // Frontend poruke (JavaScript)
        'same_time_not_allowed' => [
            'en' => 'Same arrival and departure time is not allowed for this time slot.',
            'mne' => 'Isti dolazak i odlazak nije dozvoljen za ovaj termin.'
        ],
        'slot_full_arrival' => [
            'en' => 'Arrival slot is full. Please choose another time.',
            'mne' => 'Termin za dolazak je pun. Molimo odaberite drugo vreme.'
        ],
        'slot_full_departure' => [
            'en' => 'Departure slot is full. Please choose another time.',
            'mne' => 'Termin za odlazak je pun. Molimo odaberite drugo vreme.'
        ],
        'sending' => [
            'en' => 'Sending...',
            'mne' => 'Slanje...'
        ],
        'creating_free_reservation' => [
            'en' => 'Creating free reservation...',
            'mne' => 'Kreiranje besplatne rezervacije...'
        ],
        'free_reservation_successful' => [
            'en' => 'Free reservation created successfully!',
            'mne' => 'Besplatna rezervacija je uspješno kreirana!'
        ],
        'free_reservation_error' => [
            'en' => 'Error creating free reservation: ',
            'mne' => 'Greška pri kreiranju besplatne rezervacije: '
        ],
        'temp_data_save_failed' => [
            'en' => 'Failed to save temporary data. Please try again.',
            'mne' => 'Greška pri čuvanju privremenih podataka. Molimo pokušajte ponovo.'
        ],
        'temp_data_error' => [
            'en' => 'Error with temporary data: ',
            'mne' => 'Greška sa privremenim podacima: '
        ],
        'payment_initialization_error' => [
            'en' => 'Error initializing payment. Please try again.',
            'mne' => 'Greška pri inicijalizaciji plaćanja. Molimo pokušajte ponovo.'
        ],
        'payment_sending_error' => [
            'en' => 'Error sending payment request: ',
            'mne' => 'Greška pri slanju zahteva za plaćanje: '
        ],
        'reservation_successful' => [
            'en' => 'Reservation created successfully!',
            'mne' => 'Rezervacija je uspješno kreirana!'
        ],
        'unknown_error' => [
            'en' => 'Unknown error occurred.',
            'mne' => 'Došlo je do nepoznate greške.'
        ],
        'reserve' => [
            'en' => 'Reserve',
            'mne' => 'Rezerviši'
        ],
        'continue_payment' => [
            'en' => 'Continue with payment',
            'mne' => 'Nastavi sa plaćanjem'
        ]
    ];
}
