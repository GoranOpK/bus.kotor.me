{{-- Prikaz forme za filtriranje rezervacija po slot vremenu --}}
<form method="GET" class="mb-3">
    <label for="slot_time">Slot vrijeme:</label>
    <input type="datetime-local" id="slot_time" name="slot_time" value="{{ request('slot_time') }}">
    <button type="submit">Filtriraj</button>
</form>

{{-- Prikaz svih rezervacija za readonly admina --}}
<table>
    <thead>
        <tr>
            <th>Tip vozila</th>
            <th>Registarske oznake</th>
            <th>Slot vrijeme</th>
            <th>Tip slota</th>
            {{-- Dodaj još kolona samo za glavne admine --}}
            @unless(auth()->user()->hasRole('admin_readonly'))
                <th>Ostali podaci</th>
            @endunless
        </tr>
    </thead>
    <tbody>
        @foreach($reservations as $reservation)
            <tr>
                {{-- Prikaz tipa vozila (ako koristiš relaciju, zamijeni sa $reservation->vehicleType->name) --}}
                <td>{{ $reservation->vehicle_type ?? $reservation->vehicle_type_id }}</td>
                <td>{{ $reservation->license_plate }}</td>
                {{-- Prikaz slot vremena (ako koristiš relaciju, zamjeni sa $reservation->timeslot->start_time) --}}
                <td>{{ $reservation->slot_time ?? $reservation->time_slot_id }}</td>
                <td>{{ $reservation->slot_type ?? $reservation->type }}</td>
                @unless(auth()->user()->hasRole('admin_readonly'))
                    <td>{{ $reservation->other_info ?? '' }}</td>
                @endunless
            </tr>
        @endforeach
    </tbody>
</table>