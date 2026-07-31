import React, { useState, useEffect, useRef, useCallback } from 'react';

/**
 * StickyTableWrapper
 * Best practice UI/UX container for wide tables:
 * 1. Kanban Wheel Scroll: Mouse wheel over table automatically scrolls horizontally!
 * 2. Floating Viewport Scrollbar: Always visible fixed at bottom of screen when table is in view.
 * 3. Dual Scrollbars: Top & Bottom styled scrollbar tracks.
 * 4. Mouse Drag-to-Scroll (Kanban UI style).
 * 5. Cross-browser custom scrollbar styling (prevents Windows scrollbar clipping).
 */
export default function StickyTableWrapper({
    children,
    className = '',
    containerClassName = '',
    maxHeight = 'calc(100vh - 280px)',
    enableDrag = true,
    enableWheelScroll = true,
}) {
    const containerRef = useRef(null);
    const topScrollbarRef = useRef(null);
    const bottomScrollbarRef = useRef(null);
    const floatingScrollbarRef = useRef(null);

    const [hasOverflow, setHasOverflow] = useState(false);
    const [isTableInViewport, setIsTableInViewport] = useState(false);
    const [scrollWidth, setScrollWidth] = useState(0);
    const [scrollbarBounds, setScrollbarBounds] = useState({ left: 0, width: 0 });

    const [canScrollLeft, setCanScrollLeft] = useState(false);
    const [canScrollRight, setCanScrollRight] = useState(false);

    const [isDragging, setIsDragging] = useState(false);
    const dragPos = useRef({ startX: 0, scrollLeft: 0, isMouseDown: false });

    const isSyncing = useRef(false);

    // Sync metrics & positions
    const updateMetrics = useCallback(() => {
        const target = containerRef.current;
        if (!target) return;

        const sWidth = target.scrollWidth;
        const cWidth = target.clientWidth;
        const overflow = sWidth > cWidth + 2;

        setHasOverflow(overflow);
        setScrollWidth(sWidth);

        const sLeft = target.scrollLeft;
        setCanScrollLeft(sLeft > 5);
        setCanScrollRight(sLeft < sWidth - cWidth - 5);

        // Viewport position
        const rect = target.getBoundingClientRect();
        const windowHeight = window.innerHeight;

        // Table is in viewport if its top is above screen bottom and bottom is below top
        const inViewport = rect.top < windowHeight - 50 && rect.bottom > 80;
        setIsTableInViewport(overflow && inViewport);

        setScrollbarBounds({
            left: rect.left,
            width: rect.width,
        });

        // Sync all scrollbar elements
        if (!isSyncing.current) {
            isSyncing.current = true;
            if (topScrollbarRef.current) topScrollbarRef.current.scrollLeft = sLeft;
            if (bottomScrollbarRef.current) bottomScrollbarRef.current.scrollLeft = sLeft;
            if (floatingScrollbarRef.current) floatingScrollbarRef.current.scrollLeft = sLeft;
            isSyncing.current = false;
        }
    }, []);

    useEffect(() => {
        const target = containerRef.current;
        if (!target) return;

        updateMetrics();

        const handleScroll = () => {
            if (isSyncing.current) return;
            isSyncing.current = true;
            const sLeft = target.scrollLeft;
            if (topScrollbarRef.current) topScrollbarRef.current.scrollLeft = sLeft;
            if (bottomScrollbarRef.current) bottomScrollbarRef.current.scrollLeft = sLeft;
            if (floatingScrollbarRef.current) floatingScrollbarRef.current.scrollLeft = sLeft;
            setCanScrollLeft(sLeft > 5);
            setCanScrollRight(sLeft < target.scrollWidth - target.clientWidth - 5);
            isSyncing.current = false;
        };

        const resizeObserver = new ResizeObserver(updateMetrics);
        resizeObserver.observe(target);
        window.addEventListener('scroll', updateMetrics, { passive: true });
        window.addEventListener('resize', updateMetrics, { passive: true });
        target.addEventListener('scroll', handleScroll, { passive: true });

        return () => {
            resizeObserver.disconnect();
            window.removeEventListener('scroll', updateMetrics);
            window.removeEventListener('resize', updateMetrics);
            target.removeEventListener('scroll', handleScroll);
        };
    }, [updateMetrics]);

    // Handle scrollbar sync from any scrollbar element
    const handleExternalScroll = (sourceRef) => {
        if (isSyncing.current || !containerRef.current || !sourceRef.current) return;

        isSyncing.current = true;
        const newLeft = sourceRef.current.scrollLeft;
        containerRef.current.scrollLeft = newLeft;

        if (topScrollbarRef.current && sourceRef !== topScrollbarRef) topScrollbarRef.current.scrollLeft = newLeft;
        if (bottomScrollbarRef.current && sourceRef !== bottomScrollbarRef) bottomScrollbarRef.current.scrollLeft = newLeft;
        if (floatingScrollbarRef.current && sourceRef !== floatingScrollbarRef) floatingScrollbarRef.current.scrollLeft = newLeft;

        setCanScrollLeft(newLeft > 5);
        setCanScrollRight(newLeft < containerRef.current.scrollWidth - containerRef.current.clientWidth - 5);

        isSyncing.current = false;
    };

    // Kanban-style Mouse Wheel Horizontal Scroll
    const handleWheel = (e) => {
        if (!enableWheelScroll || !hasOverflow || !containerRef.current) return;

        const target = containerRef.current;
        // Determine scroll delta (vertical or horizontal wheel)
        const delta = Math.abs(e.deltaX) > Math.abs(e.deltaY) ? e.deltaX : e.deltaY;
        if (Math.abs(delta) < 1) return;

        const canScrollMoreLeft = target.scrollLeft > 0 && delta < 0;
        const canScrollMoreRight = target.scrollLeft < target.scrollWidth - target.clientWidth - 1 && delta > 0;

        if (canScrollMoreLeft || canScrollMoreRight || e.shiftKey) {
            e.preventDefault();
            target.scrollLeft += delta * 1.2;
        }
    };

    // Mouse Drag-to-Scroll (Kanban style)
    const handleMouseDown = (e) => {
        if (!enableDrag || !hasOverflow) return;

        const target = e.target;
        if (
            target.closest('button') ||
            target.closest('a') ||
            target.closest('input') ||
            target.closest('select') ||
            target.closest('textarea') ||
            target.closest('[role="button"]') ||
            target.closest('[data-no-drag]')
        ) {
            return;
        }

        dragPos.current = {
            startX: e.clientX,
            scrollLeft: containerRef.current.scrollLeft,
            isMouseDown: true,
        };
    };

    const handleMouseMove = useCallback(
        (e) => {
            if (!dragPos.current.isMouseDown || !containerRef.current) return;

            const dx = e.clientX - dragPos.current.startX;
            if (Math.abs(dx) > 5 && !isDragging) {
                setIsDragging(true);
            }

            if (isDragging || Math.abs(dx) > 5) {
                containerRef.current.scrollLeft = dragPos.current.scrollLeft - dx;
            }
        },
        [isDragging]
    );

    const handleMouseUp = useCallback(() => {
        if (dragPos.current.isMouseDown) {
            dragPos.current.isMouseDown = false;
            setTimeout(() => setIsDragging(false), 50);
        }
    }, []);

    useEffect(() => {
        if (!enableDrag) return;
        window.addEventListener('mousemove', handleMouseMove);
        window.addEventListener('mouseup', handleMouseUp);
        return () => {
            window.removeEventListener('mousemove', handleMouseMove);
            window.removeEventListener('mouseup', handleMouseUp);
        };
    }, [enableDrag, handleMouseMove, handleMouseUp]);

    return (
        <div className={`relative group/table-wrapper ${className}`}>
            <style>{`
                .custom-table-scrollbar::-webkit-scrollbar {
                    height: 12px;
                    width: 12px;
                }
                .custom-table-scrollbar::-webkit-scrollbar-track {
                    background: #f1f5f9;
                    border-radius: 6px;
                }
                .custom-table-scrollbar::-webkit-scrollbar-thumb {
                    background: #94a3b8;
                    border-radius: 6px;
                    border: 2px solid #f1f5f9;
                }
                .custom-table-scrollbar::-webkit-scrollbar-thumb:hover {
                    background: #64748b;
                }
                .floating-table-scrollbar::-webkit-scrollbar {
                    height: 12px;
                }
                .floating-table-scrollbar::-webkit-scrollbar-track {
                    background: #1e293b;
                    border-radius: 6px;
                }
                .floating-table-scrollbar::-webkit-scrollbar-thumb {
                    background: #64748b;
                    border-radius: 6px;
                    border: 2px solid #1e293b;
                }
                .floating-table-scrollbar::-webkit-scrollbar-thumb:hover {
                    background: #94a3b8;
                }
            `}</style>

            {/* Top Horizontal Scrollbar Track */}
            {hasOverflow && (
                <div
                    ref={topScrollbarRef}
                    onScroll={() => handleExternalScroll(topScrollbarRef)}
                    className="custom-table-scrollbar overflow-x-auto bg-slate-100 border-b border-slate-200 rounded-t-lg transition-all"
                    style={{ height: '14px' }}
                >
                    <div style={{ width: `${scrollWidth}px`, height: '1px' }} />
                </div>
            )}

            {/* Edge Shadow Left */}
            {canScrollLeft && (
                <div
                    className="pointer-events-none absolute top-0 bottom-0 left-0 z-20 w-6 bg-gradient-to-r from-slate-900/15 to-transparent transition-opacity duration-200"
                    aria-hidden="true"
                />
            )}

            {/* Edge Shadow Right */}
            {canScrollRight && (
                <div
                    className="pointer-events-none absolute top-0 bottom-0 right-0 z-20 w-6 bg-gradient-to-l from-slate-900/15 to-transparent transition-opacity duration-200"
                    aria-hidden="true"
                />
            )}

            {/* Main Table Scroll Container */}
            <div
                ref={containerRef}
                onMouseDown={handleMouseDown}
                onWheel={handleWheel}
                style={{ maxHeight }}
                className={`custom-table-scrollbar overflow-auto border bg-white ${
                    isDragging ? 'cursor-grabbing' : hasOverflow ? 'cursor-grab' : ''
                } ${containerClassName}`}
            >
                {children}
            </div>

            {/* Bottom Docked Scrollbar Track */}
            {hasOverflow && (
                <div
                    ref={bottomScrollbarRef}
                    onScroll={() => handleExternalScroll(bottomScrollbarRef)}
                    className="custom-table-scrollbar overflow-x-auto bg-slate-100 border-t border-slate-200 rounded-b-lg transition-all"
                    style={{ height: '14px' }}
                >
                    <div style={{ width: `${scrollWidth}px`, height: '1px' }} />
                </div>
            )}

            {/* Floating Viewport Scrollbar (Fixed at bottom of screen whenever table is in viewport) */}
            {isTableInViewport && (
                <div
                    ref={floatingScrollbarRef}
                    onScroll={() => handleExternalScroll(floatingScrollbarRef)}
                    className="floating-table-scrollbar fixed bottom-0 z-50 overflow-x-auto bg-slate-900/95 backdrop-blur-md border-t border-slate-700 shadow-2xl transition-all duration-150 rounded-t-lg"
                    style={{
                        left: `${scrollbarBounds.left}px`,
                        width: `${scrollbarBounds.width}px`,
                        height: '16px',
                    }}
                >
                    <div style={{ width: `${scrollWidth}px`, height: '1px' }} />
                </div>
            )}
        </div>
    );
}
