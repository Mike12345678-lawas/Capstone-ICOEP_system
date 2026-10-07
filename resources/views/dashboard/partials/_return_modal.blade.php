{{-- Return Equipment modal: borrower must attach a photo of the item being returned. --}}
<div class="return-modal-backdrop" id="returnModal" role="dialog" aria-modal="true" aria-labelledby="returnModalTitle">
    <form method="POST" action="" enctype="multipart/form-data" class="return-modal" id="returnModalForm">
        @csrf
        <div class="return-modal-header">
            <h3 id="returnModalTitle">Return Equipment</h3>
            <button type="button" class="return-modal-close" aria-label="Close" onclick="closeReturnModal()">&times;</button>
        </div>
        <div class="return-modal-body">
            <p style="margin: 0 0 .25rem; font-weight: 600;" id="returnModalItem"></p>
            <p class="text-muted" style="margin: 0 0 1rem; font-size: .85rem;">
                Take or upload a clear photo of the equipment you are returning. Staff will check it and confirm the return.
            </p>

            <label class="photo-dropzone" id="returnDropzone" for="return_photo">
                <input type="file" id="return_photo" name="return_photo" accept="image/*" capture="environment" required>
                <div id="returnDropzonePlaceholder">
                    <svg width="36" height="36" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M23 19a2 2 0 0 1-2 2H3a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h4l2-3h6l2 3h4a2 2 0 0 1 2 2z"></path><circle cx="12" cy="13" r="4"></circle></svg>
                    <div style="font-weight: 600; margin-top: .35rem;">Click to take or choose a photo</div>
                    <div style="font-size: .75rem;">JPG, PNG or WEBP · max 5 MB</div>
                </div>
                <img id="returnPhotoPreview" alt="Return photo preview" style="display: none;">
            </label>
            <div id="returnPhotoError" style="display: none; color: #dc2626; font-size: .8rem; margin-top: .5rem;"></div>
        </div>
        <div class="return-modal-footer">
            <button type="button" class="btn btn-secondary btn-sm" onclick="closeReturnModal()">Cancel</button>
            <button type="submit" class="btn btn-primary btn-sm" id="returnModalSubmit">Submit Return</button>
        </div>
    </form>
</div>

<script>
(function () {
    const modal    = document.getElementById('returnModal');
    const form     = document.getElementById('returnModalForm');
    const input    = document.getElementById('return_photo');
    const preview  = document.getElementById('returnPhotoPreview');
    const holder   = document.getElementById('returnDropzonePlaceholder');
    const errorBox = document.getElementById('returnPhotoError');
    const dropzone = document.getElementById('returnDropzone');
    const MAX_BYTES = 5 * 1024 * 1024;

    function resetPhoto() {
        input.value = '';
        preview.src = '';
        preview.style.display = 'none';
        holder.style.display = '';
        errorBox.style.display = 'none';
    }

    window.openReturnModal = function (requestId, itemName, units) {
        form.action = '{{ url('/equipment-requests') }}/' + requestId + '/return-request';
        document.getElementById('returnModalItem').textContent =
            itemName + (units ? ' — ' + units + ' unit(s)' : '');
        resetPhoto();
        modal.classList.add('open');
    };

    window.closeReturnModal = function () {
        modal.classList.remove('open');
    };

    input.addEventListener('change', function () {
        const file = input.files[0];
        errorBox.style.display = 'none';
        if (!file) { resetPhoto(); return; }
        if (!file.type.startsWith('image/')) {
            resetPhoto();
            errorBox.textContent = 'Please choose an image file.';
            errorBox.style.display = 'block';
            return;
        }
        if (file.size > MAX_BYTES) {
            resetPhoto();
            errorBox.textContent = 'The photo must not be larger than 5 MB.';
            errorBox.style.display = 'block';
            return;
        }
        preview.src = URL.createObjectURL(file);
        preview.style.display = 'block';
        holder.style.display = 'none';
    });

    ['dragenter', 'dragover'].forEach(evt => dropzone.addEventListener(evt, e => { e.preventDefault(); dropzone.classList.add('dragover'); }));
    ['dragleave', 'drop'].forEach(evt => dropzone.addEventListener(evt, () => dropzone.classList.remove('dragover')));

    form.addEventListener('submit', function (e) {
        if (!input.files.length) {
            e.preventDefault();
            errorBox.textContent = 'Please attach a photo of the equipment you are returning.';
            errorBox.style.display = 'block';
            return;
        }
        document.getElementById('returnModalSubmit').disabled = true;
        document.getElementById('returnModalSubmit').textContent = 'Submitting…';
    });

    modal.addEventListener('click', e => { if (e.target === modal) closeReturnModal(); });
    document.addEventListener('keydown', e => { if (e.key === 'Escape') closeReturnModal(); });

    // Auto-open when arriving with ?return={id} (e.g. from the overview page)
    const returnId = new URLSearchParams(window.location.search).get('return');
    if (returnId) {
        const btn = document.getElementById('return-btn-' + returnId);
        if (btn) btn.click();
    }
})();
</script>
