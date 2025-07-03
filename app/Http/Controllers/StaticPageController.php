<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class StaticPageController extends Controller
{
    // سياسة الخصوصية
    public function privacyPolicy()
    {
        return view('privacy-policy');
    }

    // حذف البيانات
    public function dataDeletion()
    {
        return view('data-deletion');
    }

    // شروط الخدمة
    public function termsOfService()
    {
        return view('terms-of-service');
    }
}
