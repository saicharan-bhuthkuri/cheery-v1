<?php
/**
 * KaamMitra - Shared Authentication Footer Component
 * Clean, focused footer for authentication workflows with copyright, helpline, and legal links.
 */

$base = $base ?? '../../';
?>
<footer class="auth-page-footer">
    <div class="auth-footer-container">
        <div class="auth-footer-info">
            <span class="auth-footer-copy">&copy; <?= date('Y'); ?> KaamMitra Technologies Private Limited. All rights reserved.</span>
            <span class="auth-footer-sep">&bull;</span>
            <span class="auth-footer-phone">
                <i data-lucide="phone"></i>
                <span>24/7 Helpline: <?= defined('APP_PHONE_DISPLAY') ? APP_PHONE_DISPLAY : '1800-KAAM-MITRA'; ?></span>
            </span>
        </div>
        <div class="auth-footer-links">
            <a href="<?= $base; ?>index.php">Return to Home</a>
            <a href="javascript:void(0)">Terms and Conditions</a>
            <a href="javascript:void(0)">Privacy Policy</a>
            <a href="javascript:void(0)">Trust &amp; Safety</a>
        </div>
    </div>
</footer>
