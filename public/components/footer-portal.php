<?php
/**
 * KaamMitra - Reusable Compact Portal Footer Component
 * Parameters: $portalName (string, e.g. 'Customer Portal', 'Partner Operations', 'Administration Console')
 */

$portalName = $portalName ?? 'KaamMitra Operations Console';
?>
<footer style="padding: 1.5rem; text-align: center; font-size: 0.8rem; color: #64748B; border-top: 1px solid rgba(0, 0, 0, 0.05); margin-top: auto;">
    <div style="max-width: 1200px; margin: 0 auto; display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 0.75rem;">
        <div>
            &copy; <?= date('Y'); ?> <?= defined('APP_NAME') ? APP_NAME : 'KaamMitra'; ?> Technologies Private Limited &bull; <?= htmlspecialchars($portalName); ?>
        </div>
        <div style="display: flex; align-items: center; gap: 1rem;">
            <span style="display: inline-flex; align-items: center; gap: 0.3rem;">
                <i data-lucide="shield" style="width: 13px; height: 13px; color: #10B981;"></i>
                <span>256-Bit TLS Secured</span>
            </span>
            <span style="color: #CBD5E1;">&bull;</span>
            <a href="tel:<?= defined('APP_PHONE') ? APP_PHONE : '1800-522-6648'; ?>" style="color: inherit; text-decoration: none;">
                Helpdesk: <?= defined('APP_PHONE_DISPLAY') ? APP_PHONE_DISPLAY : '1800-KAAM-MITRA'; ?>
            </a>
        </div>
    </div>
</footer>
