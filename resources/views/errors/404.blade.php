<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex, nofollow">
    <title>404 - Halaman Tidak Ditemukan</title>
    
    <!-- Google Fonts: Outfit & Plus Jakarta Sans -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@400;500;600;700;800&family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    
    <!-- Tailwind CSS Play CDN -->
    <script src="https://cdn.tailwindcss.com"></script>
    
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    fontFamily: {
                        sans: ['"Plus Jakarta Sans"', 'sans-serif'],
                        outfit: ['"Outfit"', 'sans-serif'],
                    },
                    animation: {
                        'pulse-slow': 'pulse 3s cubic-bezier(0.4, 0, 0.6, 1) infinite',
                        'float': 'float 6s ease-in-out infinite',
                    },
                    keyframes: {
                        float: {
                            '0%, 100%': { transform: 'translateY(0)' },
                            '50%': { transform: 'translateY(-12px)' },
                        }
                    }
                }
            }
        }
    </script>
    
    <style>
        body {
            background-color: #F8FAFC;
        }
    </style>
</head>
<body class="font-sans antialiased text-slate-800 min-h-screen flex flex-col items-center justify-center relative overflow-hidden bg-gradient-to-tr from-slate-50 via-white to-blue-50/50">
    
    <!-- Decorative background elements -->
    <div class="absolute -top-40 -left-40 w-96 h-96 bg-blue-400/10 rounded-full blur-3xl pointer-events-none"></div>
    <div class="absolute -bottom-40 -right-40 w-96 h-96 bg-indigo-400/10 rounded-full blur-3xl pointer-events-none"></div>

    <div class="max-w-md w-full px-6 py-12 text-center relative z-10">
        <!-- Floating Illustration / Icon -->
        <div class="mb-8 relative flex justify-center animate-float">
            <div class="absolute inset-0 bg-blue-500/5 blur-2xl rounded-full scale-75"></div>
            <div class="relative bg-white border border-slate-100/80 rounded-3xl p-6 shadow-xl shadow-slate-100 flex items-center justify-center">
                <svg class="h-16 w-16 text-blue-600 animate-pulse-slow" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M9.172 16.172a4 4 0 015.656 0M9 10h.01M15 10h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
            </div>
        </div>

        <!-- Huge 404 Text -->
        <h1 class="font-outfit text-8xl font-black tracking-tight bg-gradient-to-r from-blue-600 via-indigo-600 to-blue-800 bg-clip-text text-transparent select-none drop-shadow-sm leading-none">
            404
        </h1>
        
        <h2 class="mt-4 text-xl font-bold text-slate-900 tracking-tight">
            Halaman Tidak Ditemukan
        </h2>
        
        <p class="mt-3 text-sm text-slate-500 leading-relaxed">
            Maaf, halaman yang Anda tuju tidak dapat kami temukan atau mungkin telah dipindahkan ke alamat lain.
        </p>

        <!-- Actions -->
        <div class="mt-8 flex flex-col gap-3">
            <a href="/" class="inline-flex items-center justify-center gap-2 rounded-2xl text-sm font-bold text-white bg-blue-600 hover:bg-blue-700 active:bg-blue-800 py-3.5 px-6 shadow-md shadow-blue-500/10 transition-all duration-200 hover:-translate-y-0.5">
                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6" />
                </svg>
                Kembali ke Beranda
            </a>
            
            <a href="/track" class="inline-flex items-center justify-center gap-2 rounded-2xl text-sm font-bold text-slate-700 bg-white border border-slate-200 hover:bg-slate-50 active:bg-slate-100 py-3.5 px-6 shadow-sm transition-all duration-200 hover:-translate-y-0.5">
                <svg class="h-4 w-4 text-slate-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                </svg>
                Lacak Pesanan PO
            </a>
        </div>

        <!-- Minimalist branding / footer -->
        <div class="mt-12 pt-6 border-t border-slate-100 text-[11px] font-semibold text-slate-400 uppercase tracking-widest">
            Secure Portal Verification
        </div>
    </div>
</body>
</html>
