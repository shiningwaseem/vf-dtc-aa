IRON STAMINA DTC PACKAGE

Root checkout:
  /me-dtc/

Test link pattern:
  https://getironstamina.com/me-dtc/?AFFID=3E4A99C1&c1=test1&c2=test2&c3=IRON-DTC-TEST001

What was changed:
- Final bb-v1 payment.php design is now the DTC index.php.
- The checkout submits directly to app/src/create-combined.php.
- No prospect/shipping step is required before checkout.
- Campaign/product mappings are the same as bb-v1:
    YP normal: 25 / 99 / 101 / 103 / upsell 105
    YP scrub: 31 / 109 / 111 / 113 / upsell 115
    MBC mappings are preserved from bb-v1.
- Package totals: 5B $198.80, 3B $149.91, 2B $149.91.
- Testo upsell: $79.89 total / $39.95 per bottle.
- Folder path auto-detects from the server, so renaming the folder still updates config paths.

Important:
- The package preserves the Konnektive API credentials from the working Blood DTC copy used to build it.
- Test orders should use a fresh private/incognito session for each new order because the session prevents duplicate initial orders.
