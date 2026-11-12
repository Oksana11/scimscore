<?php

namespace App\Support\Mail;

use App\Enums\OtpPurpose;
use App\Models\EmailTemplate;
use App\Models\Setting;
use Illuminate\Support\HtmlString;
use InvalidArgumentException;

/**
 * Editable texts of system emails.
 *
 * Security: admins edit plain text only. It is HTML-escaped, placeholders are replaced by
 * fixed server-side values, and email addresses / https links are turned into links after
 * escaping. Nothing is compiled as Blade. The security-relevant parts (the one-time code,
 * the result table) are fixed in the email layout and cannot be removed by editing.
 */
final class EmailTemplates
{
    public const FOOTER_KEY = 'email_footer';

    public const DEFAULT_FOOTER = "Questions? Contact us at {support_email}.\nThis is an automated message — please don't reply.";

    /**
     * @return array<string, array{label: string, description: string, placeholders: list<string>, subject: string, heading: string, body: string}>
     */
    public static function definitions(): array
    {
        $otp = ['{minutes}', '{support_email}', '{app_name}'];
        $common = ['{support_email}', '{app_name}', '{site_url}'];

        return [
            'login_code' => [
                'label' => 'Sign-in code',
                'description' => 'One-time code for signing in or creating an account. The code itself is always shown below the text.',
                'placeholders' => $otp,
                'subject' => 'Your SCIMSCORE sign-in code',
                'heading' => 'Your one-time code',
                'body' => "Use the code below to sign in to SCIMSCORE. If this email isn't registered yet, entering the code will create your account.\nValid for {minutes} minutes · single use.\nDidn't request this? You can safely ignore this email — nobody can access your account without this code. Never share it with anyone, including SCIMSCORE staff.",
            ],
            'email_change_code' => [
                'label' => 'Confirm new email',
                'description' => 'Code sent to the NEW address when a user changes their sign-in email.',
                'placeholders' => $otp,
                'subject' => 'Confirm your new SCIMSCORE email address',
                'heading' => 'Confirm your new email address',
                'body' => "A request was made to change the sign-in email of a SCIMSCORE account to this address. Enter the code on your Profile page to confirm.\nValid for {minutes} minutes · single use.\nDidn't request this? You can safely ignore this email.",
            ],
            'quick_result_code' => [
                'label' => 'Quick assessment: email code',
                'description' => 'Code that verifies the address before a quick assessment result is emailed.',
                'placeholders' => $otp,
                'subject' => 'Your SCIMSCORE verification code',
                'heading' => 'Your one-time code',
                'body' => "Enter this code on the result page to receive your SCIM assessment result by email.\nValid for {minutes} minutes · single use.\nDidn't request this? You can safely ignore this email.",
            ],
            'admin_login_code' => [
                'label' => 'Administrator sign-in code',
                'description' => 'Step 1 of the administrator sign-in.',
                'placeholders' => $otp,
                'subject' => 'Your SCIMSCORE administrator sign-in code',
                'heading' => 'Your one-time code',
                'body' => "Use the code below for step 1 of the SCIMSCORE administrator sign-in. You will then be asked for the code from your authenticator app.\nValid for {minutes} minutes · single use.\nDidn't request this? Contact {support_email} immediately.",
            ],
            'email_changed' => [
                'label' => 'Sign-in email changed',
                'description' => 'Sent to the OLD address after a confirmed change. Never contains the new address.',
                'placeholders' => $common,
                'subject' => 'Your SCIMSCORE sign-in email was changed',
                'heading' => 'Your sign-in email was changed',
                'body' => "The sign-in email of your SCIMSCORE account has been changed. This address will no longer receive sign-in codes.\nIf you did not make this change, contact support immediately: {support_email}",
            ],
            'assessment_result' => [
                'label' => 'Assessment result',
                'description' => 'Text shown below the result table of an emailed SCIM assessment. The table is always included.',
                'placeholders' => $common,
                'subject' => 'SCIM assessment result',
                'heading' => 'SCIM assessment result',
                'body' => 'Tip: keep these emails in a dedicated folder to find patient identifiers for follow-up assessments.',
            ],
        ];
    }

    public static function keyForOtp(OtpPurpose $purpose): string
    {
        return match ($purpose) {
            OtpPurpose::Login => 'login_code',
            OtpPurpose::EmailChange => 'email_change_code',
            OtpPurpose::QuickResultEmail => 'quick_result_code',
            OtpPurpose::AdminLogin => 'admin_login_code',
        };
    }

    public static function exists(string $key): bool
    {
        return array_key_exists($key, self::definitions());
    }

    /**
     * Current texts (admin override or default), raw — for the edit form.
     *
     * @return array{subject: string, heading: string, body: string, customized: bool}
     */
    public static function current(string $key): array
    {
        $def = self::definitions()[$key] ?? throw new InvalidArgumentException("Unknown email template [{$key}].");
        $row = EmailTemplate::query()->where('key', $key)->first();

        return [
            'subject' => $row->subject ?? $def['subject'],
            'heading' => $row->heading ?? $def['heading'],
            'body' => $row->body ?? $def['body'],
            'customized' => $row !== null,
        ];
    }

    public static function render(string $key): RenderedEmail
    {
        $t = self::current($key);
        $vars = self::variables();

        return new RenderedEmail(
            subject: self::singleLine(strtr($t['subject'], $vars)),
            heading: self::singleLine(strtr($t['heading'], $vars)),
            paragraphs: self::paragraphs(strtr($t['body'], $vars)),
            footer: self::paragraphs(strtr((string) Setting::get(self::FOOTER_KEY, self::DEFAULT_FOOTER), $vars)),
        );
    }

    /**
     * @return array<string, string>
     */
    private static function variables(): array
    {
        return [
            '{support_email}' => Setting::supportEmail(),
            '{app_name}' => 'SCIMSCORE',
            '{site_url}' => rtrim((string) config('app.url'), '/'),
            '{minutes}' => (string) (int) config('scim.otp.ttl_minutes'),
        ];
    }

    private static function singleLine(string $text): string
    {
        return trim((string) preg_replace('/\s+/u', ' ', $text));
    }

    /**
     * Escape first, then linkify: the only HTML in the output is what this method adds.
     *
     * @return list<HtmlString>
     */
    private static function paragraphs(string $text): array
    {
        $out = [];
        foreach (preg_split('/\R/u', $text) ?: [] as $line) {
            $line = trim($line);
            if ($line === '') {
                continue;
            }
            $html = htmlspecialchars($line, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
            $html = (string) preg_replace_callback(
                '~\bhttps://[^\s<>"\']+[^\s<>"\'.,;:!?)]|[A-Za-z0-9._%+-]+@[A-Za-z0-9.-]+\.[A-Za-z]{2,}~',
                static function (array $m): string {
                    $href = str_starts_with($m[0], 'https://') ? $m[0] : 'mailto:'.$m[0];

                    return '<a href="'.$href.'" style="color:#2563eb">'.$m[0].'</a>';
                },
                $html,
            );
            $out[] = new HtmlString($html);
        }

        return $out;
    }
}
