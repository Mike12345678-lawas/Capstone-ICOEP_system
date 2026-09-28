@if(session('success'))
    <div class="flash-message flash-success animate-fade-in-up">
        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="20 6 9 17 4 12"></polyline></svg>
        <span>{!! session('success') !!}</span>
        <button class="flash-close" onclick="this.parentElement.remove()">&times;</button>
    </div>
@endif

@if(session('error'))
    <div class="flash-message flash-error animate-fade-in-up">
        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"></circle><line x1="15" y1="9" x2="9" y2="15"></line></svg>
        <span>{!! session('error') !!}</span>
        <button class="flash-close" onclick="this.parentElement.remove()">&times;</button>
    </div>
@endif

@if(session('warning'))
    <div class="flash-message flash-warning animate-fade-in-up">
        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"></path></svg>
        <span>{!! session('warning') !!}</span>
        <button class="flash-close" onclick="this.parentElement.remove()">&times;</button>
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
        <button class="flash-close" onclick="this.parentElement.remove()">&times;</button>
    </div>
@endif
