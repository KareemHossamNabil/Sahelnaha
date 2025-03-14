<!DOCTYPE html>
<html lang="ar" dir="rtl">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>تأكيد حسابك | Verify Your Account</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            background-color: #f4f4f4;
            margin: 0;
            padding: 0;
        }

        .container {
            width: 100%;
            max-width: 500px;
            margin: 30px auto;
            background: #ffffff;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0px 0px 10px rgba(0, 0, 0, 0.1);
            text-align: center;
            direction: rtl;
        }

        .header {
            background: #20776B;
            color: white;
            padding: 15px;
            font-size: 20px;
            font-weight: bold;
            border-top-left-radius: 8px;
            border-top-right-radius: 8px;
        }

        .logo {
            margin: 20px 0;
        }

        .otp {
            font-size: 24px;
            font-weight: bold;
            color: #20776B;
            background: #e9ecef;
            display: inline-block;
            padding: 10px 20px;
            border-radius: 5px;
            margin: 10px 0;
        }

        .footer {
            margin-top: 20px;
            font-size: 12px;
            color: #888;
        }
    </style>
</head>

<body>
    <div class="container">
        <div class="header">Sahelnaha | سَـهلـنَـاهـا</div>
        <img class="logo" src="https://drive.google.com/uc?export=view&id=1Ucj5krbAbLSb80Ks-qj4Zu-EfyTSRq28"
            alt="Sahelnaha Logo" width="150" height="auto" style="max-width: 100%; height: auto;">



        <h2>🔐 تأكيد حسابك</h2>
        <p>استخدم الكود أدناه لتأكيد بريدك الإلكتروني:</p>
        <div class="otp">{{ $otp }}</div>
        <p>هذا الكود صالح لفترة محدودة، لا تشاركه مع أي شخص.</p>
        <p class="footer">إذا لم تطلب هذا، يرجى تجاهل هذا البريد الإلكتروني.</p>

        <hr>
        <h2>🔐 Verify Your Account</h2>
        <p>Use the code below to verify your email address:</p>
        <div class="otp">{{ $otp }}</div>
        <p>This code is valid for a limited time. Do not share it with anyone.</p>
        <p class="footer">If you didn't request this, please ignore this email.</p>
    </div>
</body>

</html>
