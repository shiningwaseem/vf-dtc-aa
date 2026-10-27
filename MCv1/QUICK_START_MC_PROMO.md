# Mastercard Promo — Quick Start & Replication Cheatsheet

Use this file when replicating the Mastercard Promo into a **new offer or funnel repository** (both **Main Offers** and **DTC Offers**).

---

## 1. Offer Type Identification (CRITICAL Step 0)

Before modifying code, check what type of funnel you are working in:

| Funnel Type | How to Identify | Checkout Page(s) | Order Backend Processor |
| :--- | :--- | :--- | :--- |
| **Main Offer (Multi-Step)** | User navigates: Landing (`index.php`) ➔ VSL/Presell ➔ Shipping (`shipping.php` / `qualify.php`) ➔ Payment (`payment.php`). Form submits to `create_order.php`. | `payment.php`<br>`mobile/payment.php` | `app/src/create_order.php` |
| **DTC Offer (1-Step Combined)** | `index.php` is the direct checkout lander showing bottle packages + shipping inputs + CC fields in 1 step. Form submits to `create-combined.php`. | `index.php`<br>`payment.php`<br>`mobile/payment.php` | `app/src/create-combined.php` (Desktop)<br>`app/src/create_order.php` (Mobile) |

> [!IMPORTANT]
> **In DTC Offers, Desktop orders submit to `app/src/create-combined.php`!**
> If you only edit `create_order.php`, desktop orders will NOT receive the Mastercard Promo Product ID in Konnektive. You **MUST** update `app/src/create-combined.php` and `index.php`.

---

## 2. File Manifest at a Glance

### 📁 The 5 Drop-In Files to COPY (Zero Changes Needed)
Copy these 5 files directly from this repository into the new offer repository. They are 100% self-contained and modular:

| Source File | Destination File in New Repo | Description |
| :--- | :--- | :--- |
| `app/src/mc_banner.php` | `app/src/mc_banner.php` | Modular yellow checkout banner & green promo badge partial |
| `app/src/mc_modal.php` | `app/src/mc_modal.php` | Modular Visa upgrade popup modal (reads from config) |
| `app/src/detect_card.php` | `app/src/detect_card.php` | Ajax endpoint for card bin detection & promo session locking |
| `assets/css/mc_promo.css` | `assets/css/mc_promo.css` | Complete stylesheet for banner, modal, dropdown & animations |
| `assets/js/mc_promo.js` | `assets/js/mc_promo.js` | Universal JS controller for card detection, modal, & package upgrades |

---

### ✏️ Files to EDIT by Offer Type

| File to Edit | Main Offer | DTC Offer | What to Add / Change |
| :--- | :---: | :---: | :--- |
| **1. `app/configs/config.php`** | ✅ | ✅ | Add `six_bottle_product_id` to affiliates, add `mc_promo` block, and configure `$productSubFolder` path. |
| **2. `app/src/functions.php`** | ✅ | ✅ | Append `is_test_card_match()` and `is_mastercard()` helper functions. |
| **3. `app/src/fraud.php`** | ✅ | ✅ | Update `luhn_check()` so whitelisted test cards bypass Luhn validation. |
| **4. `app/src/create_order.php`** | ✅ | ✅ (Mobile) | Update product ID selection for Mastercard promo (used by multi-step & mobile checkout). |
| **5. `app/src/create-combined.php`** | ❌ | ✅ (Desktop) | Update product ID selection for Mastercard promo (used by DTC 1-step checkout). |
| **6. `payment.php`** | ✅ | ✅ | Link CSS, add 6th bottle/price spans, insert banner, add card dropdown, add modal & JS controller. |
| **7. `index.php`** | ❌ (Presell) | ✅ (Checkout) | Exact same 6 integration steps as `payment.php` when `index.php` is the DTC checkout lander. |
| **8. `mobile/payment.php`** | ✅ | ✅ | Mobile checkout integration (CSS, banner, dropdown, modal, JS controller). |
| **9. `mobile/choose.php`** | ✅ | ✅ | Mobile package selection (CSS, banner, 6th bottle markup). |

---

## 3. Bundle Cheatsheet: 5B vs. 6B Plan Configurations

Both 5-Bottle and 6-Bottle funnels use the **exact same files and directory structure**. 

When creating or modifying a 6-Bottle plan:
- Instead of a 3-bottle product ID, you add the 4-bottle product ID.
- For the top plan, you add the 6-bottle product ID (in place of 5B).
- For the Mastercard promotion, you add the promo product ID (+1 Free Bottle / 6B promo).
- **The variable names can stay the same** (`three_bottle_product_id`, `five_bottle_product_id`, `six_bottle_product_id`) OR you can use descriptive variable names (`four_bottle_product_id`, `six_bottle_product_id`, `promo_product_id`). The backend is coded to support both seamlessly!

---

### Variable Mapping Conventions (Both Styles Fully Supported)

| Tier / Package | 5-Bottle Plan (`promo_package = 5`) | 6-Bottle Plan: Variables Kept Same | 6-Bottle Plan: Descriptive Variables |
| :--- | :--- | :--- | :--- |
| **Top Tier (Standard)** | `five_bottle_product_id` (e.g. `103`) | `five_bottle_product_id` holds 6B ID (e.g. `4047`) | `six_bottle_product_id` (e.g. `4047`) |
| **Top Tier (Mastercard)** | `six_bottle_product_id` holds promo ID (`195`) | `six_bottle_product_id` holds promo ID (`4049`) | `promo_product_id` or `seven_bottle_product_id` (`4049`) |
| **Middle Tier** | `three_bottle_product_id` (e.g. `101`) | `three_bottle_product_id` holds 4B ID (e.g. `4045`) | `four_bottle_product_id` (e.g. `4045`) |
| **Lower Tier** | `two_bottle_product_id` (e.g. `99`) | `two_bottle_product_id` holds 2B ID (e.g. `4043`) | `two_bottle_product_id` or `one_bottle_product_id` (`4043`) |
| **Backend `promo_package`** | `5` | `6` | `6` |
| **HTML `data-pkg` attributes**| Top: `5`, Middle: `3`, Lower: `1` | Top: `6`, Middle: `4`, Lower: `2` | Top: `6`, Middle: `4`, Lower: `2` |

---

### How the Order Processors (`create_order.php` & `create-combined.php`) Handle This

The order processing engines automatically detect:
```php
$promoPkg = (int) ($configs->mc_promo->promo_package ?? 5);

// 1. Promo Product ID resolution (supports promo_product_id, seven_bottle_product_id, or six_bottle_product_id)
$promoProductId = $affiliate->promo_product_id ?? ($affiliate->seven_bottle_product_id ?? null);
if (empty($promoProductId)) {
    $promoProductId = $affiliate->six_bottle_product_id ?? null;
}

// 2. Standard Top Package Product ID resolution
if ($promoPkg === 6 && !empty($affiliate->six_bottle_product_id) && !empty($promoProductId) && $affiliate->six_bottle_product_id != $promoProductId) {
    $productId = (int) $affiliate->six_bottle_product_id;
} else {
    $productId = (int) ($affiliate->five_bottle_product_id ?? ($affiliate->six_bottle_product_id ?? 103));
}

// 3. Middle Tier: checks four_bottle_product_id first, then falls back to three_bottle_product_id
if (in_array($package, [3, 4], true)) {
    $productId = (int) ($affiliate->four_bottle_product_id ?? ($affiliate->three_bottle_product_id ?? 101));
}

// 4. Lower Tier: checks two_bottle_product_id first, then one_bottle_product_id
if (in_array($package, [1, 2], true)) {
    $productId = (int) ($affiliate->two_bottle_product_id ?? ($affiliate->one_bottle_product_id ?? 99));
}
```

> [!TIP]
> You do **not** need to worry about breaking the backend if you reuse `three_bottle_product_id` for your 4B product ID, or `five_bottle_product_id` for your 6B product ID. The backend logic resolves the correct product ID every time!

---

## 4. Card Switching & Fraud Prevention Safeguards

1. **Card Digits are the Single Source of Truth**:
   In `app/src/functions.php`, `create_order.php`, and `create-combined.php`, `is_mastercard()` strictly inspects the raw digits of `fields_cc_number`. If the card number begins with `4` (Visa), `3` (Amex), or `6` (Discover), `is_mastercard()` strictly returns `false`, ensuring Konnektive receives the standard package (`five_bottle_product_id`), **never** the promo product ID (`promo_product_id`)—even if the dropdown says `master`.
2. **Auto-Clear on Manual Dropdown Change**:
   In `assets/js/mc_promo.js`, when a customer manually changes `#card_type_select`, `#cardNumber` is **immediately cleared and focused**. This eliminates any possibility of an old Visa number lingering while Mastercard is selected.
3. **Real-Time Keystroke Detection**:
   Switching cards in real time updates the UI instantly: entering Mastercard (`5...` or `2...`) attaches the promo (+1 Free Bottle, dark green banner) without popup interruption; entering Visa (`4...`) revokes the promo (yellow banner, 6th bottle hidden) without repeat modals.
4. **Konnektive CRM Test Cards Support**:
   Konnektive test cards start with `7` (e.g., `7411111111111111` for Visa, and `7555555555555444` for Mastercard). Both frontend `mc_promo.js` and server `is_mastercard()` detect and strip the `7` prefix to test the real-time flows. Furthermore, `luhn_check` in `fraud.php` automatically bypasses Luhn validation for test cards starting with `7` or `0000`.

---

## 5. The Exact Code to Edit in Each File

### Edit 1: `app/configs/config.php`
1. Set the correct `$productSubFolder` and normalize `$rootPath` and `$mobilePath` to avoid double slashes `//`:
   ```php
   $productSubFolder = 'your-subfolder-path'; // e.g. 'is-dtc-em/MCv1'
   if (!empty($_SERVER['DOCUMENT_ROOT'])) {
       $documentRoot = realpath($_SERVER['DOCUMENT_ROOT']);
       $projectRoot = realpath(dirname(__DIR__, 2));
       if ($documentRoot && $projectRoot && strpos($projectRoot, $documentRoot) === 0) {
           $productSubFolder = trim(str_replace('\\', '/', substr($projectRoot, strlen($documentRoot))), '/');
       }
   }
   $rootPath = ($productSubFolder !== '') ? "/$productSubFolder/" : '/';
   $mobilePath = ($productSubFolder !== '') ? "/$productSubFolder/mobile/" : '/mobile/';
   ```
2. Add the Mastercard promo product IDs under `'affiliates'`:
   > [!IMPORTANT]
   > **DO NOT CREATE A NEW AFFILIATE ENTRY OR YP ALIAS IN `config.php`!**
   > Add `six_bottle_product_id` and `six_bottle_product_id_sc` directly into the **existing** primary affiliate configuration block (e.g. `'A7652664'` or whatever default affiliate is configured). Do NOT duplicate the array or create a new key like `'3E4A99C1'`.

   ```php
   'six_bottle_product_id'    => 195, // Mastercard promo package product ID
   'six_bottle_product_id_sc' => 197, // Scrub Mastercard promo package product ID
   ```
3. Append `'mc_promo'` configuration block:
   ```php
   'mc_promo' => (object) array(
       'enabled'       => true,
       'promo_package' => 5,        // Set to 5 for 2B/3B/5B funnels, or 6 for 2B/4B/6B funnels
       'promo_price'   => '198.80', // Same price as regular top package

       // Checkout Banner
       'banner_logo_img'     => 'assets/images/mastercard.png',
       'banner_pill'         => 'LIMITED OFFER:',
       'banner_headline'     => 'Receive an Additional bottle for FREE when paying with Mastercard!',
       'banner_applied_text' => '🎉 LIMITED OFFER APPLIED: +1 Free Bottle Added with Mastercard!',

       // Modal Defaults
       'modal_logo_img'      => 'assets/images/mastercard.png',
       'modal_security_text' => '🔒 Safe &amp; Secure 256-Bit Encrypted Checkout',
       'modal_top_title'     => 'SPECIAL MASTERCARD UPGRADE <br>1 FREE BOTTLE!',
       'modal_headline'      => 'Upgrade your Package to <strong>5-Bottle Package & get 1 FREE Bottle</strong> when you pay with Mastercard.',
       'modal_btn_yes'       => '✓ Yes, Add +1 FREE Bottle with my Mastercard',
       'modal_btn_no'        => 'No Thanks, continue with my Visa card',

       // Per-Package Custom Copy:
       'packages' => array(
           5 => (object) array(
               'top_title' => 'SPECIAL MASTERCARD UPGRADE <br>1 FREE BOTTLE!',
               'headline'  => 'Get 6 Bottles for Only <span style="color:#16a34a; font-weight:800;">$33.13 / bottle</span>',
               'subtext'   => 'Pay with Mastercard to get <strong>+1 EXTRA FREE Bottle</strong> added to your 5-Bottle package (Total 6 Bottles) at no extra charge!',
               'btn_yes'   => '✓ Yes, Add +1 FREE Bottle with my Mastercard',
               'btn_no'    => 'No Thanks, continue with my Visa card'
           ),
           3 => (object) array(
               'top_title' => 'SPECIAL MASTERCARD UPGRADE <br>1 FREE BOTTLE!',
               'headline'  => 'Get 6 Bottles for Only <span style="color:#16a34a; font-weight:800;">$33.13 / bottle</span>',
               'subtext'   => 'Upgrade your 3-Bottle Package to <strong>5-Bottle Package & get 1 FREE Bottle</strong> when you pay with Mastercard.',
               'btn_yes'   => '✓ Yes, Upgrade to 6 Bottles with Mastercard',
               'btn_no'    => 'No Thanks, keep my 3-Bottle Package with Visa'
           ),
           1 => (object) array(
               'top_title' => 'SPECIAL MASTERCARD UPGRADE <br>1 FREE BOTTLE!',
               'headline'  => 'Get 6 Bottles for Only <span style="color:#16a34a; font-weight:800;">$33.13 / bottle</span>',
               'subtext'   => 'Upgrade your 2-Bottle Package to <strong>5-Bottle Package & get 1 FREE Bottle</strong> when you pay with Mastercard.',
               'btn_yes'   => '✓ Yes, Upgrade to 6 Bottles with Mastercard',
               'btn_no'    => 'No Thanks, keep my 2-Bottle Package with Visa'
           )
       ),

       // Whitelisted Test Cards for Checkout & QA Testing
       'test_cards' => (object) array(
           'generic' => array('0000', '0000000000000000'),
           'visa'    => array('7411111111111111'),
           'master'  => array('7555555555555444')
       )
   )
   ```

---

### Edit 2: `app/src/functions.php`
Append helper functions to the bottom:
```php
function is_test_card_match($cardNumber, $cardList) {
	if (empty($cardNumber) || empty($cardList)) return false;
	$clean = preg_replace('/\D/', '', (string)$cardNumber);
	foreach ((array)$cardList as $item) {
		$cleanItem = preg_replace('/\D/', '', (string)$item);
		if (!empty($cleanItem) && ($clean === $cleanItem || strpos($clean, $cleanItem) === 0)) {
			return true;
		}
	}
	return false;
}

function is_mastercard($cardNumber = '', $cardType = '') {
	global $configs;
	$cleanCard = preg_replace('/\D/', '', $cardNumber);

	if (!empty($cleanCard)) {
		$testCards = $configs->mc_promo->test_cards ?? null;
		if ($testCards) {
			if (!empty($testCards->master) && is_test_card_match($cleanCard, $testCards->master)) {
				return true;
			}
			if (!empty($testCards->visa) && is_test_card_match($cleanCard, $testCards->visa)) {
				return false;
			}
			if (!empty($testCards->generic) && is_test_card_match($cleanCard, $testCards->generic)) {
				$type = strtolower(trim((string)$cardType));
				return in_array($type, array('master', 'mastercard', 'mc'));
			}
		}

		$effectiveCard = (strpos($cleanCard, '7') === 0 && strlen($cleanCard) > 1) ? substr($cleanCard, 1) : $cleanCard;

		if (strpos($cleanCard, '0000') !== 0) {
			return (
				preg_match('/^5[1-5]/', $effectiveCard) ||
				preg_match('/^2(22[1-9]|2[3-9]|[3-6]\d|7[0-1]\d|720)/', $effectiveCard)
			) ? true : false;
		}
	}

	$type = strtolower(trim((string)$cardType));
	return in_array($type, array('master', 'mastercard', 'mc'));
}
```

---

### Edit 3: `app/src/fraud.php`
Update `luhn_check()` to bypass test cards:
```php
function luhn_check($number) {
  global $configs;

  $number = preg_replace('/\D/', '', $number);

  $testCards = $configs->mc_promo->test_cards ?? null;
  if ($testCards) {
    if (
      (!empty($testCards->generic) && is_test_card_match($number, $testCards->generic)) ||
      (!empty($testCards->visa) && is_test_card_match($number, $testCards->visa)) ||
      (!empty($testCards->master) && is_test_card_match($number, $testCards->master))
    ) {
      return true;
    }
  }

  if (strpos($number, '7') === 0 || strpos($number, '0000') === 0) {
    return true;
  }

  $number_length = strlen($number);
  $parity = $number_length % 2;
  $total = 0;
  for ($i = 0; $i < $number_length; $i++) {
    $digit = $number[$i];
    if ($i % 2 == $parity) {
      $digit *= 2;
      if ($digit > 9) {
        $digit -= 9;
      }
    }
    $total += $digit;
  }

  return ($total % 10 == 0);
}
```

---

### Edit 4A: `app/src/create_order.php` (Main Offer & Mobile)
Replace the package selection block in `app/src/create_order.php`:
```php
if (isset($_SESSION["affid"]) && isset($configs->affiliates[$_SESSION["affid"]])) {
	$affiliate = $configs->affiliates[$_SESSION["affid"]];
	$campaign_id = $affiliate->campaign_id;
	$isMastercard = is_mastercard($_POST["fields_cc_number"] ?? '', $_POST["fields_cc_type"] ?? '');

	$promoPkg = $configs->mc_promo->promo_package ?? 5;

	// Default to top package (5-Bottle or 6-Bottle)
	$product_id = $affiliate->five_bottle_product_id ?? ($affiliate->six_bottle_product_id ?? null);
	$_SESSION["package"] = $promoPkg;

	// Mastercard Promo Product ID
	$promoProductId = $affiliate->promo_product_id ?? ($affiliate->six_bottle_product_id ?? null);
	if ($promoPkg == 6 && !empty($affiliate->seven_bottle_product_id)) {
		$promoProductId = $affiliate->seven_bottle_product_id;
	}
	if ($isMastercard && !empty($promoProductId)) {
		$product_id = $promoProductId;
	}

	// Middle Package (3-Bottle or 4-Bottle)
	if (isset($_POST["package"]) && in_array((int)$_POST["package"], array(3, 4))) {
		$product_id = $affiliate->four_bottle_product_id ?? ($affiliate->three_bottle_product_id ?? null);
		$_SESSION["package"] = (int)$_POST["package"];
	}

	// Lower Package (1/2-Bottle or 2-Bottle)
	if (isset($_POST["package"]) && in_array((int)$_POST["package"], array(1, 2))) {
		$product_id = $affiliate->two_bottle_product_id ?? ($affiliate->one_bottle_product_id ?? null);
		$_SESSION["package"] = (int)$_POST["package"];
	}
} else {
	http_response_code(400);
	exit;
}

$scrub = $configs->affiliates[$_SESSION["affid"]]->scrub;
if (!isset($_SESSION["scrub"])) {
	$_SESSION["scrub"] = false;
}

if ($scrub > rand(0, 100)) {
	$_SESSION["scrub"] = true;
	$campaign_id = $affiliate->campaign_id_sc;

	// Top Package Scrub
	$product_id = $affiliate->five_bottle_product_id_sc ?? ($affiliate->six_bottle_product_id_sc ?? null);
	$_SESSION["package"] = $promoPkg;

	$promoScrubId = $affiliate->promo_product_id_sc ?? ($affiliate->six_bottle_product_id_sc ?? null);
	if ($promoPkg == 6 && !empty($affiliate->seven_bottle_product_id_sc)) {
		$promoScrubId = $affiliate->seven_bottle_product_id_sc;
	}
	if ($isMastercard && !empty($promoScrubId)) {
		$product_id = $promoScrubId;
	}

	if (isset($_POST["package"])) {
		$pkgVal = (int)$_POST["package"];
		if (in_array($pkgVal, array(1, 2))) {
			$product_id = $affiliate->two_bottle_product_id_sc ?? ($affiliate->one_bottle_product_id_sc ?? null);
			$_SESSION["package"] = $pkgVal;
		}
		if (in_array($pkgVal, array(3, 4))) {
			$product_id = $affiliate->four_bottle_product_id_sc ?? ($affiliate->three_bottle_product_id_sc ?? null);
			$_SESSION["package"] = $pkgVal;
		}
	}
}
```

---

### Edit 4B: `app/src/create-combined.php` (CRITICAL for DTC Offers)
If the offer repository has `app/src/create-combined.php`, update the product resolution block (around lines 85–135):
```php
$affiliate = $configs->affiliates[$affid];
$cardNumber = preg_replace('/\D/', '', dtc_post('fields_cc_number'));
$cardType = dtc_post('fields_cc_type') ?: dtc_post('card_type_select');
$isMastercard = is_mastercard($cardNumber, $cardType);

$promoPkg = $configs->mc_promo->promo_package ?? 5;
$package = (int) dtc_post('package', '5');
if (!in_array($package, [1, 3, 5], true)) {
    $package = 5;
}
$_SESSION['package'] = $package;

// Normal campaign/product mapping
$campaignId = (int) $affiliate->campaign_id;
$productId = (int) ($affiliate->five_bottle_product_id ?? ($affiliate->six_bottle_product_id ?? 103));

// Mastercard Promo Product ID for top package (upgrades to 6B)
$promoProductId = $affiliate->promo_product_id ?? ($affiliate->six_bottle_product_id ?? null);
if ($package === $promoPkg && $isMastercard && !empty($promoProductId)) {
    $productId = (int) $promoProductId;
}

if ($package === 3) {
    $productId = (int) ($affiliate->three_bottle_product_id ?? 101);
} elseif ($package === 1) {
    $productId = (int) ($affiliate->two_bottle_product_id ?? 99);
}

// Scrub setup
$scrub = isset($affiliate->scrub) ? (int) $affiliate->scrub : 0;
if (isset($_SESSION['c3']) && in_array((string) $_SESSION['c3'], ['158', '240', '241'], true)) {
    $scrub = 7;
}

$isScrub = $scrub > 0 && $scrub > random_int(0, 100);
$_SESSION['scrub'] = $isScrub;

if ($isScrub) {
    $campaignId = (int) $affiliate->campaign_id_sc;
    $productId = (int) ($affiliate->five_bottle_product_id_sc ?? ($affiliate->six_bottle_product_id_sc ?? 113));

    $promoScrubId = $affiliate->promo_product_id_sc ?? ($affiliate->six_bottle_product_id_sc ?? null);
    if ($package === $promoPkg && $isMastercard && !empty($promoScrubId)) {
        $productId = (int) $promoScrubId;
    }

    if ($package === 3) {
        $productId = (int) ($affiliate->three_bottle_product_id_sc ?? 111);
    } elseif ($package === 1) {
        $productId = (int) ($affiliate->two_bottle_product_id_sc ?? 109);
    }
}
```

---

### Edit 5: `payment.php` & `index.php` (Desktop Checkout)

Apply these **6 steps** to `payment.php` (and also to `index.php` if `index.php` is the DTC checkout lander):

1. **In `<head>`**:
   ```html
   <link href="assets/css/mc_promo.css" type="text/css" rel="stylesheet">
   ```
2. **In Top Package card (`.product1` / `data-pkg="5"`)**:
   Add the 6th bottle image and dynamic price spans:
   ```html
   <!-- 6th bottle image positioned with CSS -->
   <img class="product1-image6" id="product1-image6" alt="" src="assets/images/product.png">
   
   <!-- Spans for dynamic price / bottle updates -->
   <span id="p1-header-title">BUY 3 GET 2 FREE</span>
   <span id="p1-pack-title">5 Bottle Performance Pack</span>
   $<span id="p1-per-price">39.76</span>
   $<span id="p1-save-price">151.15</span>
   $<span id="p1-retail-price">349.95</span>
   ```
3. **Above `<span class="accept-text">We Accept:</span>`**:
   ```php
   <?php include(__DIR__ . '/app/src/mc_banner.php'); ?>
   ```
4. **Above `#cardNumber`**:
   ```html
   <div class="margin-bottom-5">
       <div class="phone-12 columns">
           <label id="card_type_label" for="card_type_select">Card Type:</label>
       </div>
   </div>
   <div class="phone-12 columns form-holder">
       <select name="card_type_select" id="card_type_select" class="form-control required">
           <option value="">Select Card Type</option>
           <option value="visa">Visa Card</option>
           <option value="master">Mastercard</option>
       </select>
   </div>
   ```
5. **In `submit_form()` validation**:
   ```javascript
   if ($("#cardNumber").val() === "") {
       errors.push("Please enter a credit card number.");
   } else {
       var cleanNumForSubmit = ($("#cardNumber").val() || '').replace(/\D/g, '');
       var detected = GetCardType(cleanNumForSubmit);
       if (detected === 'visa' || detected === 'master') {
           if (typeof MCPromo !== 'undefined' && typeof MCPromo.setCardType === 'function') {
               MCPromo.setCardType(detected, false);
           }
       } else if ($('#card_type_select').val()) {
           if (typeof MCPromo !== 'undefined' && typeof MCPromo.setCardType === 'function') {
               MCPromo.setCardType($('#card_type_select').val(), true);
           }
       }
   }
   ```
6. **Before `</body>`**:
   ```php
       <?php include(__DIR__ . '/app/src/mc_modal.php'); ?>
       <script type="text/javascript" src="assets/js/mc_promo.js"></script>
   </body>
   </html>
   ```

---

### Edit 6: `mobile/payment.php` (Mobile Checkout)

Apply these essential mobile configurations to `mobile/payment.php`:

1. **In `<head>`**, link the promotional stylesheet using normalized paths:
   ```html
   <link href="<?php echo rtrim($configs->root_path, '/') . "/assets/css/mc_promo.css"; ?>" rel="stylesheet">
   ```
2. **Directly above `<div class="cards-list">`**, include the banner partial:
   ```php
   <?php include(__DIR__ . '/../app/src/mc_banner.php'); ?>
   ```
3. **Inside `.checkout-form-wrapper`, directly above `#cardNumber`**, insert the Card Type selector dropdown:
   ```html
   <div class="form-holder" id="card_type_holder">
       <label for="card_type_select">Card Type:</label>
       <select name="card_type_select" id="card_type_select" class="form-control required">
           <option value="">Select Card Type</option>
           <option value="visa">Visa Card</option>
           <option value="master">Mastercard</option>
       </select>
   </div>
   ```
4. **Ensure `#cardNumber` input has `maxlength="19"`**:
   ```html
   <input type="tel" name="fields_cc_number" id="cardNumber" data-threeds="pan" class="form-control required masked" placeholder="Card Number" maxlength="19" autocomplete="off" />
   ```
5. **Ensure the checkout submit button is visible by default (NEVER `display:none;` inline)**:
   ```html
   <div id="rushtop"><input alt="Submit" border="0" class="pulsebutton cformbtn-default btn_pulse" id="cform-submit-btn" name="" src="<?php echo rtrim($configs->mobile_path, '/'); ?>/assets/images/RushButton.jpg" style="width: 100%; outline:none; display:block;" type="image" value="submit"></div>
   ```
   > [!IMPORTANT]
   > Do NOT leave `style="...display:none;"` on `#cform-submit-btn`. If `display:none;` is present inline, any delay or error in masking scripts will cause the checkout button to vanish completely. Always use `display:block;` inline, and use `rtrim($configs->root_path, '/')` and `rtrim($configs->mobile_path, '/')` to prevent scheme-relative `//assets/...` loading errors.

6. **Update the mobile `#cardNumber` input listener for test card support**:
   Mobile checkouts often bind keyup validation that disables the button (`.btn_pulse`) and adds `.error-card` (red border). Test cards (e.g. `0000`, `7411...`, `7555...`) have `null` card type in older plugins, which causes `result.card_type.name` to throw an unhandled `TypeError` that leaves the button permanently disabled with a red border.
   Update the listener on `#cardNumber` to:
   - Handle `'input keyup change paste'` events.
   - Whitelist test cards (`0000`, `7411...`, `7555...`, prefix `7`): bypass `validateCreditCard()`, clear `.error-card`, enable `.btn_pulse`, and sync detected brand (`'master'` vs `'visa'`) with `MCPromo.setCardType()`.
   - Use safe property access `(result && result.card_type && result.card_type.name)` for live cards.
   - In `LuhnAlgorithm()`, ensure test cards bypass Luhn checksum and return `true`.

7. **Right before the closing `</body>` tag**, include the modal partial and JS controller:
   ```php
       <?php include(__DIR__ . '/../app/src/mc_modal.php'); ?>
       <script src="<?php echo rtrim($configs->root_path, '/'); ?>/assets/js/mc_promo.js"></script>
   </body>
   </html>
   ```

---

## 6. The Master AI Agent Prompt for Antigravity

Copy this exact prompt when instructing Antigravity to replicate the Mastercard Promo in a new repository. Fill in the top **`[OFFER REPLICATION CONFIGURATION]`** block with your specific offer details:

```text
================================================================================
[OFFER REPLICATION CONFIGURATION — EDIT THIS BLOCK BEFORE GIVING TO AGENT]
================================================================================
- OFFER_TYPE: [DTC or MAIN]
  * Set to 'DTC' if index.php is the 1-step checkout lander with credit card & shipping inputs.
  * Set to 'MAIN' if this is a multi-step VSL/presell funnel where payment.php is the checkout.

- BUNDLE_PLAN: [5B or 6B]
  * Set to '5B' for 2B / 3B / 5-Bottle funnels (promo_package = 5).
  * Set to '6B' for 2B / 4B / 6-Bottle funnels (promo_package = 6).
  * NOTE: Both 5B and 6B plans share the EXACT same files and code structure.

- SUBFOLDER_PATH: [e.g. 'is-dtc-em/MCv1' or '' if root domain]

- KONNEKTIVE PRODUCT IDS:
  * Top Tier Standard Product ID: [e.g. 103 for 5B, or 4047 for 6B]
  * Top Tier Mastercard Promo Product ID (+1 Free Bottle): [e.g. 195 for 6B promo, or 4049 for 7B promo]
  * Middle Tier Product ID: [e.g. 101 for 3B, or 4045 for 4B]
  * Lower Tier Product ID: [e.g. 99 for 2B, or 4043 for 2B]
  * Top Tier Regular Price: [e.g. 198.80]

- (OPTIONAL) SCRUB PRODUCT IDS (Leave blank if scrub = 0):
  * Top Tier Standard Scrub ID: [e.g. 113]
  * Top Tier Promo Scrub ID: [e.g. 197]
  * Middle Tier Scrub ID: [e.g. 111]
  * Lower Tier Scrub ID: [e.g. 109]
================================================================================

Please replicate the Mastercard +1 Free Bottle Upgrade promotion in this offer repository following MASTERCARD_PROMO_INTEGRATION_GUIDE.md and QUICK_START_MC_PROMO.md, adhering strictly to the [OFFER REPLICATION CONFIGURATION] parameters above:

Step 1: Check Offer Type & Architecture:
- Read OFFER_TYPE and BUNDLE_PLAN from the configuration above.
- If OFFER_TYPE is 'DTC':
  * Desktop orders submit to app/src/create-combined.php, and mobile orders submit to app/src/create_order.php.
  * BOTH index.php and payment.php are checkout pages and must receive the complete frontend integration.
- If OFFER_TYPE is 'MAIN':
  * Orders submit to app/src/create_order.php.
  * payment.php is the checkout page.
- Remember: 5B and 6B plans share the exact same files and directory structure. In 6B plans, the 4B product ID replaces the 3B ID, and the 6B product ID replaces the 5B ID, while the affiliate variable names in config.php can either remain identical or use descriptive names.

Step 2: Deploy the 5 Modular Drop-In Files (Zero Changes Needed):
- app/src/mc_banner.php
- app/src/mc_modal.php
- app/src/detect_card.php
- assets/css/mc_promo.css
- assets/js/mc_promo.js

Step 3: Backend Configuration & Order Processors:
- app/configs/config.php:
  * Set $productSubFolder to the configured SUBFOLDER_PATH and normalize $rootPath and $mobilePath to avoid double slashes.
  * Update the campaign affiliate array with the provided Product IDs (standard, promo, middle, lower, and scrub IDs).
    IMPORTANT: Add product IDs directly into the EXISTING primary affiliate array. DO NOT create a new affiliate entry or alias (e.g. '3E4A99C1').
  * Configure the 'mc_promo' block:
    - Set 'promo_package' to 5 (if 5B) or 6 (if 6B).
    - Set 'promo_price' to the configured Top Tier Regular Price.
    - Set modal/banner copy matching the bundle tier.
    - Include test_cards configuration (generic '0000', Visa '7411', Mastercard '7555').
- app/src/functions.php: Append is_test_card_match() and is_mastercard() helper functions.
- app/src/fraud.php: Update luhn_check() so test cards bypass Luhn validation.
- app/src/create_order.php: Implement Mastercard promo product resolution supporting both 5B and 6B variable styles.
- app/src/create-combined.php (CRITICAL for DTC offers): Implement Mastercard promo product resolution, card digits validation, and dtc_log() audit logging.

Step 4: Frontend Integration:
- Desktop Checkout (payment.php, AND index.php if DTC):
  1. Link assets/css/mc_promo.css in <head>.
  2. Add 6th bottle visual badge, image/markup, and price per bottle/total savings spans on Package 1.
  3. Include app/src/mc_banner.php directly above "We Accept:".
  4. Add the #card_type_select dropdown directly above #cardNumber.
  5. Update submit_form() CC validation to sync detected brand with MCPromo.setCardType().
  6. Include app/src/mc_modal.php and assets/js/mc_promo.js before </body>.
- Mobile Checkout (mobile/payment.php, and mobile/choose.php if present):
  1. Link CSS using rtrim($configs->root_path, '/').
  2. Include banner above cards list.
  3. Add #card_type_select dropdown above #cardNumber and set maxlength="19".
  4. CRITICAL: Ensure #cform-submit-btn has style="...display:block;" inline (NEVER display:none; inline) and uses rtrim($configs->mobile_path, '/') so the submit button never vanishes.
  5. Whitelist test cards (0000, 7411..., 7555...) in #cardNumber listeners so they bypass Luhn/card-type errors and enable the checkout button.
  6. Include modal and JS controller before </body>.

Step 5: Verification & Testing:
- Run php -l on all modified PHP files to ensure zero syntax errors.
- Run a PHP CLI unit test verifying that:
  1. Test card 7555555555555444 on the top package resolves to the Mastercard Promo Product ID.
  2. Test card 0000000000000000 on the top package resolves to the Standard Product ID.
  3. Live Mastercard (BIN 51-55 or 2221-2720) resolves to the Mastercard Promo Product ID.
  4. Live Visa (BIN 4...) resolves to the Standard Product ID.
```
