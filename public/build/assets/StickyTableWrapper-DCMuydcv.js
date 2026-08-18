import{d as r,j as l}from"./app-DC4mFA6V.js";function K({children:W,className:j="",containerClassName:y="",maxHeight:R="calc(100vh - 280px)",enableDrag:p=!0,enableWheelScroll:D=!0}){const s=r.useRef(null),c=r.useRef(null),a=r.useRef(null),i=r.useRef(null),[h,$]=r.useState(!1),[z,N]=r.useState(!1),[v,X]=r.useState(0),[M,C]=r.useState({left:0,width:0}),[I,x]=r.useState(!1),[O,m]=r.useState(!1),[w,E]=r.useState(!1),f=r.useRef({startX:0,scrollLeft:0,isMouseDown:!1}),u=r.useRef(!1),d=r.useCallback(()=>{const t=s.current;if(!t)return;const e=t.scrollWidth,o=t.clientWidth,n=e>o+2;$(n),X(e);const b=t.scrollLeft;x(b>5),m(b<e-o-5);const g=t.getBoundingClientRect(),H=window.innerHeight,V=g.top<H-50&&g.bottom>80;N(n&&V),C({left:g.left,width:g.width}),u.current||(u.current=!0,c.current&&(c.current.scrollLeft=b),a.current&&(a.current.scrollLeft=b),i.current&&(i.current.scrollLeft=b),u.current=!1)},[]);r.useEffect(()=>{const t=s.current;if(!t)return;d();const e=()=>{if(u.current)return;u.current=!0;const n=t.scrollLeft;c.current&&(c.current.scrollLeft=n),a.current&&(a.current.scrollLeft=n),i.current&&(i.current.scrollLeft=n),x(n>5),m(n<t.scrollWidth-t.clientWidth-5),u.current=!1},o=new ResizeObserver(d);return o.observe(t),window.addEventListener("scroll",d,{passive:!0}),window.addEventListener("resize",d,{passive:!0}),t.addEventListener("scroll",e,{passive:!0}),()=>{o.disconnect(),window.removeEventListener("scroll",d),window.removeEventListener("resize",d),t.removeEventListener("scroll",e)}},[d]);const L=t=>{if(u.current||!s.current||!t.current)return;u.current=!0;const e=t.current.scrollLeft;s.current.scrollLeft=e,c.current&&t!==c&&(c.current.scrollLeft=e),a.current&&t!==a&&(a.current.scrollLeft=e),i.current&&t!==i&&(i.current.scrollLeft=e),x(e>5),m(e<s.current.scrollWidth-s.current.clientWidth-5),u.current=!1},T=t=>{if(!D||!h||!s.current)return;const e=s.current,o=Math.abs(t.deltaX)>Math.abs(t.deltaY)?t.deltaX:t.deltaY;if(Math.abs(o)<1)return;const n=e.scrollLeft>0&&o<0,b=e.scrollLeft<e.scrollWidth-e.clientWidth-1&&o>0;(n||b||t.shiftKey)&&(t.preventDefault(),e.scrollLeft+=o*1.2)},B=t=>{if(!p||!h)return;const e=t.target;e.closest("button")||e.closest("a")||e.closest("input")||e.closest("select")||e.closest("textarea")||e.closest('[role="button"]')||e.closest("[data-no-drag]")||(f.current={startX:t.clientX,scrollLeft:s.current.scrollLeft,isMouseDown:!0})},S=r.useCallback(t=>{if(!f.current.isMouseDown||!s.current)return;const e=t.clientX-f.current.startX;Math.abs(e)>5&&!w&&E(!0),(w||Math.abs(e)>5)&&(s.current.scrollLeft=f.current.scrollLeft-e)},[w]),k=r.useCallback(()=>{f.current.isMouseDown&&(f.current.isMouseDown=!1,setTimeout(()=>E(!1),50))},[]);return r.useEffect(()=>{if(p)return window.addEventListener("mousemove",S),window.addEventListener("mouseup",k),()=>{window.removeEventListener("mousemove",S),window.removeEventListener("mouseup",k)}},[p,S,k]),l.jsxs("div",{className:`relative group/table-wrapper ${j}`,children:[l.jsx("style",{children:`
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
            `}),h&&l.jsx("div",{ref:c,onScroll:()=>L(c),className:"custom-table-scrollbar overflow-x-auto bg-slate-100 border-b border-slate-200 rounded-t-lg transition-all",style:{height:"14px"},children:l.jsx("div",{style:{width:`${v}px`,height:"1px"}})}),I&&l.jsx("div",{className:"pointer-events-none absolute top-0 bottom-0 left-0 z-20 w-6 bg-gradient-to-r from-slate-900/15 to-transparent transition-opacity duration-200","aria-hidden":"true"}),O&&l.jsx("div",{className:"pointer-events-none absolute top-0 bottom-0 right-0 z-20 w-6 bg-gradient-to-l from-slate-900/15 to-transparent transition-opacity duration-200","aria-hidden":"true"}),l.jsx("div",{ref:s,onMouseDown:B,onWheel:T,style:{maxHeight:R},className:`custom-table-scrollbar overflow-auto border bg-white ${w?"cursor-grabbing":h?"cursor-grab":""} ${y}`,children:W}),h&&l.jsx("div",{ref:a,onScroll:()=>L(a),className:"custom-table-scrollbar overflow-x-auto bg-slate-100 border-t border-slate-200 rounded-b-lg transition-all",style:{height:"14px"},children:l.jsx("div",{style:{width:`${v}px`,height:"1px"}})}),z&&l.jsx("div",{ref:i,onScroll:()=>L(i),className:"floating-table-scrollbar fixed bottom-0 z-50 overflow-x-auto bg-slate-900/95 backdrop-blur-md border-t border-slate-700 shadow-2xl transition-all duration-150 rounded-t-lg",style:{left:`${M.left}px`,width:`${M.width}px`,height:"16px"},children:l.jsx("div",{style:{width:`${v}px`,height:"1px"}})})]})}export{K as S};
