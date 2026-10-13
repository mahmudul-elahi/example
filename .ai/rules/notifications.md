---
paths:
  - 'app/Notifications/**'
  - 'resources/views/emails/**'
---

# Notifications

## Queue every notification
Notifications always implement `Illuminate\Contracts\Queue\ShouldQueue` (alongside the `Queueable` trait) so they are queued instead of sent inline during a request. Never leave a notification synchronous — delivery must go through the configured `QUEUE_CONNECTION`, which requires a running worker.

## Use a custom Blade view for notification emails
Notification emails render a custom Blade view, never the default `notifications::email` markdown template. Pass one data array to both `view()` and `text()`: `->view('emails.auth.verify-email-otp', $data)->text('emails.auth.verify-email-otp-text', $data)`. Keep templates under `resources/views/emails/**`, extend the shared HTML layout `emails.layouts.base`, and use inline CSS because email clients do not load the app stylesheet. Every email view has a plain-text counterpart.
