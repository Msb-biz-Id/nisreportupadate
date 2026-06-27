import { Link } from '@inertiajs/react';

export default function GuestLayout({ children }) {
    return (
        <div 
            className="min-h-screen flex flex-col justify-center items-center px-4 py-12 relative overflow-hidden font-sans antialiased bg-black"
        >
            <div className="w-full max-w-md z-10 space-y-6">
                {/* Logo Header */}
                <div className="flex flex-col items-center">
                    <Link href="/" className="inline-flex items-center gap-3 bg-white/5 backdrop-blur-md rounded-2xl px-5 py-3 border border-white/10 hover:bg-white/10 transition duration-200">
                        <div className="w-9 h-9 rounded-xl flex items-center justify-center font-black text-base text-white shadow-lg bg-primary">
                            N
                        </div>
                        <span className="font-bold text-white tracking-widest text-xs uppercase">NIS Report</span>
                    </Link>
                </div>

                {/* Inner Card wrapper */}
                <div className="bg-white rounded-[32px] shadow-2xl shadow-black/30 border border-slate-100 p-8">
                    {children}
                </div>

                {/* Footer copyright */}
                <p className="text-center text-xs text-white/40">
                    &copy; {new Date().getFullYear()} &mdash; NIS Report
                </p>
            </div>
        </div>
    );
}
