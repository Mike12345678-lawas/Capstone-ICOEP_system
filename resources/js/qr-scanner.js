import jsQR from 'jsqr';

window.jsQR = jsQR;
window.dispatchEvent(new CustomEvent('jsqr-ready', { detail: jsQR }));
