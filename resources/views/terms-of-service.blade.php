<!DOCTYPE html>
<html lang="ar" dir="rtl">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>شروط الخدمة - سهلناها</title>
    <style>
        :root {
            --primary: #20776B;
            --light: #e9ecef;
            --dark: #1a1a1a;
            --accent: #FF6B35;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Tajawal', 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }

        body {
            background-color: var(--light);
            color: var(--dark);
            line-height: 1.8;
        }

        .container {
            max-width: 900px;
            margin: 0 auto;
            padding: 30px;
        }

        header {
            background: linear-gradient(135deg, var(--primary), #1a5e55);
            color: white;
            padding: 40px 0;
            text-align: center;
            border-bottom: 5px solid #1a5e55;
            position: relative;
            overflow: hidden;
        }

        header::before {
            content: "";
            position: absolute;
            top: -50%;
            left: -50%;
            width: 200%;
            height: 200%;
            background: radial-gradient(circle, rgba(255, 255, 255, 0.1) 0%, rgba(255, 255, 255, 0) 70%);
            transform: rotate(30deg);
        }

        .logo {
            font-size: 2.5rem;
            font-weight: 700;
            margin-bottom: 10px;
            position: relative;
            z-index: 2;
        }

        .tagline {
            font-size: 1.2rem;
            opacity: 0.9;
            position: relative;
            z-index: 2;
        }

        .section {
            background: white;
            border-radius: 12px;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.05);
            padding: 30px;
            margin-bottom: 30px;
            border-left: 4px solid var(--primary);
            transition: transform 0.3s, box-shadow 0.3s;
        }

        .section:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.1);
        }

        h1,
        h2,
        h3 {
            color: var(--primary);
            margin-bottom: 20px;
        }

        h1 {
            font-size: 2rem;
            text-align: center;
            margin: 30px 0;
            position: relative;
        }

        h1::after {
            content: "";
            display: block;
            width: 100px;
            height: 4px;
            background: var(--accent);
            margin: 15px auto;
            border-radius: 2px;
        }

        h2 {
            font-size: 1.5rem;
            border-bottom: 2px solid var(--light);
            padding-bottom: 10px;
            display: flex;
            align-items: center;
        }

        h2::before {
            content: "•";
            color: var(--accent);
            margin-left: 10px;
            font-size: 1.8rem;
        }

        p,
        ul,
        ol {
            margin-bottom: 15px;
            font-size: 1.05rem;
        }

        ul,
        ol {
            padding-right: 20px;
        }

        li {
            margin-bottom: 10px;
            position: relative;
            padding-right: 20px;
        }

        li::before {
            content: "";
            position: absolute;
            right: 0;
            top: 10px;
            width: 8px;
            height: 8px;
            background-color: var(--primary);
            border-radius: 50%;
        }

        .highlight {
            background-color: rgba(32, 119, 107, 0.1);
            padding: 20px;
            border-radius: 12px;
            border-right: 3px solid var(--primary);
            margin: 25px 0;
            position: relative;
        }

        .highlight::before {
            content: "!";
            position: absolute;
            left: -15px;
            top: -15px;
            background: var(--accent);
            color: white;
            width: 30px;
            height: 30px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: bold;
            font-size: 1.2rem;
        }

        .warning {
            background-color: rgba(255, 193, 7, 0.15);
            padding: 20px;
            border-radius: 12px;
            border-right: 3px solid #ffc107;
            margin: 25px 0;
        }

        .contact-box {
            background: linear-gradient(135deg, #20776B, #1a5e55);
            color: white;
            padding: 25px;
            border-radius: 12px;
            margin-top: 30px;
            position: relative;
            overflow: hidden;
        }

        .contact-box::before {
            content: "";
            position: absolute;
            top: 0;
            right: 0;
            width: 100px;
            height: 100px;
            background: radial-gradient(circle, rgba(255, 255, 255, 0.2) 0%, rgba(255, 255, 255, 0) 70%);
        }

        .btn {
            display: inline-block;
            background-color: var(--accent);
            color: white;
            padding: 12px 25px;
            border-radius: 8px;
            text-decoration: none;
            font-weight: 600;
            margin-top: 15px;
            transition: all 0.3s;
            border: none;
            cursor: pointer;
            box-shadow: 0 4px 10px rgba(255, 107, 53, 0.3);
        }

        .btn:hover {
            background-color: #e05a2b;
            transform: translateY(-3px);
            box-shadow: 0 6px 15px rgba(255, 107, 53, 0.4);
        }

        .table-container {
            overflow-x: auto;
            margin: 25px 0;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            border-radius: 10px;
            overflow: hidden;
        }

        th {
            background-color: var(--primary);
            color: white;
            padding: 15px;
            text-align: right;
        }

        td {
            padding: 12px 15px;
            border-bottom: 1px solid var(--light);
        }

        tr:nth-child(even) {
            background-color: rgba(32, 119, 107, 0.05);
        }

        tr:hover {
            background-color: rgba(32, 119, 107, 0.1);
        }

        .icon-box {
            display: flex;
            align-items: center;
            margin: 20px 0;
            background: rgba(32, 119, 107, 0.05);
            padding: 15px;
            border-radius: 10px;
        }

        .icon {
            font-size: 2rem;
            margin-left: 15px;
            color: var(--primary);
            min-width: 50px;
            text-align: center;
        }

        footer {
            background-color: var(--dark);
            color: white;
            text-align: center;
            padding: 25px;
            margin-top: 40px;
            border-top: 5px solid var(--primary);
        }

        .footer-links {
            display: flex;
            justify-content: center;
            gap: 20px;
            margin-top: 15px;
        }

        .footer-links a {
            color: #a0d8cf;
            text-decoration: none;
            transition: color 0.3s;
        }

        .footer-links a:hover {
            color: white;
            text-decoration: underline;
        }

        @media (max-width: 768px) {
            .container {
                padding: 15px;
            }

            .section {
                padding: 20px;
            }

            h1 {
                font-size: 1.7rem;
            }

            h2 {
                font-size: 1.3rem;
            }

            .icon-box {
                flex-direction: column;
                text-align: center;
            }

            .icon {
                margin: 0 0 15px 0;
            }
        }
    </style>
    <link href="https://fonts.googleapis.com/css2?family=Tajawal:wght@400;500;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>

<body>
    <header>
        <div class="container">
            <div class="logo">Sahelnaha</div>
            <div class="tagline">نوصل الخدمة بكل سهولة</div>
        </div>
    </header>

    <div class="container">
        <h1>شروط وأحكام استخدام تطبيق سهلناها</h1>

        <div class="section">
            <h2>القبول بالشروط</h2>
            <p>باستخدامك تطبيق سهلناها ("التطبيق")، فإنك توافق على الالتزام بهذه الشروط والأحكام ("الشروط"). إذا كنت لا
                توافق على هذه الشروط، فيرجى عدم استخدام التطبيق. نحتفظ بالحق في تعديل هذه الشروط في أي وقت، وسيتم إعلامك
                بالتحديثات.</p>

            <div class="highlight">
                <p>يُعتبر استمرار استخدامك للتطبيق بعد أي تعديلات دليلاً على موافقتك على الشروط المعدلة.</p>
            </div>
        </div>

        <div class="section">
            <h2>وصف الخدمة</h2>
            <p>يوفر تطبيق سهلناها منصة رقمية تتيح للمستخدمين ("العميل") طلب خدمات الصيانة المختلفة، وتمكين الفنيين
                المؤهلين ("مقدم الخدمة") من تقديم هذه الخدمات. الخدمات تشمل لكن لا تقتصر على:</p>

            <div class="icon-box">
                <div class="icon">
                    <i class="fas fa-tools"></i>
                </div>
                <div>
                    <ul>
                        <li>صيانة الأجهزة الكهربائية والمنزلية</li>
                        <li>صيانة التكييف والتبريد</li>
                        <li>صيانة السباكة والتمديدات</li>
                        <li>صيانة الأجهزة الإلكترونية والحاسوب</li>
                        <li>خدمات النجارة والدهانات</li>
                        <li>خدمات صيانة السيارات (في مواقع محددة)</li>
                    </ul>
                </div>
            </div>
        </div>

        <div class="section">
            <h2>التسجيل والحساب</h2>
            <p>لطلب الخدمات أو تقديمها، يجب عليك إنشاء حساب في التطبيق:</p>

            <ul>
                <li>يجب أن تكون بالغًا (18 سنة فأكثر) لإنشاء حساب</li>
                <li>يجب تقديم معلومات دقيقة وكاملة أثناء التسجيل</li>
                <li>أنت المسؤول الوحيد عن سرية معلومات حسابك</li>
                <li>يجب تحديث معلوماتك فور تغيرها</li>
                <li>للفنيين: يجب تقديم وثائق تثبت المؤهلات والخبرات</li>
            </ul>

            <div class="warning">
                <p>يحق لنا تعليق أو إغلاق الحسابات التي تنتهك هذه الشروط أو تنشر محتوى غير قانوني أو مسيء دون سابق
                    إنذار.</p>
            </div>
        </div>

        <div class="section">
            <h2>طلبات الخدمة والدفع</h2>
            <p>عند طلب خدمة من خلال التطبيق:</p>

            <ul>
                <li>يجب تحديد مواصفات الخدمة المطلوبة بدقة</li>
                <li>سيتم تقديم عرض سعر مبدئي قابلاً للتعديل بعد المعاينة</li>
                <li>يمكنك اختيار طريقة الدفع المناسبة (نقداً، بطاقة ائتمان، محفظة إلكترونية)</li>
                <li>للدفع الإلكتروني: يتم خصم المبلغ بعد إكمال الخدمة بنجاح</li>
                <li>نحن وسيط فقط ولا نتحمل مسؤولية الخدمات المقدمة</li>
            </ul>

            <div class="table-container">
                <table>
                    <thead>
                        <tr>
                            <th>نوع الخدمة</th>
                            <th>ضمان الجودة</th>
                            <th>فترة الضمان</th>
                            <th>سياسة الإلغاء</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td>صيانة بسيطة</td>
                            <td>90 يوم</td>
                            <td>30 يوم</td>
                            <td>إلغاء مجاني قبل 24 ساعة</td>
                        </tr>
                        <tr>
                            <td>صيانة متوسطة</td>
                            <td>180 يوم</td>
                            <td>60 يوم</td>
                            <td>إلغاء مجاني قبل 48 ساعة</td>
                        </tr>
                        <tr>
                            <td>صيانة معقدة</td>
                            <td>365 يوم</td>
                            <td>90 يوم</td>
                            <td>إلغاء مجاني قبل 72 ساعة</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

        <div class="section">
            <h2>مسؤوليات الفنيين</h2>
            <p>عند قبولك كفني في سهلناها:</p>

            <div class="icon-box">
                <div class="icon">
                    <i class="fas fa-user-cog"></i>
                </div>
                <div>
                    <ul>
                        <li>يجب تقديم خدمات عالية الجودة وفق المعايير المتفق عليها</li>
                        <li>الالتزام بالمواعيد المحددة مع العملاء</li>
                        <li>توفير الأدوات والمواد اللازمة للخدمة (ما لم يتفق على غير ذلك)</li>
                        <li>احترام خصوصية العميل وممتلكاته</li>
                        <li>التواصل المهني واللبق مع العملاء</li>
                        <li>تحديث حالة الطلب بشكل مستمر في التطبيق</li>
                    </ul>
                </div>
            </div>
        </div>

        <div class="section">
            <h2>مسؤوليات العملاء</h2>
            <p>كمستخدم لخدمات سهلناها، أنت توافق على:</p>

            <div class="icon-box">
                <div class="icon">
                    <i class="fas fa-users"></i>
                </div>
                <div>
                    <ul>
                        <li>توفير معلومات دقيقة عن المشكلة والمكان</li>
                        <li>تهيئة المكان المناسب لإجراء الصيانة</li>
                        <li>الالتزام بالمواعيد المتفق عليها</li>
                        <li>دفع المستحقات المتفق عليها بعد إكمال الخدمة</li>
                        <li>معاملة الفنيين باحترام وأخلاق</li>
                        <li>تقديم تقييم صادق للخدمة بعد الانتهاء</li>
                    </ul>
                </div>
            </div>

            <div class="highlight">
                <p>يحق للفني رفض تقديم الخدمة في حال وجود تهديد لسلامته أو في حال عدم توفر الشروط المناسبة للعمل.</p>
            </div>
        </div>

        <div class="section">
            <h2>الرسوم والعمولات</h2>
            <p>تطبق سهلناها رسوم خدمة على المعاملات:</p>

            <ul>
                <li>عمولة 15% على قيمة الخدمة للطلبات العادية</li>
                <li>عمولة 10% على الطلبات المتكررة (أكثر من 3 مرات لنفس العميل)</li>
                <li>رسوم إضافية 5% للخدمات العاجلة (خلال 4 ساعات)</li>
                <li>لا توجد رسوم اشتراك شهرية للفنيين</li>
                <li>تسوية المدفوعات للفنيين كل أسبوعين</li>
            </ul>

            <div class="warning">
                <p>جميع الأسعار تشمل ضريبة القيمة المضافة حسب أنظمة جمهورية مصر العربية.</p>
            </div>
        </div>

        <div class="section">
            <h2>الضمان وسياسة الاسترداد</h2>
            <p>نسعى لتقديم أفضل الخدمات، وفي حال عدم الرضا:</p>

            <ul>
                <li>ضمان إصلاح لمدة 90 يوم للخدمات الأساسية</li>
                <li>يمكن طلب إعادة الخدمة مجاناً في حال وجود نفس المشكلة خلال فترة الضمان</li>
                <li>في حال عدم إمكانية الإصلاح، يتم استرداد كامل المبلغ المدفوع</li>
                <li>يجب الإبلاغ عن المشكلة خلال 48 ساعة من انتهاء الخدمة</li>
            </ul>

            <div class="contact-box">
                <h3>للمطالبات والاستفسارات</h3>
                <p>بريد إلكتروني: sahelnaha.co@gmail.com</p>
                <p>هاتف الدعم: +201094698814</p>
                <p>ساعات العمل: السبت - الخميس (8 ص - 10 م)</p>
                <a href="/contact" class="btn">تواصل مع الدعم الفني</a>
            </div>
        </div>

        <div class="section">
            <h2>الحد من المسؤولية</h2>
            <p>سهلناها منصة وسيطة فقط، وليست مسؤولة عن:</p>

            <ul>
                <li>أي أضرار تلحق بالممتلكات أثناء تقديم الخدمة</li>
                <li>تأخير أو عدم تنفيذ الخدمة بسبب ظروف خارجة عن إرادتنا</li>
                <li>الخسائر غير المباشرة أو العرضية</li>
                <li>أي نزاعات تنشأ بين العملاء والفنيين</li>
            </ul>

            <div class="highlight">
                <p>يجب على الفنيين الحصول على تأمين مسؤولية مهنية لتغطية الأضرار المحتملة أثناء تقديم الخدمات.</p>
            </div>
        </div>
    </div>

    <footer>
        <div class="container">
            <p>© 2025 Sahelnaha. كل الحقوق محفوظة</p>
            <div class="footer-links">
                <a href="/privacy-policy">سياسة الخصوصية</a>
                <a href="/terms-of-service">شروط الخدمة</a>
                <a href="/data-deletion">حذف البيانات</a>
                <a href="/contact">تواصل معنا</a>
            </div>
            <p>منصة سهلناها - ربط العملاء بالفنيين المحترفين منذ 2023</p>
        </div>
    </footer>
</body>

</html>
