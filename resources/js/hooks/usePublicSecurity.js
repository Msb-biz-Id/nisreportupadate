import { useEffect } from 'react';

/**
 * Reusable React Hook to secure public pages against unauthorized audits, inspect element, and source scraping.
 * Disables context menu (right-click), restricts common DevTools shortcuts, and runs an active debugger loop
 * to freeze the developer panel if opened.
 */
export default function usePublicSecurity() {
    useEffect(() => {
        // 1. Disable context menu (Right-Click)
        const handleContextMenu = (e) => {
            e.preventDefault();
        };
        document.addEventListener('contextmenu', handleContextMenu);

        // 2. Disable DevTools and Source-Viewing keyboard shortcuts
        const handleKeyDown = (e) => {
            if (
                e.keyCode === 123 || // F12
                (e.ctrlKey && e.shiftKey && (e.keyCode === 73 || e.keyCode === 74 || e.keyCode === 67)) || // Ctrl+Shift+I (Inspect), Ctrl+Shift+J (Console), Ctrl+Shift+C (Element Selector)
                (e.ctrlKey && e.keyCode === 85) || // Ctrl+U (View Source)
                (e.ctrlKey && e.keyCode === 83)    // Ctrl+S (Save Page)
            ) {
                e.preventDefault();
                return false;
            }
        };
        document.addEventListener('keydown', handleKeyDown);

        // 3. DevTools Countermeasure (Active Debugger Loop)
        // Check at a sane interval and without recursion to avoid crashing the browser tab's call stack.
        const debugInterval = setInterval(() => {
            try {
                const start = performance.now();
                debugger;
                const end = performance.now();
                if (end - start > 100) {
                    (function() {}).constructor("debugger")();
                }
            } catch (err) {}
        }, 1000);

        return () => {
            document.removeEventListener('contextmenu', handleContextMenu);
            document.removeEventListener('keydown', handleKeyDown);
            clearInterval(debugInterval);
        };
    }, []);
}

