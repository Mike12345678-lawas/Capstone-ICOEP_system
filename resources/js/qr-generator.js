import QRCode from 'qrcode';

window.QRCode = QRCode;
window.dispatchEvent(new CustomEvent('qrcode-ready', { detail: QRCode }));
