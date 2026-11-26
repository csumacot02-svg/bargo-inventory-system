import { Html5Qrcode } from 'html5-qrcode';

document.addEventListener('DOMContentLoaded', () => {
    const toggleBtn = document.getElementById('toggle-camera');
    const statusEl = document.getElementById('scan-status');
    const codeInput = document.getElementById('code-input');
    const form = document.getElementById('manual-scan-form');

    if (! toggleBtn || ! statusEl || ! codeInput || ! form) {
        return;
    }

    let scanner = null;
    let cameraOn = false;

    async function onScanSuccess(decodedText) {
        codeInput.value = decodedText;
        statusEl.textContent = 'Code detected: ' + decodedText;

        if (scanner && cameraOn) {
            await scanner.stop();
            cameraOn = false;
            toggleBtn.textContent = 'Start Camera';
        }

        form.submit();
    }

    toggleBtn.addEventListener('click', async () => {
        if (! cameraOn) {
            scanner = new Html5Qrcode('reader');

            try {
                await scanner.start(
                    { facingMode: 'environment' },
                    { fps: 10, qrbox: { width: 250, height: 250 } },
                    onScanSuccess,
                );
                cameraOn = true;
                toggleBtn.textContent = 'Stop Camera';
                statusEl.textContent = 'Camera active — point at a QR or barcode.';
            } catch (err) {
                statusEl.textContent = 'Camera could not start: ' + err;
            }
        } else if (scanner) {
            await scanner.stop();
            cameraOn = false;
            toggleBtn.textContent = 'Start Camera';
            statusEl.textContent = 'Camera stopped.';
        }
    });
});
