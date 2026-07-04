/**
 * Play a notification sound, either by loading the mp3 asset or synthesizing it on-the-fly.
 * 
 * @param {string} soundName 'bell-chime' | 'success-tada' | 'warning-alert' | 'cash-register'
 */
export function playNotificationSound(soundName) {
    const SYNTHESIZED_SOUNDS = ['success-tada', 'cash-register', 'warning-alert', 'bell-chime'];
    
    if (SYNTHESIZED_SOUNDS.includes(soundName)) {
        synthesizeSound(soundName);
        return;
    }

    try {
        const audio = new Audio(`/sounds/${soundName}.mp3`);
        audio.play().catch(() => {
            // Fallback to Web Audio synthesis if blocked by browser autoplay/missing files
            synthesizeSound(soundName);
        });
    } catch (e) {
        synthesizeSound(soundName);
    }
}

/**
 * Synthesizes dynamic premium sound effects natively in the browser using the Web Audio API.
 */
function synthesizeSound(soundName) {
    try {
        const AudioContextClass = window.AudioContext || window.webkitAudioContext;
        if (!AudioContextClass) return;
        
        const ctx = new AudioContextClass();
        const now = ctx.currentTime;
        
        if (soundName === 'success-tada') {
            // Pleasant arpeggio: C5 -> E5 -> G5 -> C6
            const notes = [523.25, 659.25, 783.99, 1046.50];
            notes.forEach((freq, idx) => {
                const osc = ctx.createOscillator();
                const gain = ctx.createGain();
                
                osc.type = 'sine';
                osc.frequency.setValueAtTime(freq, now + idx * 0.08);
                
                gain.gain.setValueAtTime(0.2, now + idx * 0.08);
                gain.gain.exponentialRampToValueAtTime(0.01, now + idx * 0.08 + 0.35);
                
                osc.connect(gain);
                gain.connect(ctx.destination);
                
                osc.start(now + idx * 0.08);
                osc.stop(now + idx * 0.08 + 0.4);
            });
        } else if (soundName === 'cash-register') {
            // High register triple-chime with quick attack and delay
            const frequencies = [987.77, 1318.51, 1567.98]; // B5, E6, G6
            frequencies.forEach((freq, idx) => {
                const osc = ctx.createOscillator();
                const gain = ctx.createGain();
                
                osc.type = 'triangle';
                osc.frequency.setValueAtTime(freq, now + idx * 0.05);
                
                gain.gain.setValueAtTime(0.15, now + idx * 0.05);
                gain.gain.exponentialRampToValueAtTime(0.01, now + idx * 0.05 + 0.25);
                
                osc.connect(gain);
                gain.connect(ctx.destination);
                
                osc.start(now + idx * 0.05);
                osc.stop(now + idx * 0.05 + 0.3);
            });
            
            // Metallic coin drop snap
            const oscSnap = ctx.createOscillator();
            const gainSnap = ctx.createGain();
            oscSnap.type = 'sawtooth';
            oscSnap.frequency.setValueAtTime(440, now);
            oscSnap.frequency.exponentialRampToValueAtTime(100, now + 0.1);
            gainSnap.gain.setValueAtTime(0.1, now);
            gainSnap.gain.exponentialRampToValueAtTime(0.001, now + 0.1);
            
            oscSnap.connect(gainSnap);
            gainSnap.connect(ctx.destination);
            
            oscSnap.start(now);
            oscSnap.stop(now + 0.1);
        } else if (soundName === 'warning-alert') {
            // Low sweeping double alarm pulse
            [0, 0.2].forEach((delay) => {
                const osc = ctx.createOscillator();
                const gain = ctx.createGain();
                
                osc.type = 'sawtooth';
                osc.frequency.setValueAtTime(330, now + delay);
                osc.frequency.linearRampToValueAtTime(220, now + delay + 0.15);
                
                gain.gain.setValueAtTime(0.15, now + delay);
                gain.gain.exponentialRampToValueAtTime(0.01, now + delay + 0.15);
                
                osc.connect(gain);
                gain.connect(ctx.destination);
                
                osc.start(now + delay);
                osc.stop(now + delay + 0.15);
            });
        } else {
            // Default 'bell-chime' - dual warm pure sine tones (E5 & A5)
            const osc1 = ctx.createOscillator();
            const osc2 = ctx.createOscillator();
            const gain = ctx.createGain();
            
            osc1.type = 'sine';
            osc1.frequency.setValueAtTime(659.25, now); // E5
            
            osc2.type = 'sine';
            osc2.frequency.setValueAtTime(880.00, now + 0.08); // A5
            
            gain.gain.setValueAtTime(0.2, now);
            gain.gain.exponentialRampToValueAtTime(0.01, now + 0.5);
            
            osc1.connect(gain);
            osc2.connect(gain);
            gain.connect(ctx.destination);
            
            osc1.start(now);
            osc1.stop(now + 0.5);
            
            osc2.start(now + 0.08);
            osc2.stop(now + 0.5);
        }
    } catch (err) {
        console.warn('Web Audio Context synthesis failed or was blocked by browser:', err);
    }
}
