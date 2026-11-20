<?php

namespace App\Enums;

enum OtpPurpose: string
{
    case Login = 'login';
    case EmailChange = 'email_change';
    case QuickResultEmail = 'quick_result_email';
    case AdminLogin = 'admin_login';
}
