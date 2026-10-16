/**
 * KaamMitra - Frequently Asked Questions Accordion Controller
 */

(function() {
    'use strict';

    document.addEventListener('DOMContentLoaded', function() {
        initFaqAccordion();
    });

    function initFaqAccordion() {
        var faqCards = document.querySelectorAll('.faq-card');
        if (!faqCards.length) return;

        faqCards.forEach(function(card) {
            var trigger = card.querySelector('.faq-trigger');
            if (!trigger) return;

            trigger.addEventListener('click', function() {
                var isCurrentlyActive = card.classList.contains('active');

                faqCards.forEach(function(otherCard) {
                    if (otherCard !== card) otherCard.classList.remove('active');
                });

                if (isCurrentlyActive) {
                    card.classList.remove('active');
                } else {
                    card.classList.add('active');
                }
            });
        });
    }
})();
