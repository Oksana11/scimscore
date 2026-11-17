import QRCode from 'qrcode';

// Renders the otpauth:// setup URI as a QR code locally in the browser
// (the secret never goes to a third-party QR service).
export function initTotpQr(canvas) {
    const uri = canvas.dataset.totpQr;
    if (!uri || !uri.startsWith('otpauth://totp/')) return;
    QRCode.toCanvas(canvas, uri, { width: 200, margin: 1, errorCorrectionLevel: 'M' }, (err) => {
        if (err) canvas.remove();
    });
}
