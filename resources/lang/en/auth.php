<?php

return [
    'failed'                => 'These credentials do not match our records.',
    'password'              => 'The provided password is incorrect.',
    'throttle'              => 'Too many login attempts. Please try again in :seconds seconds.',
    'password_reset_invalid' => 'This password reset link is invalid or has expired.',
    'password_reset_sent'   => 'If that email is registered, you will receive password reset instructions.',

    'register' => [
        'title'                 => 'Create Account',
        'full_name'             => 'Full Name',
        'email'                 => 'Email Address',
        'password'              => 'Password',
        'password_confirmation' => 'Confirm Password',
        'employee_number'       => 'Employee Number (optional)',
        'department'            => 'Department (optional)',
        'location'              => 'Location (optional)',
        'phone'                 => 'Phone Number (optional)',
        'locale'                => 'Language',
        'submit'                => 'Create Account',
        'have_account'          => 'Already have an account?',
        'login_link'            => 'Log in',
    ],

    'login' => [
        'title'          => 'Log In',
        'email'          => 'Email Address',
        'password'       => 'Password',
        'remember'       => 'Remember me',
        'submit'         => 'Log In',
        'forgot'         => 'Forgot your password?',
        'no_account'     => "Don't have an account?",
        'register_link'  => 'Create Account',
    ],

    'password_request' => [
        'title'       => 'Reset Password',
        'email'       => 'Email Address',
        'submit'      => 'Send Reset Link',
        'back_login'  => 'Back to Login',
    ],

    'password_reset' => [
        'title'                 => 'Set New Password',
        'email'                 => 'Email Address',
        'password'              => 'New Password',
        'password_confirmation' => 'Confirm New Password',
        'submit'                => 'Reset Password',
    ],
];
