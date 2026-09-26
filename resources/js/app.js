document.addEventListener('DOMContentLoaded', function () {
    // 1. Toggle Ganti Berkas
    const btnToggle = document.getElementById('btn-toggle-upload');
    const uploadCollapsible = document.getElementById('upload-collapsible');
    if (btnToggle && uploadCollapsible) {
        btnToggle.addEventListener('click', function () {
            uploadCollapsible.classList.toggle('hidden');
            if (!uploadCollapsible.classList.contains('hidden')) {
                uploadCollapsible.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
                const fileInput = uploadCollapsible.querySelector('input[type="file"]');
                if (fileInput) {
                    fileInput.focus();
                }
            }
        });
    }

    // 2. State Sedang Memeriksa (Loading state & Prevent Double Submit)
    const formProcess = document.getElementById('form-process');
    const btnProcess = document.getElementById('btn-process');
    const btnProcessText = document.getElementById('btn-process-text');
    const btnProcessIcon = document.getElementById('btn-process-icon');

    if (formProcess && btnProcess) {
        let isProcessing = false;
        formProcess.addEventListener('submit', function (e) {
            if (isProcessing) {
                e.preventDefault();
                return false;
            }
            isProcessing = true;
            btnProcess.disabled = true;
            btnProcess.classList.add('opacity-75', 'cursor-not-allowed');

            if (btnProcessIcon) {
                btnProcessIcon.outerHTML = '<svg class="animate-spin -ml-1 mr-2 h-4 w-4 text-white inline-block shrink-0" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>';
            }
            if (btnProcessText) {
                btnProcessText.textContent = 'Sedang Memeriksa...';
            }
        });
    }

    // 3. Drag and Drop & File Display pada Area Upload
    const dropZones = document.querySelectorAll('.upload-dropzone');
    dropZones.forEach(function (zone) {
        const fileInput = zone.querySelector('input[type="file"]');
        const fileNameDisplay = zone.querySelector('.selected-file-name');

        if (fileInput) {
            fileInput.addEventListener('change', function () {
                if (this.files && this.files.length > 0 && fileNameDisplay) {
                    fileNameDisplay.textContent = 'Berkas terpilih: ' + this.files[0].name;
                    fileNameDisplay.classList.remove('hidden');
                }
            });
        }

        ['dragenter', 'dragover'].forEach(function (eventName) {
            zone.addEventListener(eventName, function (e) {
                e.preventDefault();
                e.stopPropagation();
                zone.classList.add('border-indigo-500', 'bg-indigo-50/40');
            }, false);
        });

        ['dragleave', 'drop'].forEach(function (eventName) {
            zone.addEventListener(eventName, function (e) {
                e.preventDefault();
                e.stopPropagation();
                zone.classList.remove('border-indigo-500', 'bg-indigo-50/40');
            }, false);
        });

        zone.addEventListener('drop', function (e) {
            const dt = e.dataTransfer;
            const files = dt.files;
            if (files && files.length > 0 && fileInput) {
                fileInput.files = files;
                if (fileNameDisplay) {
                    fileNameDisplay.textContent = 'Berkas terpilih: ' + files[0].name;
                    fileNameDisplay.classList.remove('hidden');
                }
            }
        }, false);
    });
});
