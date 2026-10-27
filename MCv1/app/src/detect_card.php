<?php
include(__DIR__ . '/../../_global.php');

header('Content-Type: application/json');

$action = $_POST['action'] ?? 'detect';
$rawCard = $_POST['card_number'] ?? '';
$cleanCard = preg_replace('/\D/', '', $rawCard);

if ($action === 'set_mastercard_promo') {
    $_SESSION['mastercard_promo'] = true;
    $_SESSION['card_type_locked'] = 'master';
    echo json_encode([
        'status' => 'success',
        'promo_active' => true,
        'free_bottle' => true,
        'locked_card_type' => 'master',
        'message' => 'Mastercard promo activated! Extra free bottle attached.'
    ]);
    exit();
}

if ($action === 'cancel_promo') {
    $_SESSION['mastercard_promo'] = false;
    unset($_SESSION['card_type_locked']);
    echo json_encode([
        'status' => 'success',
        'promo_active' => false,
        'free_bottle' => false,
        'message' => 'Mastercard promo cancelled.'
    ]);
    exit();
}

// Detection logic
$isMastercard = false;
$isVisa = false;
$cardType = 'unknown';

if (strlen($cleanCard) > 0) {
    // 1. Check whitelisted test cards from config
    $testCards = $configs->mc_promo->test_cards ?? null;
    if ($testCards) {
        if (!empty($testCards->master) && is_test_card_match($cleanCard, $testCards->master)) {
            $isMastercard = true;
            $cardType = 'mastercard';
        } elseif (!empty($testCards->visa) && is_test_card_match($cleanCard, $testCards->visa)) {
            $isVisa = true;
            $cardType = 'visa';
        }
    }

    if (!$isMastercard && !$isVisa) {
        // Support Konnektive test cards starting with '7' (e.g. 74... for Visa, 75... for Mastercard)
        $effectiveCard = (strpos($cleanCard, '7') === 0 && strlen($cleanCard) > 1) ? substr($cleanCard, 1) : $cleanCard;

        if (preg_match('/^4/', $effectiveCard)) {
            $isVisa = true;
            $cardType = 'visa';
        } elseif (
            preg_match('/^5[1-5]/', $effectiveCard) ||
            preg_match('/^2(22[1-9]|2[3-9]|[3-6]\d|7[0-1]\d|720)/', $effectiveCard) ||
            (strlen($effectiveCard) === 1 && ($effectiveCard === '5' || $effectiveCard === '2'))
        ) {
            $isMastercard = true;
            $cardType = 'mastercard';
        } elseif (preg_match('/^3[47]/', $effectiveCard)) {
            $cardType = 'amex';
        } elseif (preg_match('/^(6011|65|64[4-9]|622)/', $effectiveCard)) {
            $cardType = 'discover';
        }
    }
}

if ($isMastercard) {
    $_SESSION['mastercard_promo'] = true;
    $_SESSION['card_type_locked'] = 'master';
} elseif ($isVisa) {
    $_SESSION['mastercard_promo'] = false;
    unset($_SESSION['card_type_locked']);
}

echo json_encode([
    'status' => 'success',
    'card_type' => $cardType,
    'is_mastercard' => $isMastercard,
    'is_visa' => $isVisa,
    'free_bottle' => $isMastercard,
    'promo_active' => !empty($_SESSION['mastercard_promo']),
    'card_type_locked' => $_SESSION['card_type_locked'] ?? null
]);
exit();
