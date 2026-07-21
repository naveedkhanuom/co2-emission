<?php

namespace App\Support;

use App\Models\User;
use App\Notifications\GeneralNotification;

/**
 * Central place to raise in-app notifications, so wording, icons and colours
 * stay consistent wherever they are triggered. Add a named method here for each
 * kind of event rather than constructing GeneralNotification inline.
 *
 * Colours map to Bootstrap contextual classes: primary, success, warning,
 * danger, info. Icons are Font Awesome class names (e.g. "fa-file-export").
 */
class Notifier
{
    /**
     * Low-level send. Accepts a User model or a user id; silently no-ops on a
     * missing user so a notification failure can never break the action that
     * triggered it.
     */
    public static function to(User|int|null $user, string $title, string $message, string $icon = 'fa-bell', string $color = 'primary', ?string $url = null): void
    {
        $user = $user instanceof User ? $user : ($user ? User::find($user) : null);

        if (! $user) {
            return;
        }

        $user->notify(new GeneralNotification($title, $message, $icon, $color, $url));
    }

    public static function exportReady(User|int|null $user, string $name, ?string $url = null): void
    {
        self::to($user, 'Export ready', "Your export \"{$name}\" has finished and is ready to download.", 'fa-file-export', 'success', $url);
    }

    public static function exportFailed(User|int|null $user, string $name): void
    {
        self::to($user, 'Export failed', "Your export \"{$name}\" could not be generated. Open the Reports page to retry.", 'fa-triangle-exclamation', 'danger', route('reports.index'));
    }

    public static function surveyCompleted(User|int|null $user, string $supplierName, ?string $url = null): void
    {
        self::to($user, 'Supplier survey completed', "{$supplierName} submitted their survey. Responses have been staged as draft Scope 3 records.", 'fa-clipboard-check', 'success', $url);
    }

    public static function importCompleted(User|int|null $user, int $imported, ?string $url = null): void
    {
        self::to($user, 'Import completed', "{$imported} emission record(s) were imported successfully.", 'fa-file-import', 'success', $url);
    }

    public static function billProcessed(User|int|null $user, string $summary, ?string $url = null): void
    {
        self::to($user, 'Utility bill processed', $summary, 'fa-receipt', 'info', $url);
    }

    /**
     * Raise a detected emissions anomaly. Severity ('danger'|'warning'|'info')
     * maps straight to the notification colour so critical spikes stand out.
     */
    public static function anomaly(User|int|null $user, string $title, string $message, string $severity = 'warning', string $icon = 'fa-triangle-exclamation', ?string $url = null): void
    {
        $color = in_array($severity, ['danger', 'warning', 'info'], true) ? $severity : 'warning';
        self::to($user, $title, $message, $icon, $color, $url);
    }
}
