import React, { useState, useEffect, useRef, useCallback } from 'react';

/**
 * StickyTableWrapper
 * Best practice UI/UX container for wide tables:
 * 1. Viewport-fixed floating horizontal scrollbar (bottom of screen when table is visible).
 * 2. Mouse Drag-to-Scroll (Kanban UI style).
 * 3. Shift + Wheel horizontal scroll support.
 * 4. Dynamic edge shadow gradients (indicating overflow left/right).
 */
export default function StickyTableWrapper({
    children,
    className = '',
    containerClassName = '',
    maxHeight = 'calc(100vh - 280px)',
    enableDrag = true,
}) {
    const containerRef = useRef(null);
    const scrollbarRef = useRef(null);

    const [hasOverflow, setHasOverflow] = useState(false);
    const [showFloatingScrollbar, setShowFloatingScrollbar] = useState(false);
    const [scrollWidth, setScrollWidth] = useState(0);
    const [scrollbarBounds, setScrollbarBounds] = useState({ left: 0, width: 0 });

    const [canScrollLeft, setCanScrollLeft] = useState(false);
    const [canScrollRight, setCanScrollRight] = useState(false);

    const [isDragging, setIsDragging] = useState(false);
    const dragPos = useRef({ startX: 0, scrollLeft: 0, isMouseDown: false });

    // Sync scrollbar width & position
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

        // Calculate viewport position for floating scrollbar
        const rect = target.getBoundingClientRect();
        const windowHeight = window.innerHeight;

        // Floating scrollbar should show if:
        // 1. Container has horizontal overflow
        // 2. Container top is above screen bottom (rect.top < windowHeight)
        // 3. Container bottom is below screen bottom (rect.bottom > windowHeight)
        const isVisibleInViewport = rect.top < windowHeight - 40 && rect.bottom > windowHeight;
        setShowFloatingScrollbar(overflow && isVisibleInViewport);

        setScrollbarBounds({
            left: rect.left,
            width: rect.width,
        });
    }, []);

    useEffect(() => {
        const target = containerRef.current;
        if (!target) return;

        updateMetrics();

        const handleScroll = () => {
            updateMetrics();
            if (scrollbarRef.current && !isSyncingScrollbar.current) {
                isSyncingTarget.current = true;
                scrollbarRef.current.scrollLeft = target.scrollLeft;
            }
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

    // Bidirectional sync for floating scrollbar
    const isSyncingTarget = useRef(false);
    const isSyncingScrollbar = useRef(false);

    const handleFloatingScroll = () => {
        if (isSyncingTarget.current) {
            isSyncingTarget.current = false;
            return;
        }
        if (containerRef.current && scrollbarRef.current) {
            isSyncingScrollbar.current = true;
            containerRef.current.scrollLeft = scrollbarRef.current.scrollLeft;
        }
    };

    // Mouse Drag-to-Scroll handlers (Kanban style)
    const handleMouseDown = (e) => {
        if (!enableDrag || !hasOverflow) return;
        // Don't drag if clicking interactive elements
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

    // Shift + Wheel Horizontal Scroll
    const handleWheel = (e) => {
        if (!hasOverflow || !containerRef.current) return;
        if (e.shiftKey || Math.abs(e.deltaX) > Math.abs(e.deltaY)) {
            containerRef.current.scrollLeft += e.deltaY || e.deltaX;
        }
    };

    return (
        <div className={`relative group/table-wrapper ${className}`}>
            {/* Edge Shadow Left */}
            {canScrollLeft && (
                <div
                    className="pointer-events-none absolute top-0 bottom-0 left-0 z-20 w-5 bg-gradient-to-r from-slate-900/10 to-transparent transition-opacity duration-200"
                    aria-hidden="true"
                />
            )}

            {/* Edge Shadow Right */}
            {canScrollRight && (
                <div
                    className="pointer-events-none absolute top-0 bottom-0 right-0 z-20 w-5 bg-gradient-to-l from-slate-900/10 to-transparent transition-opacity duration-200"
                    aria-hidden="true"
                />
            )}

            {/* Main Table Scroll Container */}
            <div
                ref={containerRef}
                onMouseDown={handleMouseDown}
                onWheel={handleWheel}
                style={{ maxHeight }}
                className={`overflow-auto rounded-lg border bg-white select-none ${
                    isDragging ? 'cursor-grabbing' : hasOverflow ? 'cursor-grab' : ''
                } ${containerClassName}`}
            >
                {children}
            </div>

            {/* Floating Scrollbar fixed to bottom of screen */}
            {showFloatingScrollbar && (
                <div
                    ref={scrollbarRef}
                    onScroll={handleFloatingScroll}
                    className="fixed bottom-0 z-50 overflow-x-auto bg-slate-900/80 backdrop-blur-md border-t border-slate-700/50 shadow-2xl transition-all duration-200 rounded-t-md"
                    style={{
                        left: `${scrollbarBounds.left}px`,
                        width: `${scrollbarBounds.width}px`,
                        height: '14px',
                    }}
                >
                    <div style={{ width: `${scrollWidth}px`, height: '1px' }} />
                </div>
            )}
        </div>
    );
}
