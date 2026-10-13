@extends('emails.layouts.base')

@section('title', 'Your password reset code')

@section('content')
    <h1 style="margin:0 0 12px;font-size:20px;line-height:1.3;color:#18181b;">Reset your password</h1>
    <p style="margin:0 0 24px;">We received a request to reset your password. Enter the code below to continue.</p>

    <p style="margin:0 0 24px;text-align:center;">
        <span style="display:inline-block;padding:14px 24px;background-color:#f4f4f5;border-radius:10px;font-size:28px;font-weight:700;letter-spacing:8px;color:#18181b;">{{ $code }}</span>
    </p>

    <p style="margin:0;">This code expires in {{ $expiresInMinutes }} minutes.</p>
@endsection

@section('footer')
    If you did not request a password reset, you can safely ignore this email.
@endsection
