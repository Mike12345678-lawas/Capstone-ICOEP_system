/**
 * Verification Test: Standards-compliant QR Code Generation and Decoding
 * 
 * Verifies that generating a QR code with the application's configuration
 * (error correction M, standard 4-module quiet zone, >= 220px) decodes cleanly
 * using jsQR back to the original token string.
 */

import QRCode from 'qrcode';
import jsQR from 'jsqr';

async function run() {
    const token = 'EVT-1-USR-5-ABCDEF123456';
    console.log(`[Test] Original Token: ${token}`);

    // Generate QR using the same settings as events/show.blade.php:
    // errorCorrectionLevel: 'M', margin: 4, width: 240
    const qr = QRCode.create(token, {
        errorCorrectionLevel: 'M',
        margin: 4
    });

    const size = qr.modules.size;
    const margin = 4;
    const totalModules = size + (margin * 2);
    const scale = Math.ceil(240 / totalModules); // >= 220px target
    const width = totalModules * scale;
    const height = totalModules * scale;

    console.log(`[Test] Matrix Size: ${size}x${size} modules, Total Canvas: ${width}x${height}px (Scale: ${scale})`);

    // Create RGBA pixel buffer
    const rgba = new Uint8ClampedArray(width * height * 4);
    rgba.fill(255); // White background & quiet zone

    for (let r = 0; r < size; r++) {
        for (let c = 0; c < size; c++) {
            if (qr.modules.get(r, c)) {
                for (let y = 0; y < scale; y++) {
                    for (let x = 0; x < scale; x++) {
                        const px = ((r + margin) * scale + y) * width + ((c + margin) * scale + x);
                        rgba[px * 4 + 0] = 15;  // Dark slate module (R)
                        rgba[px * 4 + 1] = 23;  // G
                        rgba[px * 4 + 2] = 42;  // B
                        rgba[px * 4 + 3] = 255; // Alpha
                    }
                }
            }
        }
    }

    // Decode with jsQR using the scanner's settings: inversionAttempts: 'attemptBoth'
    const decoded = jsQR(rgba, width, height, {
        inversionAttempts: 'attemptBoth'
    });

    if (!decoded || !decoded.data) {
        console.error('❌ FAILED: jsQR could not decode the generated QR code.');
        process.exit(1);
    }

    console.log(`[Test] Decoded Result: ${decoded.data}`);

    if (decoded.data === token) {
        console.log('✅ PASSED: Generated QR code decodes back to original token perfectly!');
        process.exit(0);
    } else {
        console.error(`❌ MISMATCH: Expected "${token}", got "${decoded.data}"`);
        process.exit(1);
    }
}

run().catch(err => {
    console.error('❌ Error during verification:', err);
    process.exit(1);
});
