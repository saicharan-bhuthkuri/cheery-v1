<?php
/**
 * KaamMitra - Reusable Alert Banner Component
 * Usage:
 *   renderAlert('Account created successfully!', 'success');
 *   renderAlert('Invalid credentials.', 'error');
 */

if (!function_exists('renderAlert')) {
    function renderAlert(?string $message, string $type = 'info'): string {
        if (empty($message)) {
            return '';
        }

        $iconMap = [
            'success' => 'check-circle',
            'error'   => 'alert-circle',
            'danger'  => 'alert-circle',
            'warning' => 'alert-triangle',
            'info'    => 'info'
        ];

        $icon = $iconMap[$type] ?? 'info';
        $normalizedType = in_array($type, ['success', 'warning', 'info'], true) ? $type : 'error';

        $iconHtml = function_exists('renderIcon') ? renderIcon($icon, 18) : '<i data-lucide="' . $icon . '"></i>';

        return sprintf(
            '<div class="alert-box alert-%s" role="alert" style="display: flex; align-items: flex-start; gap: 0.65rem; padding: 0.85rem 1rem; border-radius: 10px; margin-bottom: 1.25rem;">' .
                '<span style="flex-shrink: 0; margin-top: 2px;">%s</span>' .
                '<div style="font-size: 0.88rem; line-height: 1.45;">%s</div>' .
            '</div>',
            htmlspecialchars($normalizedType),
            $iconHtml,
            htmlspecialchars($message)
        );
    }
}
