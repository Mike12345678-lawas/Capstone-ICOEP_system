@if(session('success'))
    <div class="flash-message flash-success animate-fade-in-up">
        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="20 6 9 17 4 12"></polyline></svg>
        <span>{!! session('success') !!}</span>
        <button class="flash-close" aria-label="Close" onclick="this.parentElement.remove()">&times;</button>
    </div>
@endif

@if(session('error'))
    <div class="flash-message flash-error animate-fade-in-up">
        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"></circle><line x1="15" y1="9" x2="9" y2="15"></line></svg>
        <div style="flex:1;">
            <span>{!! session('error') !!}</span>
            @if(session('suggestions') && count(session('suggestions')) > 0)
                <div class="suggestion-chips">
                    <span style="font-size: .8125rem; font-weight: 600; color: #991b1b; display: inline-flex; align-items: center; gap: .25rem;">
                        Alternatives:
                    </span>
                    @foreach(session('suggestions') as $sug)
                        @if(($sug['type'] ?? '') === 'time_slot' || ($sug['type'] ?? '') === 'alternate_date')
                            <button type="button" class="suggestion-chip"
                                onclick="prefillConflictSlot('{{ $sug['date'] }}', '{{ $sug['start'] }}', '{{ $sug['end'] }}')"
                                title="Click to prefill this slot">
                                🕒 {{ $sug['label'] }}
                            </button>
                        @elseif(($sug['type'] ?? '') === 'alternate_facility')
                            <a href="{{ url('/facilities/' . $sug['facility_id'] . '?reservation_date=' . old('reservation_date') . '&time_start=' . old('time_start') . '&time_end=' . old('time_end') . '&title=' . urlencode(old('title', ''))) }}"
                               class="suggestion-chip"
                               onclick="return prefillFacility('{{ $sug['facility_id'] }}');"
                               title="Click to select this facility">
                                🏢 {{ $sug['label'] }}
                            </a>
                        @elseif(($sug['type'] ?? '') === 'alternate_equipment')
                            <a href="{{ url('/equipment/' . $sug['equipment_id'] . '?quantity=' . old('quantity', 1) . '&date_needed=' . old('date_needed') . '&expected_return_date=' . old('expected_return_date') . '&purpose=' . urlencode(old('purpose', ''))) }}"
                               class="suggestion-chip"
                               onclick="return prefillEquipment('{{ $sug['equipment_id'] }}');"
                               title="Click to select this equipment">
                                📦 {{ $sug['label'] }}
                            </a>
                        @endif
                    @endforeach
                </div>
                <script>
                function prefillConflictSlot(date, start, end) {
                    const d = document.getElementById('reservation_date') || document.querySelector('[name="reservation_date"]');
                    const s = document.getElementById('time_start') || document.querySelector('[name="time_start"]');
                    const e = document.getElementById('time_end') || document.querySelector('[name="time_end"]');
                    if (d && date) d.value = date;
                    if (s && start) s.value = start;
                    if (e && end) e.value = end;
                    if (d) d.scrollIntoView({ behavior: 'smooth', block: 'center' });
                }
                function prefillFacility(id) {
                    const f = document.getElementById('facility_id') || document.querySelector('[name="facility_id"]');
                    if (f) {
                        f.value = id;
                        f.scrollIntoView({ behavior: 'smooth', block: 'center' });
                        return false;
                    }
                    return true;
                }
                function prefillEquipment(id) {
                    const eq = document.getElementById('equipment_id') || document.querySelector('[name="equipment_id"]');
                    if (eq) {
                        eq.value = id;
                        eq.scrollIntoView({ behavior: 'smooth', block: 'center' });
                        return false;
                    }
                    return true;
                }
                </script>
            @endif
        </div>
        <button class="flash-close" aria-label="Close" onclick="this.closest('.flash-message').remove()">&times;</button>
    </div>
@endif

@if(session('warning'))
    <div class="flash-message flash-warning animate-fade-in-up">
        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"></path></svg>
        <span>{!! session('warning') !!}</span>
        <button class="flash-close" aria-label="Close" onclick="this.parentElement.remove()">&times;</button>
    </div>
@endif

@if($errors->any())
    <div class="flash-message flash-error animate-fade-in-up">
        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"></circle><line x1="15" y1="9" x2="9" y2="15"></line></svg>
        <span>
            @foreach($errors->all() as $error)
                {{ $error }}<br>
            @endforeach
        </span>
        <button class="flash-close" aria-label="Close" onclick="this.parentElement.remove()">&times;</button>
    </div>
@endif
