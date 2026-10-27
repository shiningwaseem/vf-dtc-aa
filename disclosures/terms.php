<?php include("../_global.php");

ini_set('display_errors', '1');
ini_set('display_startup_errors', '1');
error_reporting(E_ALL);

?>
<!DOCTYPE html PUBLIC "-//W3C//DTD HTML 4.0 Transitional//EN">
<html>

<head>
    <meta http-equiv="CONTENT-TYPE" content="text/html; charset=UTF-8">
    <title>Terms & Conditions</title>
    <style type="text/css">
    body {
        font-family: Tahoma, Geneva, sans-serif;
        margin: 0;
        padding: 15px;
    }

    p {
        color: #000;
        font-size: 12px;
        line-height: 20px;
        margin: 15px 0 0 0;
    }

    h1 {
        font-size: 22px;
        margin: 0 0 10px 0;
    }

    h4 {
        font-size: 18px;
        margin: 25px 0 0 0;
    }

    li {
        margin: 10px 0;
        font-size: 14px;
        line-height: 20px;
    }
    </style>
</head>

<body>
     <div class="popup-inner">
        <a href="javascript:void(0)" class="close-popup-link"></a>
        <h1>Terms</h1>

        <h4>1. SCOPE &amp; APPLICATION&nbsp;</h4>

        <p>1.1 You expressly agree and accept the Conditions set forth herein unconditionally as a binding contract
            ("the Agreement") enforceable by law. The following are the terms and conditions of participation in the
            <?php echo htmlspecialchars((string) $configs->corp_name, ENT_QUOTES, 'UTF-8'); ?>, Subscription ("Subscription") or any other Product on this Site, all
            such references to
            Subscription or Product combined shall be ("Product"). "Customer", "I", "You" or "Your" refers to you.
            "Site" means this World Wide Website located at the URL or <?php echo htmlspecialchars((string) $configs->corp_name, ENT_QUOTES, 'UTF-8'); ?>, ("We", "Us"
            or "Our") reserves the
            right to amend this Agreement from time to time.
            It is agreed that any such amendment will apply to Customer. <?php echo htmlspecialchars((string) $configs->corp_name, ENT_QUOTES, 'UTF-8'); ?> agrees to
            inform Customer of any
            amendment to the agreement. Should Customer fail to object to any amendment to the Agreement within one
            week, such failure shall serve as an acceptance of the amendment.

        </p>
        <h4>2. PRODUCT AND BILLING</h4>
        <p>
            2.1 All product purchases made from this website are required to be paid in full.

            <br><br>2.1.1. The prices for the products are as follows:
            $<?php echo number_format((float) $configs->bottle_price_5, 2); ?> for the 5 bottle package
            ($<?php echo number_format((float) $configs->bottle_per_5, 2); ?> bottle);
            $<?php echo number_format((float) $configs->bottle_price_3, 2); ?> for the 3 bottle package
            ($<?php echo number_format((float) $configs->bottle_per_3, 2); ?> bottle);
            $<?php echo number_format((float) $configs->bottle_price_1, 2); ?> for the 2 bottle package
            ($<?php echo number_format((float) $configs->bottle_per_1, 2); ?> bottle).

            <br><br>2.2.1 You authorize us to initiate a charge to your credit card as indicated upon your purchase.

            <br><br>2.3.1 (Cont) This product is sold as a program with the price and product being charged and
            delivered on the initial sale, and then again monthly (every 30 days after the initial purchase). Depending
            on the selected number of products upon initial purchase, the price per installment will vary. For 2
            bottles, each installment is $<?php echo number_format((float) $configs->bottle_price_1, 2); ?>. For 3 bottles,
            each installment is $<?php echo number_format((float) $configs->bottle_price_3, 2); ?> and for 5 bottles, each
            installment is $<?php echo number_format((float) $configs->bottle_price_5, 2); ?>. The program begins when the
            customer signs up, and continues indefinitely until the customer shall choose to cancel. The secondary
            product is optionally sold as a single bottle add on purchase with the initial order only.

            <br><br>2.3.2 Your product will ship within 24 hours and will arrive within
            5-7 business days. If your product does not arrive within the allotted amount of time, please call customer
            service to address the delay. Although we always strive to make our deliveries as timely as possible,
            circumstances may arise outside of our control, such as inclement weather, natural disasters, or other
            postal delays, which may impede the timely arrival of your product. You agree we are not liable for such delays.

            <br><br>2.4 Please contact Customer service at
            <span class="phone-line"><?php echo htmlspecialchars((string) $configs->customer_service_number, ENT_QUOTES, 'UTF-8'); ?></span>
            between the hours of <?php echo htmlspecialchars((string) $configs->cs_hours, ENT_QUOTES, 'UTF-8'); ?>
            with any questions regarding your product, payment or return.

            <br><br>2.5.1 Additional Opt-in Product:
            <?php echo htmlspecialchars(trim((string) $configs->corp_name), ENT_QUOTES, 'UTF-8'); ?>,
            if purchased, will be shipped within 24 hours and arrive within
            5-7 business days. You are ordering a
            30 day supply and will be charged
            $<?php echo number_format((float) $configs->upsell_price, 2); ?> for the product you receive.
            <?php if (!empty($configs->upsell_discount->enabled)): ?>
            If you accept the limited-time <?php echo (int) $configs->upsell_discount->percent_off; ?>% discount offer, you will instead be charged
            $<?php echo number_format((float) $configs->upsell_discount->price, 2); ?> plus
            $<?php echo number_format((float) $configs->upsell_discount->shipping, 2); ?> shipping, then
            $<?php echo number_format((float) ($configs->upsell_discount->rebill_discounted ? $configs->upsell_discount->rebill_price_discounted : $configs->upsell_discount->rebill_price_regular), 2); ?>
            plus shipping every 30 days until you cancel.
            <?php endif; ?>
            This purchase is backed by a 100% Money Back Guarantee. If at some point you choose to cancel this
            purchase, call customer service at anytime between
            <?php echo htmlspecialchars((string) $configs->cs_hours, ENT_QUOTES, 'UTF-8'); ?>.
        </p>

        <h4>3. RETURNS, CANCELLATION, AND REFUND POLICY REFUND POLICY.</h4>
        <p>
            3.1 In order to be considered for a refund, you must call Customer Service at
            <span class="phone-line"><?php echo htmlspecialchars((string) $configs->customer_service_number, ENT_QUOTES, 'UTF-8'); ?></span>
            before any arrangements will be made to issue a refund. We do require that you agree to send back
            the product you received in order to receive a full refund for any transaction. Refunds will only
            be offered for up to 60 days from purchase date, any transaction prior to that shall
            not be considered for a refund.

            <br><br>3.2 Persons with a medical condition, who are pregnant, or have reason to believe they may
            become pregnant in the next 60 days should not order this product. Refund requests will
            not be accepted and refunds will not be given for these reasons. You must consult a physician
            prior to placing an order if you are unsure about whether you can take this product.

            <br><br>3.3 All processed orders are subject to a 9.95 processing fee
            if the charge for the product/order has settled.

            <br><br>3.4 All returns are subject to a restocking fee. Accordingly all products have a
            8.95 restocking fee, per item, if returned unopened and in good condition.
        </p>

        <h4>4. DISPUTE RESOLUTION</h4>

        <p>4.1 If you have any dispute concerning any aspect of these Terms of Website Use, the Website, or any of our
            services, you agree to submit your dispute for resolution by arbitration before the American Arbitration
            Association ("AAA") in the county where you live by filing a Demand for Arbitration. The arbitrator will
            have exclusive authority to resolve any dispute including any claim that all or any part of these Terms of
            Website Use are unenforceable.


            <br><br>4.2 Opt-Out of Arbitration/Class Action Waiver. The Terms &amp; Conditions do not constitute a
            waiver of
            any of your rights and remedies to pursue a claim individually and not as a class action in binding
            arbitration as provided above. This provision preventing you from bringing, joining or participating in
            class action lawsuits is an independent Agreement.

            <br><br>4.3 YOU ACKNOWLEDGE AND AGREE THAT, VIA YOUR ACCEPTANCE OF THESE DISPUTE RESOLUTION PROVISIONS, YOU
            WAIVE ANY RIGHT TO A JURY TRIAL, AS WELL AS YOUR RIGHT TO BRING, JOIN OR PARTICIPATE AS A PLAINTIFF OR A
            CLASS MEMBER IN A CLASS ACTION SUIT OR MULTI-PARTY ARBITRATION BROUGHT AGAINST US, ANY PERSON RELATED TO US
            OR A SERVICE PROVIDER USED BY US TO PROVIDE THE SERVICE.


            <br><br>4.4 You agree to indemnify for any financial harm or any losses caused by Your objections to fees
            that does not comply with this Section. You will be held responsible for the reimbursement of any fees and
            losses incurred as a result of Your failure to comply with any provision in this Agreement.


            <br><br>4.5 Credit Card Billing Customer expressly agrees that if Customer pays by credit card, check or
            demand debit, Customer shall abide by the following statement: "I hereby authorize to initiate debit/credit
            entries to my bank deposit account or credit card."
        </p>
        <h4>5. OFFER, ACKNOWLEDGMENT AND ACCEPTANCE
        </h4>

        <p>5.1 While we make every effort to ensure that items appearing on the Site are available, we cannot guarantee
            that all items are in stock or immediately available when you submit your order. We may reject Your order
            (without liability) if We are unable to process or fulfill it. If this is the case, we will refund any prior
            payment that you have made for that item.
            &nbsp;


            <br><br>5.2 An order submitted by You constitutes an offer by You to Us to purchase the Subscription on
            these Conditions and is subject to Our subsequent acceptance.
            <br><br>5.3 Prior to such acceptance, an automatic e-mail acknowledgement of Your order may be generated.
            Please note that any such automatic acknowledgement does not constitute a formal acceptance of Your order.
            &nbsp;
            <br><br>
            5.4 Our acceptance of Your order takes effect and the contract concluded at the point where such offer is
            expressly accepted by Us dispatching Your order and accepting Your credit card or other payment
            ("Acceptance").
            &nbsp;
            <br><br>5.5 We may keep records of orders received, acknowledgements, acceptances and other contract records
            for a reasonable period after Acceptance. We may be able to provide You with copies on written request;
            however You must make sure you print a copy of all such documents and these Conditions for your own records.
            &nbsp;
        </p>
        <h4>6. YOUR REPRESENTATIONS
            &nbsp;</h4>

        <p>6.1 You represent that the information provided by You when placing Your order is up-to-date, materially
            accurate, and is sufficient for Us to fulfill your order. You are responsible for maintaining and promptly
            updating Your account information with Us for accuracy and completeness and keeping such information (and
            any passwords given to You
            for the purposes of accessing the Site and/or purchasing Products) secure against unauthorized access.
            Unless agreed otherwise or required by applicable law, any warranties provided in relation to Your purchase
            only extend to You on the understanding that You are a user and not a reseller of the Product.
            &nbsp;


            <br><br>6.2 No warranty, commitment or any other obligation should ever be assumed by You on Our behalf or
            on behalf of a
            Product manufacturer, license or supplier without Our express prior written consent.

            <br><br>6.3 PRICE AND TERMS OF PAYMENT (NOTE: WE CANNOT CONFIRM PRICES PRIOR TO ACCEPTANCE OF
            YOUR ORDER)

            <br><br>6.4 Prices payable for the Product are those in effect at the time of dispatch or delivery, unless
            otherwise expressly
            agreed. Prices may be indicated on the Site or an order acknowledgement but the authoritative price in the
            event of
            any discrepancy, is the price that is notified to You on Our Acceptance.

            <br><br>6.5 We have the right at any time prior to Our Acceptance to withdraw any discount and/or to revise
            prices to take into account increases in costs including (without limitation) costs of any materials,
            carriage, labor or the increase or imposition of any tax, duty or other levy and any variation in exchange
            rates. We also reserve the right to notify You of any mistakes in Product descriptions or errors in pricing
            prior to product dispatch. In such event if you choose to continue with fulfillment of the order, You
            acknowledge that the Product or Service will be provided in accordance with such revised description or
            corrected price.

            <br><br>6.6 The places that we deliver to are listed on the Site ("Territory"). Unless otherwise specified,
            prices quoted are: exclusive of the costs of shipping or carriage to the agreed place of delivery within the
            Territory (charges for which are stated on the Site); and exclusive of VAT and any other tax or duty which
            (where applicable) must be added to the price payable.

            <br><br>6.7 You agree to pay for taxes, shipping or carriage of Products as such costs are specified by Us
            on the Site when You submit Your purchase order. Payment shall be made prior to delivery and by such methods
            as are indicated on the Site.

            <br><br>
            6.8 Except as expressly provided elsewhere in these Conditions or the Site, payment may be taken in full
            notwithstanding any claim for short delivery or defects.


            <br>6.9 We will charge credit or debit cards on dispatch of the Product or commencement of Services. We
            reserve the right to verify credit or debit card payments prior to Acceptance.


            <br><br>6.10 If at any time you fail to pay any amount due on the relevant due date, or we are unable to
            collect payment due because of lack of funds or cancelled credit card, We may by notice declare all amounts
            unpaid at that date to be immediately due and payable. No counterclaim or set-off may be deducted from any
            payment due without our written consent. We may also take action against You for the price of Products at
            any time after payment has become due even though property in those Products may not yet have passed to you.
        </p>
        <h4>7. TERMINATION</h4>

        <p>7.1 If You commit an act of bankruptcy or enter into a deed of arrangement with creditors or a court order
            for winding-up is made against You or You take or suffer any similar action in consequence of debt or We
            have cause to believe that You are unable to pay Your debts as they fall due; or You fail to pay any amount
            by the due date or breach any of these Conditions then, without prejudice to any of our other rights, we may:
            <br><br>7.1.1 Stop any Products in transit; and/or

            <br><br>7.1.2 Suspend further Product deliveries; and/or
            <br><br>7.1.3 Stop or suspend provision of Services; and/or


            <br><br>7.1.4 By written notice, terminate Your order and all or any other contracts between Us and You.
            &nbsp;
        </p>

        <h4>8. DELIVERY AND RISK</h4>

        <p>
            8.1 Delivery timescales/dates specified on the Site, in any order acknowledgement, acceptance or elsewhere
            are estimates only. While We endeavor to meet such timescales or dates, We do not undertake to dispatch
            Products and/or commence Services by a particular date or dates and shall not be liable to You in respect of
            delays or failure to do so. IF OUR PRODUCTS FAIL TO ARRIVE IN THE ESTIMATED DELIVERY TIME OF 5-7 BUSINESS
            DAYS, PLEASE CONTACT OUR CUSTOMER SERVICE LINE AND LET THEM KNOW.


            <br><br>8.2 Delivery shall be to a valid address within the Territory submitted by You and subject to
            Acceptance ("Delivery Address"). You must check the Delivery Address on any acknowledgement or acceptance We
            provide and notify Us without delay of errors or omissions. We reserve the right to charge You for any extra
            costs arising from changes You make to the Delivery Address after You submit an order.

            <br><br>
            8.3 If You refuse or fail to take delivery of Products provided in accordance with these Conditions, any
            risk of loss or damage to the Products shall nonetheless pass and without prejudice to any other rights or
            remedies We have:


            <br><br>8.3.1 We shall be entitled to immediate payment in full for the Products or Services delivered and
            either to effect delivery by whatever means We consider appropriate or to store Products at Your risk;

            <br><br>8.3.2 You shall be liable pay on demand all costs of Product storage and any additional costs
            incurred as a result of such refusal or failure to take delivery; and

            <br>8.3.3 We shall be entitled 30 days after the agreed date for delivery to dispose of Products in such
            manner as We determine and may set off any proceeds of sale against any sums due from You.

            <br><br>8.4 Except to the extent required as a result of any mandatory rights You have as a consumer under
            applicable law, You shall not be entitled to reject the Products in whole or in part by reason of short
            delivery and shall pay in full notwithstanding short delivery or non-delivery unless You notify us in
            writing of any claim within 7 days of the latest of the date of receipt of the relevant invoice or delivery
            whereupon You shall pay for the quantity actually delivered.

            <br><br>8.5 Where We deliver Products by installments, each installment constitutes a separate contract and
            any defect in any one or more installments shall not entitle You to repudiate the contract as a whole nor to
            cancel any subsequent installment.
            <br><br>8.6 Save as otherwise provided in these Conditions, risk of loss of or damage to the Products passes
            to You on delivery or when placed in your possession or that of any carrier or transport provided by You,
            whichever shall occur first.


        </p>
        <h4>9. REJECTION, DAMAGE OR LOSS IN TRANSIT&nbsp;</h4>

        <p>9.1 Except as set out above and subject to any rights You have under applicable law that cannot be excluded
            or limited by these Conditions:

            <br><br>9.1.1 We shall not be liable and You shall not be entitled to reject Products or Services, except
            for: (a) damage to or loss of Products or any part thereof in transit (where the Products are carried by Our
            own transport or by a carrier on Our behalf) where notified to Us within 5 working days of receipt of the
            Products; (b) defects in Products (not being defects caused by any act, neglect or default on your part)
            notified in writing to Us within 30 days of receipt of the
            Products; and (c) defective performance of Services (not being defects caused by any act, neglect or default
            on Your part) where notified in writing to Us within 5 days of such defect becoming apparent.

            <br><br>9.1.2 We shall not be liable for any damage or losses arising from the use of the Products in
            connection with other defective or unsuitable Products; Your negligence; improper use or use in any manner
            inconsistent with the manufacturer's specifications or instructions.
            <br><br>9.1.3 Where these is a shortage or failure to deliver, or any defect in or damage to a Product or
            Service, We may at our option: (a) (in the case of Product shortage or non-delivery) make good any such
            shortage or non-delivery; and/or (b) in the case of failure to perform or defective performance of a
            Service, make good such failure or defective performance; and/or (c) in the case of damage or any defect(s)
            in the Product and in accordance with any applicable
            Returns Policy: (i) replace or repair the Product upon You returning the Product; or (ii) refund the price
            paid in respect of any Products found to be damaged or defective.

        </p>
        <h4>10. LIABILITY LIMITATION</h4>

        <p>10.1 TO THE MAXIMUM EXTENT LEGALLY PERMITTED, WHETHER OR NOT WE WERE AWARE OR ADVISED OF THE POSSIBILITY OF
            DAMAGES, AND WHETHER OR NOT THE LIMITED REMEDIES PROVIDED HEREIN FAIL OF THEIR ESSENTIAL PURPOSE (1) OUR
            AGGREGATE LIABILITY (WHETHER FOR BREACH OF CONTRACT, TORT OR ANY OTHER LEGAL THEORY) SHALL IN NO
            CIRCUMSTANCES EXCEED THE COST OF THE PRODUCTS YOU ORDERED AND THAT ARE MOST CLOSELY RELATED TO YOUR DAMAGES
            AND (2) WE SHALL NOT BE LIABLE FOR SPECIAL, INCIDENTAL, INDIRECT, OR CONSEQUENTIAL DAMAGES, LOST PROFITS,
            LOST REVENUE, OR COST OF COVER.

            <br><br> 10.2 <?php echo htmlspecialchars((string) $configs->corp_name, ENT_QUOTES, 'UTF-8'); ?>, in its sole discretion, shall not be liable for a chance
            occurrence or unavoidable or uncontrollable accident beyond either parties control that prevents our ability
            to fulfill obligations under the contract.

        </p>
        <h4>11. THIRD PARTY RIGHTS</h4>

        <p>11.1 You shall indemnify Us against any and all liabilities, claims and costs incurred by or made against Us
            as a direct or indirect result of us performing Services or carrying out any work on or to the Products
            where this has been done to Your (or Your representative's) specific requirements or specifications causing
            an infringement or alleged infringement of any proprietary rights of any third party.

            <br><br>11.2 To the fullest extent permitted by law, we shall have no liability to You in the event the
            Products or Services infringing or being alleged to infringe the proprietary rights of any third party. In
            the event that the Products are or may be the subject of patent, copyright, database right, registered
            design, trademark or other rights of any third party, You should refer to the relevant terms of the Product
            manufacturer and/or licensor/owner. We shall be obliged to transfer to You only such right or title as we
            have

        </p>
        <h4> 12. WARRANTY "AS IS" IN GENERAL WITHOUT REGARD TO
            SEPARATE WARRANTY STATEMENTS PACKAGED BY THE
            MANUFACTURER WITH THE PRODUCTS.
        </h4>

        <p>12.1 All Product specifications, illustrations, drawings, particulars, dimensions, performance data and other
            information on the Site or made available by Us are intended to represent no more than a general
            illustration of the Products and do not constitute a warranty or representation by us that the Products will
            conform with the same. You must refer to the manufacturer's specifications or warranty documentation to
            determine your rights and remedies in this regard.

            <br><br>12.2 You will have the benefit of the manufacturer's, licensor's or supplier's warranty with the
            Products supplied and should refer to the relevant documentation supplied with the Product in this regard.

            <br><br>12.3 Your rights of repair or replacement of any Products or any part or parts thereof which are
            found to be defective will (except where agreed otherwise) be negated or rendered void where:

            <br><br>12.3.1 Products have been repaired or altered by persons other than the manufacturer, us or any
            authorized dealer; and/or

            <br><br>12.3.2 Defective Product or Products have not been returned together with full details in writing of
            the alleged defects within 30 days from the date on which such Products were delivered; and/or

            <br><br>12.3.3 Defects are due (wholly or partially) to mistreatment, improper use or storage or maintenance
            or installation, or failure to observe any manufacturers' instructions or other directions issued or made
            available by us in connection with the delivered Products.

            <br><br>12.4 EXCEPT AS EXPRESSLY STATED OTHERWISE IN THIS SECTION 12, WE MAKE NO EXPRESS WARRANTIES OR
            REPRESENTATIONS AND WE DISCLAIM ALL IMPLIED WARRANTIES AND REPRESENTATIONS, INCLUDING, WITHOUT LIMITATION,
            THE IMPLIED WARRANTIES OF MERCHANTABILITY, FITNESS FOR A PARTICULAR PURPOSE AND NON-INFRINGEMENT. THESE
            CONDITIONS STATE YOUR SOLE AND EXCLUSIVE REMEDIES.

        </p>
        <h4>13. CONSENTS, CUSTOMS DUTIES &amp; EXPORT</h4>

        <p>13.1 By clicking ORDER NOW, I am agreeing to receive text messages from <?php echo htmlspecialchars((string) $configs->corp_name, ENT_QUOTES, 'UTF-8'); ?>
            and business partners. I
            provide my signature expressly consenting to recurring contact from <?php echo htmlspecialchars((string) $configs->corp_name, ENT_QUOTES, 'UTF-8'); ?> or
            its business partners at
            the number I provided regarding products or services via live, automated or prerecorded telephone call, text
            message, or email. I understand that my telephone company may impose charges on me for these contacts, and I
            am not required to enter into this agreement as a condition of purchasing property, goods, or services. I
            understand that I can revoke this consent at any time. Terms & conditions/privacy policy apply. For SMS
            campaigns: Text STOP to cancel and HELP for help. Msg & data rates may apply. Recurring msgs up to 9 msgs
            per month.

            <br><br>13.2 If any license or consent of any government or other authority is required for the acquisition,
            carriage or use of the Products by You, You shall obtain such license or consent at your own expense and if
            necessary produce evidence to us on demand. Failure so to do shall not entitle you to withhold or delay
            payment of the price. Any additional expenses or charges incurred by us resulting from such failure shall be
            met by you.

            <br><br>
            13.3 Products licensed or sold to you under these Conditions may be subject to export control laws and
            regulations in the Territory or other relevant jurisdiction where You take delivery or use them. You shall
            be responsible for complying with those laws and will not do anything to breach them.

            <br><br>13.4 Items entering the European Economic Area (EEA) from outside over a certain value may be
            subject to customs charges (e.g. where costs are in excess of your personal import allowance). You may be
            subject to customs charges,import duties and taxes, levied when the Product reaches Your specified
            destination. Any such additional charges for customs clearance or import duties or taxes must be met by You,
            since We have no control over what these charges are. You should contact the local customs office in the
            relevant jurisdiction for further information on customs policies or duties.

        </p>
        <h4>14. NOTICES</h4>

        <p>14.1 Any notice or other communications in relation to Our contract may be given by sending the same by hand
            delivery, pre-paid post, fax or e-mail to the latest address and contact that one party has notified in
            writing to the other. This will also be the address for service of legal proceedings in the manner
            prescribed by law. Except as set out above in relation to cancellation of consumer orders, such notices or
            communications (where properly addressed) shall be considered received:


            <br><br>14.1.1 In relation to hand delivery, on the date of delivery at the relevant address (or, if this is
            not a working date, the first working date thereafter);

            <br><br>
            14.1.2 If posted, 5 working days after the date of posting;

            <br><br>14.1.3 If sent by email, on the earliest of (i) the email being acknowledged by the recipient as
            received; (ii) receipt by the sender of an automated message indicating successful delivery or the email
            having been opened; or (iii) the expiry of 48 hours after transmission, provided that the sender has not
            received notification of unsuccessful transmission.

        </p>
        <h4>15. PERSONAL INFORMATION AND YOUR PRIVACY</h4>

        <p>15.1 We will observe applicable data protection laws and will not use information that does or can be used to
            personally identify You ("Personal Data") other than as set out in Our Privacy Policy ("Privacy Policy"). By
            submitting Your Personal Data in relation to Your order, You consent to such Personal Data being processed
            to fulfill Your order and in accordance with such Privacy Statement.

        </p>
        <h4>16. GENERAL</h4>

        <p>16.1 You shall not assign, transfer, charge or make over or purport to assign transfer charge to make over
            Your rights under these Conditions. Any purported assignment shall be null and void.
            <br><br>16.2 We shall not be liable to You nor held in breach of contract for any loss or damage which may
            be suffered as a direct or indirect result of Us being prevented, hindered or delayed in the performance by
            reason of any circumstances beyond Our reasonable control including (but not limited to) any act of God,
            war, terror, riot, civil commotion, government action, explosion, fire, flood, storm, accident, strike,
            lock-out, trade dispute or labor disturbance, breakdown of plant or machinery, interruption in the supply of
            power, Internet communications, or materials and in such event we may elect to cancel Your order and refund
            any payments made.
            <br><br>16.3 You acknowledge that these Conditions supersede and cancel all previous contracts, agreements
            and working arrangements whether oral or written, express or implied, between us. These Conditions prevail
            over any other terms or conditions contained in or referred to elsewhere or implied by trade, custom or
            course of dealing. Any purported
            terms or conditions to the contrary are hereby excluded to the fullest extent legally permitted. To the
            fullest extent permitted under applicable law, We reserve the right to modify these Conditions without prior
            written notice to You with effect for the future, subject to Your right to reject, by way of written notice,
            our modifications to these Conditions with respect to any orders for which Acceptance, but not yet
            fulfillment, has occurred.
            <br><br>16.4 No relaxation, forbearance, delay or indulgence by either You or Us in enforcing any of these
            Conditions or the granting of time by either party to the other shall prejudice or restrict such rights and
            powers.
            <br><br>16.5 No waiver of any term or condition of these Conditions shall be effective unless made in
            writing and signed by Us. The waiver of any breach of any Condition shall not be construed as a waiver of
            any subsequent breach or condition.
            <br><br>16.6 If for any reason We determine or a court of competent jurisdiction finds that any provision or
            portion of these Conditions to be illegal, unenforceable, or invalid under applicable law in a particular
            jurisdiction:
            <br><br>16.6.1 These Conditions will not be affected in other jurisdictions to the extent that such
            determination or finding has no application; and
            <br><br>16.6.2 In the relevant jurisdiction, the remainder of these Conditions (to the fullest extent
            permitted by law) will continue in full force and effect.
        </p>

        <h4>17. GOVERNING LAW&nbsp;</h4>

        <p> 17.1 The construction validity and performance of these Conditions shall be governed by US Law and You agree
            to submit to the exclusive jurisdiction of the US Courts, in the event of legal proceedings arising from any
            dispute; The language of any dispute resolution procedure or any proceedings will be English.

        </p>

        <h4 id="wireless">18. DETAILED WIRELESS POLICY </h4>
        <p>Data obtained from you in connection with this SMS service may include your name, address, cell phone number,
            your provider's name, and the date, time, and content of your messages. In addition to any fee of which you
            are notified, your provider's standard messaging rates apply to our confirmation and all subsequent SMS
            correspondence. All charges are billed by and payable to your mobile service provider. We will not be liable
            for any delays in the receipt of any SMS messages, as delivery is subject to effective transmission from
            your network operator. SMS message services are provided on an AS IS basis. We may use personal information
            to provide the services you've requested, including services that display customized content and
            advertising. In addition to any fee of which you are notified, your provider's standard messaging rates
            apply to our confirmation and all subsequent SMS correspondence. You may opt-out and remove your SMS
            information by sending "STOP", "END", "QUIT" to the SMS text message you have received. If you remove your
            SMS information from our database it will no longer be used by us for secondary purposes, disclosed to third
            parties, or used by us or third parties to send promotional correspondence to you. We may also use personal
            information for auditing, research and analysis to operate and improve our technologies and services. We may
            share aggregated and non personal information with third parties outside of
            <?php echo htmlspecialchars((string) $configs->corp_name, ENT_QUOTES, 'UTF-8'); ?> . When we use third
            parties to assist us in processing your personal information, we require that they comply with our Privacy
            Policy and any other appropriate confidentiality and security measures. We may also share information with
            third parties in limited circumstances, including when complying with legal process, preventing fraud or
            imminent harm, and ensuring the security of our network and services. You may remove your information from
            our database. If you remove your information from our database it will no longer be used by us for secondary
            purposes, disclosed to third parties, or used by us or third parties to send promotional correspondence to
            you. You may remove your information by sending your request in writing via email to
            <?php echo htmlspecialchars((string) $configs->customer_service_email, ENT_QUOTES, 'UTF-8'); ?> or by sending "STOP",
            "END", "QUIT" to the SMS text message you have received.

        </p>
    </div>
</body>

</html>