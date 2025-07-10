<!DOCTYPE html>
<html lang="ar" dir="rtl">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sahelnaha - سهلناها</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://unpkg.com/aos@2.3.1/dist/aos.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@400;600;700&display=swap" rel="stylesheet">
    <style>
        .arabic-font {
            font-family: 'Cairo', sans-serif;
            text-align: right;
        }

        .english-font {
            font-family: 'Arial', sans-serif;
            text-align: left;
        }

        .header-gradient {
            background: linear-gradient(135deg, #20776B 0%, #145C52 100%);
            background-size: 200% 200%;
            animation: header-shine 6s ease infinite;
        }

        .card-shadow {
            box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1), 0 4px 6px -4px rgba(0, 0, 0, 0.1);
        }

        @keyframes float {

            0%,
            100% {
                transform: translateY(0);
            }

            50% {
                transform: translateY(-15px);
            }
        }

        @keyframes header-shine {
            0% {
                background-position: 0% 50%;
            }

            50% {
                background-position: 100% 50%;
            }

            100% {
                background-position: 0% 50%;
            }
        }

        .float-animation {
            animation: float 4s ease-in-out infinite;
        }

        .smooth-hover {
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        }
    </style>
</head>

<body class="bg-gray-50">
    <header class="fixed w-full header-gradient py-4 shadow-lg z-50">
        <nav class="container mx-auto px-4">
            <div class="flex items-center justify-between">
                <div class="flex items-center gap-4" style="text-align: right; direction:rtl">
                    <img src="{{ asset('storage/Logo/Logo.png') }}" alt="Logo"
                        class="h-12 smooth-hover hover:scale-105">
                    <span class="english-font text-white text-xl font-bold hidden md:block">
                        Sahelnaha Mobile Application
                    </span>
                </div>
            </div>
        </nav>
    </header>

    <main>
        <section class="pt-32 pb-20 bg-gradient-to-br from-gray-100 to-gray-200">
            <div class="container mx-auto px-4 flex flex-col md:flex-row items-center gap-12" data-aos="fade-up">
                <div class="md:w-1/2 space-y-8">
                    <h1 class="arabic-font text-4xl md:text-5xl font-bold text-gray-800 leading-tight">
                        <span class="english-font block mb-4 text-gray-700">Smart Services at Your Fingertips</span>
                        <span class="arabic-font text-gray-700">سهلناها.. خدماتك كلها في تطبيق واحد</span>
                    </h1>
                    <div class="flex flex-wrap gap-4">
                        <button
                            class="bg-[#20776B] text-white english-font px-8 py-4 rounded-full smooth-hover hover:bg-[#1a5e52] hover:scale-105 shadow-lg">
                            Coming Soon
                        </button>
                    </div>
                </div>
                <div class="md:w-1/2 float-animation" data-aos="zoom-in">
                    <img src="{{ asset('storage/logo/app-preview.png') }}" alt="App Preview"
                        class="mx-auto w-2/3 md:w-1/2 xl:w-[40%] smooth-hover hover:rotate-2">
                </div>
            </div>
        </section>

        <!-- Main Features -->
        <section class="py-20 bg-white">
            <div class="container mx-auto px-4">
                <h2 class="text-center english-font text-4xl font-bold text-[#20776B] mb-16" data-aos="fade-up">
                    Powerful Features of Sahelnaha
                </h2>

                <div class="grid md:grid-cols-2 gap-10">
                    <div class="bg-white p-6 rounded-xl card-shadow" data-aos="fade-up">
                        <h3 class="text-xl font-bold text-[#20776B] mb-2">1. AI Image Recognition</h3>
                        <p class="arabic-font text-gray-700">
                            عندك قطعة غيار ومش عارف اسمها؟ صوّرها، والسيستم الذكي هيحلل الصورة ويعرضلك القطعة الصح من
                            الماركت.
                        </p>
                    </div>

                    <div class="bg-white p-6 rounded-xl card-shadow" data-aos="fade-up" data-aos-delay="100">
                        <h3 class="text-xl font-bold text-[#20776B] mb-2">2. Technician QR Code System</h3>
                        <p class="arabic-font text-gray-700">
                            لما الفني يوصلك، هتعمل Scan للـ QR بتاعه، وتطمن إنه الشخص المعتمد من النظام.
                        </p>
                    </div>

                    <div class="bg-white p-6 rounded-xl card-shadow" data-aos="fade-up" data-aos-delay="200">
                        <h3 class="text-xl font-bold text-[#20776B] mb-2">3. AI-powered Chatbot</h3>
                        <p class="arabic-font text-gray-700">
                            شات بوت ذكي يساعدك في أي وقت، يشخص المشكلة ويقولك الحل وقطع الغيار اللي محتاجها.
                        </p>
                    </div>

                    <div class="bg-white p-6 rounded-xl card-shadow" data-aos="fade-up" data-aos-delay="300">
                        <h3 class="text-xl font-bold text-[#20776B] mb-2">4. Integrated Marketplace</h3>
                        <p class="arabic-font text-gray-700">
                            تقدر تشتري أي أداة أو قطعة غيار مباشرة من التطبيق، من غير ما تدور بره.
                        </p>
                    </div>

                    <div class="bg-white p-6 rounded-xl card-shadow" data-aos="fade-up" data-aos-delay="400">
                        <h3 class="text-xl font-bold text-[#20776B] mb-2">5. Smart Wallet for Technicians</h3>
                        <p class="arabic-font text-gray-700">
                            محفظة إلكترونية آمنة للفنيين، كل تعامل مسجل ومحسوب، والتحصيل يتم بشكل فوري.
                        </p>
                    </div>

                    <div class="bg-white p-6 rounded-xl card-shadow" data-aos="fade-up" data-aos-delay="500">
                        <h3 class="text-xl font-bold text-[#20776B] mb-2">6. Transparent Review & Rating System</h3>
                        <p class="arabic-font text-gray-700">
                            كل عميل يقدر يقيّم الفني بعد الخدمة، ونظام التقييم ظاهر بوضوح للجميع.
                        </p>
                    </div>

                    <div class="bg-white p-6 rounded-xl card-shadow" data-aos="fade-up" data-aos-delay="600">
                        <h3 class="text-xl font-bold text-[#20776B] mb-2">7. Geolocation Services</h3>
                        <p class="arabic-font text-gray-700">
                            التطبيق بيستخدم تحديد الموقع عشان يوصلك بأقرب فني بسرعة وسهولة.
                        </p>
                    </div>

                    <div class="bg-white p-6 rounded-xl card-shadow" data-aos="fade-up" data-aos-delay="700">
                        <h3 class="text-xl font-bold text-[#20776B] mb-2">8. Loyalty Program & Rewards</h3>
                        <p class="arabic-font text-gray-700">
                            كل ما تستخدم التطبيق أكتر، تجمع نقاط ومكافآت وخصومات حصرية.
                        </p>
                    </div>
                </div>
            </div>
        </section>
    </main>

    <footer class="header-gradient text-white py-6">
        <div class="container mx-auto px-4 flex flex-col md:flex-row justify-between items-center gap-6 text-sm">

            <!-- الدعم الفني -->
            <div class="text-right arabic-font">
                <p class="text-white">الدعم الفني:</p>
                <p class="english-font">sahelnaha.co@gmail.com</p>
            </div>

            <!-- روابط كأزرار -->
            <div class="flex flex-wrap gap-4 justify-center">
                <a href="{{ route('privacy') }}"
                    class="bg-white text-[#20776B] font-semibold px-4 py-2 rounded-full hover:bg-gray-100 transition">
                    Privacy Policy
                </a>
                <a href="{{ route('data-deletion') }}"
                    class="bg-white text-[#20776B] font-semibold px-4 py-2 rounded-full hover:bg-gray-100 transition">
                    Data Deletion
                </a>
                <a href="{{ route('terms') }}"
                    class="bg-white text-[#20776B] font-semibold px-4 py-2 rounded-full hover:bg-gray-100 transition">
                    Terms of Service
                </a>
            </div>

            <!-- الحقوق -->
            <div class="english-font text-center">
                © 2025 Sahelnaha. All rights reserved.
            </div>

        </div>
    </footer>

    <script src="https://unpkg.com/aos@2.3.1/dist/aos.js"></script>
    <script>
        AOS.init({
            duration: 800,
            once: true,
            offset: 100,
            easing: 'ease-out-quad'
        });
    </script>
</body>

</html>
