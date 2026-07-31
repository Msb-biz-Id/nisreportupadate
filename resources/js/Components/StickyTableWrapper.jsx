import React, { useState, useEffect, useRef, useCallback } from 'react';

/**
 * StickyTableWrapper
 * Best practice UI/UX container for wide tables (Kanban-like horizontal scrolling):
 * 1. Kanban Wheel Scroll: Mouse wheel over table automatically scrolls horizontally!
 * 2. Viewport Floating & Docked Horizontal Scrollbar: Always visible when table is on screen.
 * 3. Mouse Drag-to-Scroll (Kanban UI style).
 * 4. Dual Scrollbars (Top & Bottom tracks).
 * 5. Dynamic edge shadow gradients.
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
    const bottomScrollbarRef = useRef(null);
    const topScrollbarRef = useRef(null);

    const [hasOverflow, setHasOverflow] = useState(false);
    const [isTableVisible, setIsTableVisible] = useState(false);
    const [isBottomDocked, setIsBottomDocked] = useState(false);

    const [scrollWidth, setScrollWidth] = useState(0);
    const [scrollbarBounds, setScrollbarBounds] = useState({ left: 0, width: 0 });

    const [canScrollLeft, setCanScrollLeft] = useState(false);
    const [canScrollRight, setCanScrollRight] = useState(false);

    const [isDragging, setIsDragging] = useState(false);
    const dragPos = useRef({ startX: 0, scrollLeft: 0, isMouseDown: false });

    const isSyncingTarget = useRef(false);
    const isSyncingScrollbar = useRef(false);

    // Sync scrollbar metrics
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

        // Viewport bounds
        const rect = target.getBoundingClientRect();
        const windowHeight = window.innerHeight;

        // Table is visible if its top is above viewport bottom & bottom is below viewport top
        const visible = rect.top < windowHeight - 50 && rect.bottom > 80;
        setIsTableVisible(overflow && visible);

        // Is the table bottom already inside the screen or off-screen?
        setIsBottomDocked(rect.bottom <= windowHeight);

        setScrollbarBounds({
            left: rect.left,
            width: rect.width,
        });

        // Sync scrollbar positions
        if (!isSyncingScrollbar.current) {
            isSyncingTarget.current = true;
            if (bottomScrollbarRef.current) bottomScrollbarRef.current.scrollLeft = sLeft;
            if (topScrollbarRef.current) topScrollbarRef.current.scrollLeft = sLeft;
        }
    }, []);

    useEffect(() => {
        const target = containerRef.current;
        if (!target) return;

        updateMetrics();

        const handleScroll = () => {
            updateMetrics();
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

    // Handle scrollbar dragging/scrolling
    const handleScrollbarScroll = (sourceRef) => {
        if (isSyncingTarget.current) {
            isSyncingTarget.current = false;
            return;
        }
        if (containerRef.current && sourceRef.current) {
            isSyncingScrollbar.current = true;
            const newLeft = sourceRef.current.scrollLeft;
            containerRef.current.scrollLeft = newLeft;

            if (topScrollbarRef.current && sourceRef !== topScrollbarRef) {
                topScrollbarRef.current.scrollLeft = newLeft;
            }
            if (bottomScrollbarRef.current && sourceRef !== bottomScrollbarRef) {
                bottomScrollbarRef.current.scrollLeft = newLeft;
            }

            setTimeout(() => {
                isSyncingScrollbar.current = false;
            }, 50);
        }
    };

    // Kanban-style Mouse Wheel Horizontal Scroll
    const handleWheel = (e) => {
        if (!enableWheelScroll || !hasOverflow || !containerRef.current) return;

        const target = containerRef.current;
        const delta = Math.abs(e.deltaX) > Math.abs(e.deltaY) ? e.deltaX : e.deltaY;
        if (Math.abs(delta) < 1) return;

        const canScrollMoreLeft = target.scrollLeft > 0 && delta < 0;
        const canScrollMoreRight = target.scrollLeft < target.scrollWidth - target.clientWidth - 1 && delta > 0;

        if (canScrollMoreLeft || canScrollMoreRight) {
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
            {/* Top Horizontal Scrollbar Track */}
            {hasOverflow && (
                <div
                    ref={topScrollbarRef}
                    onScroll={() => handleScrollbarScroll(topScrollbarRef)}
                    className="overflow-x-auto bg-slate-100/90 border-b border-slate-200 rounded-t-lg transition-all"
                    style={{ height: '10px' }}
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
                className={`overflow-auto border bg-white select-none ${
                    isDragging ? 'cursor-grabbing' : hasOverflow ? 'cursor-grab' : ''
                } ${containerClassName}`}
            >
                {children}
            </div>

            {/* Bottom Docked Scrollbar Track */}
            {hasOverflow && (
                <div
                    ref={bottomScrollbarRef}
                    onScroll={() => handleScrollbarScroll(bottomScrollbarRef)}
                    className="overflow-x-auto bg-slate-100/90 border-t border-slate-200 rounded-b-lg transition-all"
                    style={{ height: '12px' }}
                >
                    <div style={{ width: `${scrollWidth}px`, height: '1px' }} />
                </div>
            )}

            {/* Floating Viewport Scrollbar (Fixed to screen bottom when table bottom is off-screen) */}
            {isTableVisible && !isBottomDocked && (
                <div
                    onScroll={() => handleScrollbarScroll(bottomScrollbarRef)}
                    className="fixed bottom-0 z-50 overflow-x-auto bg-slate-900/90 backdrop-blur-md border-t border-slate-700 shadow-2xl transition-all duration-150 rounded-t-lg"
                    style={{
                        left: `${scrollbarBounds.left}px`,
                        width: `${scrollbarBounds.width}px`,
                        height: '14px',
                    }}
                >
                    <div
                        style={{ width: `${scrollWidth}px`, height: '1px' }}
                        onMouseDown={(e) => {
                            // Sync scroll when clicking floating track
                            if (bottomScrollbarRef.current) {
                                bottomScrollbarRef.current.scrollLeft = e.currentTarget.scrollLeft;
                            }
                        }}
                    />
                </div>
            )}
        </div>
    );
}
