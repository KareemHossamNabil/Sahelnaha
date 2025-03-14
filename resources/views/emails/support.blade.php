<!DOCTYPE html>
<html lang="ar" dir="rtl">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>طلب دعم فني</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            background-color: #f4f4f4;
            margin: 0;
            padding: 0;
            text-align: right;
            direction: rtl;
        }

        .container {
            max-width: 600px;
            margin: 20px auto;
            background: #ffffff;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0px 0px 10px rgba(0, 0, 0, 0.1);
        }

        .header {
            background: #20776B;
            color: white;
            padding: 15px;
            font-size: 22px;
            font-weight: bold;
            text-align: center;
            border-top-left-radius: 8px;
            border-top-right-radius: 8px;
        }

        .info {
            font-size: 16px;
            margin-bottom: 10px;
            display: flex;
            align-items: center;
        }

        .icon {
            margin-left: 10px;
            font-size: 18px;
            color: #20776B;
        }

        .message {
            background: #e9ecef;
            padding: 10px;
            border-radius: 5px;
            margin-top: 10px;
            font-size: 14px;
        }

        .footer {
            margin-top: 20px;
            font-size: 12px;
            color: #888;
            text-align: center;
        }
    </style>
</head>

<body>
    <div class="container">
        <div class="header">طلب دعم فني جديد</div>

        <div class="info"><span class="icon">👤</span> <strong>الاسم:</strong> {{ $data['name'] }}</div>
        <div class="info"><span class="icon">📍</span> <strong>العنوان:</strong> {{ $data['address'] }}</div>
        <div class="info"><span class="icon">📞</span> <strong>رقم الهاتف:</strong> {{ $data['phone'] }}</div>
        <div class="info"><span class="icon">✉️</span> <strong>الإيميل:</strong> {{ $data['email'] }}</div>

        <div class="message">
            <strong>📩 الرسالة:</strong>
            <p>{{ $data['message'] }}</p>
        </div>

        <p class="footer">تم إرسال هذا البريد تلقائيًا من نموذج الدعم الفني.</p>
    </div>
</body>

</html>
