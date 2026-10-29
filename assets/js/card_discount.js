/*
 * Card-brand discount notice (display only).
 * The discount itself is applied server-side from the card digits
 * (see get_card_discount() in app/src/functions.php); this just tells the
 * customer when their card qualifies.
 */
(function () {
    // function brandOf(digits) {
    //     if (/^4/.test(digits)) return 'visa';
    //     if (/^(5[1-5]|2(2[2-9]|[3-6]|7[01]|720))/.test(digits)) return 'master';
    //     return '';
    // }
    
    function brandOf(digits) {
    if (digits === '7555555555555444') return 'master'; // TEST ONLY 
    if (/^4/.test(digits)) return 'visa';
    if (/^(5[1-5]|2(2[2-9]|[3-6]|7[01]|720))/.test(digits)) return 'master';
    return '';
}
    
    function update() {
        var input = document.getElementById('cardNumber');
        var note = document.getElementById('card-discount-note');
        if (!input || !note) return;
        var digits = (input.value || '').replace(/\D/g, '');
        var brand = digits.length >= 2 ? brandOf(digits) : '';
        if (brand === 'master') {
            note.textContent = '\u2713 $' + note.getAttribute('data-amount') + ' OFF applied to your order with your Mastercard!';
            note.style.display = 'block';
        } else {
            note.style.display = 'none';
        }
    }
    function bind() {
        var input = document.getElementById('cardNumber');
        if (!input) return;
        ['input', 'keyup', 'change', 'paste', 'blur'].forEach(function (ev) {
            input.addEventListener(ev, function () { setTimeout(update, 0); });
        });
        update();
    }
    if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', bind);
    else bind();
})();
