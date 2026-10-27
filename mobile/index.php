<?php
include("../_global.php");
$states = array("AL" => "Alabama (AL)", "AK" => "Alaska (AK)", "AS" => "American Samoa (AS)", "AZ" => "Arizona (AZ)", "AR" => "Arkansas (AR)", "AE" => "Armed Forces (AE)", "AA" => "Armed Forces Americas (AA)", "AP" => "Armed Forces Pacific (AP)", "CA" => "California (CA)", "CO" => "Colorado (CO)", "CT" => "Connecticut (CT)", "DE" => "Delaware (DE)", "DC" => "District of Columbia (DC)", "FM" => "Federated States of Micronesia (FM)", "GA" => "Georgia (GA)", "FL" => "Florida (FL)", "HI" => "Hawaii (HI)", "GU" => "Guam (GU)", "IL" => "Illinois (IL)", "ID" => "Idaho (ID)", "IA" => "Iowa (IA)", "IN" => "Indiana (IN)", "KY" => "Kentucky (KY)", "KS" => "Kansas (KS)", "ME" => "Maine (ME)", "LA" => "Louisiana (LA)", "MA" => "Massachusetts (MA)", "MD" => "Maryland (MD)", "MN" => "Minnesota (MN)", "MI" => "Michigan (MI)", "MO" => "Missouri (MO)", "MS" => "Mississippi (MS)", "NE" => "Nebraska (NE)", "MT" => "Montana (MT)", "NH" => "New Hampshire (NH)", "NV" => "Nevada (NV)", "NM" => "New Mexico (NM)", "NJ" => "New Jersey (NJ)", "NC" => "North Carolina (NC)", "NY" => "New York (NY)", "MP" => "Northern Mariana Islands (MP)", "ND" => "North Dakota (ND)", "OK" => "Oklahoma (OK)", "OH" => "Ohio (OH)", "PA" => "Pennsylvania (PA)", "OR" => "Oregon (OR)", "MH" => "Republic of Marshall Islands (MH)", "PR" => "Puerto Rico (PR)", "SC" => "South Carolina (SC)", "RI" => "Rhode Island (RI)", "TN" => "Tennessee (TN)", "SD" => "South Dakota (SD)", "UT" => "Utah (UT)", "TX" => "Texas (TX)", "VI" => "Virgin Islands of the U.S. (VI)", "VT" => "Vermont (VT)", "WA" => "Washington (WA)", "VA" => "Virginia (VA)", "WI" => "Wisconsin (WI)", "WV" => "West Virginia (WV)", "WY" => "Wyoming (WY)");
if (!isset($_SESSION['token'])) {
    $_SESSION['token'] = generate_csrf_token();
}
/*  if ($mobile_detect->isMobile() && !$mobile_detect->isTablet()) {
     $url = $configs->mobile_path."?".$_SERVER['QUERY_STRING'];
     header("Location: $url");
 } */
$nextPage = 'qualify.php' . (!empty($_SERVER['QUERY_STRING']) ? ('?' . $_SERVER['QUERY_STRING']) : '');
header("Location: $nextPage");
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta http-equiv="content-type" content="text/html; charset=UTF-8">
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?php echo $configs->step_1_name; ?> Male Enhancement Gummies</title>

    <meta content="width=device-width, initial-scale=1" name="viewport">
    <link href="<?= $configs->root_path ?>assets/brand/favicon.png" rel="icon" type="image/png">

    <link rel="stylesheet" type="text/css" href="<?= $configs->root_path ?>assets/css/bootstrap.min.css">
    <link href="<?= $configs->root_path ?>mobile/assets/css/app.css" rel="stylesheet">
    <link href="<?= $configs->root_path ?>assets/css/style.css" type="text/css" rel="stylesheet">
    <link href="<?= $configs->root_path ?>assets/css/slick.css" type="text/css" rel="stylesheet">

    <link rel="preconnect" href="https://fonts.gstatic.com">
    <link
        href="https://fonts.googleapis.com/css2?family=Open+Sans:wght@400;700&family=Oswald:wght@400;500;600;700&family=Roboto+Condensed:ital,wght@0,400;0,700;1,400;1,700&display=swap"
        rel="stylesheet">



    <script type="text/javascript">
        function getDate(days) {
            var monthNames = new Array("Jan", "Feb", "Mar", "Apr", "May", "Jun", "Jul", "Aug", "Sep", "Oct", "Nov", "Dec");
            var now = new Date();
            now.setDate(now.getDate() + days);
            var nowString = monthNames[now.getMonth()] + " " + now.getDate() + ", " + now.getFullYear();
            document.write(nowString);
        }
    </script>
    <style>
        .pulse {
            -webkit-transition: .3s ease;
            -o-transition: .3s ease;
            transition: .3s ease;
            animation: 1s pulse infinite alternate ease-in-out;
            position: relative;
        }

        .pulseOrder {
            position: absolute;
            left: 50%;
            top: 50%;
            transform: translate(-59%, -330%);
            width: 50%;
        }

        @keyframes pulse {
            0% {
                transform: scale(1);
            }

            100% {
                transform: scale(0.95);
            }
        }

        @media (max-width: 1100px) {
            .pulseOrder {
                position: absolute;
                left: 50%;
                top: 50%;
                transform: translate(-59%, -300%);
                width: 50%;
            }
        }

        @media (max-width: 768px) {
            .pulseOrder {
                position: absolute;
                left: 50%;
                /*top: 50%;*/
                top: -26px;
                transform: translate(-59%, -302%);
                /*width: 50%;*/
                width: 269px;
            }
        }

        @media (max-width: 576px) {
            .pulseOrder {
                position: absolute;
                left: 50%;
                /*top: 50%;*/
                top: -26px;
                transform: translate(-59%, -264%);
                /*width: 50%;*/
                width: 269px;
            }
        }

        @media (max-width: 491px) {
            .pulseOrder {
                position: absolute;
                /*left: 50%;*/
                left: 54%;
                /*top: 50%;*/
                top: -43px;
                transform: translate(-59%, -172%);
                /*width: 50%;*/
                width: 269px;
                margin-left: 6px;
            }
        }

        @media (max-width: 350px) {
            .pulseOrder {
                position: absolute;
                /*left: 50%;*/
                left: 54%;
                /*top: 50%;*/
                top: -43px;
                transform: translate(-59%, -128%);
                /*width: 50%;*/
                width: 269px;
                margin-left: 18px;
            }
        }
    </style>
</head>

<body>
    <nav class="navbar" role="navigation" style="border: 0; margin-bottom: 0;">
        <div class="navbar-default navbar-inner" style="background: #eee;">
            <div class="contactInfo" style="background: #fff; padding: 6px 0">
                <div class="container">
                    <div class="row">
                        <div class="col-sm-12 independent text-center" style="color: #721c24;">
                            <strong><img src="<?= $configs->root_path ?>assets/images/mark.png"
                                    style="display: inline-block; right: 0px; width: 17px; height: auto; vertical-align: top;">WARNING:</strong>
                            Due to extremely high social media demand, there is limited supply of
                            <?php echo $configs->step_1_name; ?> Male
                            Enhancement Gummies in stock as of <div class="time" id="time1"
                                style="display:inline; font-weight: bold;">
                                <script type="text/javascript">
                                    getDate(0);
                                </script>
                            </div>!
                            <div style="display:inline;">Offer expires in <span data-countdown-home="" id="countdown"
                                    style="font-weight:bold;"><strong>Expiring Soon!</strong></span></div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="container container-custom-logo">
                <div class="row">
                    <div class="navbar-header">
                        <div class="navbar-brand">
                            <img src="<?= $configs->root_path ?>assets/brand/logo.png"
                                class="logo lazyload img-responsive">
                            <img src="<?= $configs->root_path ?>image.php" alt="alt-logo" style="display:none;">
                        </div>
                        <button type="button" class="navbar-toggle collapsed" data-toggle="collapse"
                            data-target="#navbar" aria-expanded="false" aria-controls="navbar">
                            <span class="sr-only">Toggle navigation</span>
                            <span class="icon-bar"></span>
                            <span class="icon-bar"></span>
                            <span class="icon-bar"></span>
                        </button>
                    </div>
                    <div id="navbar" class="navbar-collapse collapse orange12 navbar-custom-logo">
                        <ul class="nav navbar-nav knav">
                            <li><a href="javascript:bookmarkscroll.scrollTo('science')">How it Works</a></li>
                            <li><a href="javascript:bookmarkscroll.scrollTo('theproof')">The Proof</a></li>
                            <li><a href="javascript:bookmarkscroll.scrollTo('whatyouget')">What You Get</a></li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </nav>

    <section id="header">
        <div class="headerbg">
            <div class="container">
                <div class="col-sm-8 nopad">
                    <div class="hero-box">
                        <img src="<?= $configs->root_path ?>assets/images/main-grabber-mobile.png"
                            class="img-responsive mobile center-block" style="margin-bottom:25px;">
                        <img src="<?= $configs->root_path ?>assets/brand/product.png" class="hero-prod">
                    </div>
                    <div class="viraltextm">CLAIM YOUR FREE BOTTLE!</div>
                </div>
                <div class="col-sm-4 nopad">
                    <div class="formSeals">
                        <img src="<?= $configs->root_path ?>assets/images/seal.png" width="330" height="108"
                            class="lazyload center-block img-responsive">
                    </div>
                    <a id="ordernow"></a>

                </div>
            </div>
        </div>
    </section>

    <section id="section1">
        <div class="container">
            <div class="row">
                <div class="col-sm-7" style="padding:0 20px;">
                    <h2>Breakthrough "Amazing Formula"!</h2>
                    <p class="highlight">Anytime <strong>Natural Male Enhancement Formula</strong></p>
                    <p><strong><?php echo $configs->step_1_name; ?> Male Enhancement Gummies - The single most powerful
                            male enhancement all natural supplement formula ever, and the only
                            product available with clinically tested ingredients. YOUR SIMPLE
                            SOLUTION FOR A LARGER PENIS.</strong> </p>
                    <p><strong><span style="text-decoration: underline;">This powerful male enhancement formula has been
                                identified as the most exciting solution of our time.</span></strong></p>
                </div>
                <div class="col-sm-5 nopad" style="position:relative; display:table;">
                    <img src="<?= $configs->root_path ?>assets/images/feature-product-1.png"
                        class="lazyload img-responsive pull-right" width="479" height="441" alt="">
                    <img src="<?= $configs->root_path ?>assets/brand/product.png" class="s1-prod">
                </div>
            </div>
        </div>
    </section>

    <div class="theproofD">
        <!--desktop-->
        <div class="container">
            <div class="row">
                <div class="col-sm-6">&nbsp;</div>
                <div class="col-sm-6">
                    <img src="<?= $configs->root_path ?>assets/images/theproff.png" width="529" height="104"
                        class="lazyload img-responsive center-block" alt="ketopia ketosis" style="margin-top:107px;">
                    <h1><?php echo $configs->step_1_name; ?> Male Enhancement Gummies have helped hundreds of healthy
                        men across all ages enjoy a
                        fuller and satisfied sex life.</h1>
                    <img src="<?= $configs->root_path ?>assets/images/testimonials.jpg" width="553" height="439"
                        class="lazyload img-responsive center-block" alt="Testimonials"> <br>
                    <p class="text-center"><?php echo $configs->step_1_name; ?> Male Enhancement Gummies are the only
                        product that
                        combines powerful ingredients such as Tribulus Terrestris, Saw
                        Palmetto, L-Arginine, and Eurycoma Longifolia Extract into a single safe
                        and effective compound.</p>
                </div>
                <div class="col-sm-6">
                    <div class="s2-lft-prod">
                        <img src="<?= $configs->root_path ?>assets/brand/product.png" class="s2-prod1">
                        <img src="<?= $configs->root_path ?>assets/brand/product.png" class="s2-prod2">
                        <img src="<?= $configs->root_path ?>assets/brand/product.png" class="s2-prod3">
                    </div>
                </div>
                <div class="col-sm-6">
                    <a href="javascript:void(0);" class="btn__send loading-btn"><img
                            src="<?= $configs->root_path ?>assets/images/s2-arrow.png" width="622" height="199"
                            class="lazyload img-responsive center-block" alt="order ketopia"></a>
                </div>
            </div>
        </div>
        <!--mobile-->
        <div class="theproofM">
            <div class="container">
            </div>
        </div>
    </div>

    <div class="container">
        <a id="science"></a>
        <div class="row">
            <div class=" col-lg-12 text-center">
                <h1 class="mont60 super dark"><br>HOW DOES IT WORK?</h1>
                <span class="os28 lighter dark">
                    <?php echo $configs->step_1_name; ?> Male Enhancement Gummies solve the <span
                        class="orange">ENLARGEMENT SOURCE</span> by
                    boosting cell volume to grow larger...
                </span>
                <div class="space">&nbsp;</div>
            </div>
            <div class="col-sm-5">
                <img src="<?= $configs->root_path ?>assets/images/s4-img1.jpg" width="391" height="237"
                    class="lazyload img-responsive centered" alt="ketosis diet body burns carbs instead of fat"><br>
                <h2>Tribulus Terrestris Fruit Extract</h2>
                <p class="content">Increase free testosterone levels. It
                    stimulates the release of luteinizing hormone, which in turn stimulates
                    the release of testosterone from the Leydig cells of the testicles. This
                    stimulation increases powerful erections. </p>
                <br class="hidden-xs">
                <h2>Saw Palmetto Fruit Extract</h2>
                <p class="content">Revitalize the body and testosterone
                    levels to support a healthy sex drive. It's high nutrient content
                    increases energy, stamina and vitality. Saw Palmetto also contains
                    unique nutrients which support healthy libido.
                </p>
            </div>
            <div class="col-sm-2"></div>
            <div class="col-sm-5">
                <img src="<?= $configs->root_path ?>assets/images/s4-img2.jpg" width="391" height="237"
                    class="lazyload img-responsive centered" alt=""><br>
                <h2>L-Arginine</h2>
                <p class="content">L-Arginine is a precursor of nitric oxide
                    (NO) in the human body. L-Arginine has been shown to significantly
                    improve blood flow to the genital area - helping the penis enlarge to
                    maximum capacity, size, hardness and erection frequency. It was first
                    isolated in 1886, reportedly from the extract of a lupine.
                </p>
                <br class="hidden-xs">
                <h2>Eurycoma Longifolia Extract</h2>
                <p class="content">The root and bark of Eurycoma longifolia
                    are used for treating erectile dysfunction (ED), increasing interest in
                    sex, male infertility, boosting athletic performance, body building, and
                    reducing body fat.
                </p>
                <br class="hidden-xs"><br class="hidden-xs"><br class="hidden-xs"><br>
            </div>
        </div>
        <div class="row">
            <div class="col-lg-12 centered">
                <br><br>
                <h1 class="os33 lighter dark">WHETHER IT’S BECAUSE YOU DON'T
                    FEEL YOU MEASURE UP IN THE BEDROOM... YOUR PENIS LOOKS SMALLER COMPARED
                    TO YOUR GYM BUDDIES... OR YOU CAN'T KEEP A PARTNER BECAUSE YOU FEEL
                    INADEQUATE. THAT'S WHAT REALLY MAKES YOU FEEL TORTURED AND HOPELESS IN
                    THE BEDROOM.</h1>
                <span class="os33 orange super montbold until-txt"><strong>UNTIL NOW</strong></span>
            </div>
        </div>
    </div>

    <div class="ketpoia-ketosis-banner">
        <div class="container">
            <div class="row">
                <h2>Advanced Enhancement Formula</h2>
                <img src="<?= $configs->root_path ?>assets/brand/product.png"
                    class="center-block img-responsive bottleketosis">
                <img src="<?= $configs->root_path ?>assets/images/s5-img1.png" class="center-block img-responsive"
                    style="line-height:845px;margin:10px; max-width:491px;height:auto; width: 90%; margin:0 auto"
                    alt="Ketopia Logo">
            </div>
        </div>
    </div>

    <a id="whatyouget"></a>
    <div class="container">
        <div class="row">
            <div class=" col-lg-12 text-center mobi-margin">
                <img src="<?= $configs->root_path ?>assets/images/nongmo.jpg" width="102" height="102"
                    class="lazyload center-block" alt="non gmo">
                <h1 class="mont60 get-m super dark">WHAT DO <br class="show-mob">YOU GET?</h1>
                <span class="os28 lighter dark">Benefits of <span class="orange"><?php echo $configs->step_1_name; ?>
                        Male Enhancement
                        Gummies!</span></span>
                <div class="space">&nbsp;</div>
            </div>
            <div class="col-sm-8 col-sm-offset-2 mobi-margin">
                <div style="float:left; width:100%; text-align:center;">
                    <div class="s5-proDv">
                        <img src="<?= $configs->root_path ?>assets/brand/product.png" class="s5-prod1">
                        <img src="<?= $configs->root_path ?>assets/brand/product.png" class="s5-prod2">
                        <img src="<?= $configs->root_path ?>assets/brand/product.png" class="s5-prod3">
                    </div>
                </div>
                <h1 class="productTitle">The Key Is Massive Expansion of The Penis</h1>
                <h3 class="productSub">The Enhancement you deserve</h3>
                <p class="content">
                    The 2 penis chambers called corpus cavernosa is what
                    determines your penis size with an erection. The function of the corpus
                    cavernosum is purely erectile. Muscles surround the cavernosum and
                    spongiosum. These muscles support the penis when erect and contract
                    during ejaculation.
                </p>
                <p>
                    <img src="<?= $configs->root_path ?>assets/images/gren-tk.png"
                        style="margin:-2px 5px 0 0; width:15px;"> <strong>Scientists
                        have finally discovered a way to push these chambers to huge density and thickness by a complex
                        ingredient profile.</strong><br>
                    <img src="<?= $configs->root_path ?>assets/images/gren-tk.png"
                        style="margin:-2px 5px 0 0; width:15px;"> <strong>When
                        taking <?php echo $configs->step_1_name; ?> Male Enhancement Gummies this allows significant
                        increased blood flow to
                        the open spaces inside the corpora cavernosa chambers.</strong><br>
                    <img src="<?= $configs->root_path ?>assets/images/gren-tk.png"
                        style="margin:-2px 5px 0 0; width:15px;"> <strong>Once the
                        blood is trapped, the major muscle located in the corpora cavernosa helps to sustain this huge
                        erection.</strong>
                </p>
                <p class="content">
                    What’s more, it delivers a potent hard erection that can
                    last for hours… and it will continue to keep your penis huge as long as
                    you keep taking it. This Makes Other Male Enhancement Remedies Look
                    Like Slackers!
                </p>
            </div>
        </div>
    </div>

    <div class="stayingKetosis">
        <div class="container">
            <div class="row">
                <h3>POWERFUL</h3>
                <h2>NATURAL</h2>
                <h3>PROVEN SAFE</h3>
            </div>
        </div>
    </div>

    <a id="howto"></a>
    <div class="container">
        <div class="row">
            <div class="col-sm-8 ketopiainfo mobi-margin">
                <h2>EXPERT FORMULATION</h2>
                <p><strong><?php echo $configs->step_1_name; ?> Male Enhancement Gummies are potent in enhancing size
                        and girth that focuses in on
                        the chambers of your penis for maximum hardness and growth.</strong></p>
                <p>This special synergistic blend of penis enhancing
                    compounds shows you what science can do when you trust in nature’s
                    potent miracles. </p>
                <p><?php echo $configs->step_1_name; ?> Male Enhancement Gummies! Remember the name, because you'll be
                    hearing about it a lot very
                    soon!
                </p>
                <br>
                <h3>TIPS FOR SUCCESS</h3>
                <p>You can do absolutely nothing, remain the average size you are, and continue to lack confidence.</p>
                <strong>OR</strong><br><br>
                <p><img src="<?= $configs->root_path ?>assets/images/gren-tk.png"
                        style="margin:-2px 5px 0 0; width:15px;"> Spend 30 seconds a day
                    and take <?php echo $configs->step_1_name; ?> Male Enhancement Gummies.</p>
                <p><img src="<?= $configs->root_path ?>assets/images/gren-tk.png"
                        style="margin:-2px 5px 0 0; width:15px;"> Start satisfying women
                    like you’ve always wanted and keep her coming back for more!
                </p>
            </div>
            <div class="col-sm-4">
                <img src="<?= $configs->root_path ?>assets/brand/product.png" width="284" height="500"
                    class="img-responsive center-block formula-bottle" alt="ketonx drink">
                <p class="small text-center"><?php echo $configs->step_1_name; ?> Male Enhancement Gummies are a daily
                    supplement.</p>
            </div>
        </div>
    </div>

    <div class="allnatural">
        <div class="container">
            <div class="row">
                <img src="<?= $configs->root_path ?>assets/images/goodnews.png"
                    class="lazyload center-block img-responsive" width="796" height="395" alt="">
            </div>
        </div>
    </div>

    <a id="theproof"></a>
    <div class="container">
        <div class="row">
            <div class=" col-lg-12 text-center">
                <h1 class="mont50 super dark centered">WHAT OTHERS ARE SAYING:</h1><br>
                <span class="os28 lighter dark">
                    <?php echo $configs->step_1_name; ?> Male Enhancement Gummies are all over the Internet and the
                    results <span class="orange">are
                        astonishing!</span>
                </span><br>
                <div>
                    <center>
                        <img src="<?= $configs->root_path ?>assets/images/fb-logo.jpg" width="263" height="120" alt=""
                            class="lazyload">
                    </center>
                </div>
                <div class="space">&nbsp;</div>
            </div>
            <div class="col-md-10 col-md-offset-1">
                <center><img src="<?= $configs->root_path ?>assets/images/testimonials2.jpg"
                        class="lazyload img-responsive" alt=""> </center>
            </div>
            <div class="col-lg-12">
                <div class="space">&nbsp;</div>
            </div>
        </div>
    </div>

    <div class="footerC2a">
        <div class="container">
            <div class="row">
                <div class="col-md-4 col-sm-2">&nbsp;</div>
                <div class="col-md-8 col-sm-10">


                    <a href="javascript:void(0);" class="btn__send loading-btn">
                        <img src="<?= $configs->root_path ?>assets/images/prod-2.png" width="680"
                            class="lazyload img-responsive center-block" height="771" alt="">
                    </a>

                    <div class="s10-proDv">
                        <img src="<?= $configs->root_path ?>assets/brand/product.png" class="s10-prod1">
                        <img src="<?= $configs->root_path ?>assets/brand/product.png" class="s10-prod2">
                        <img src="<?= $configs->root_path ?>assets/brand/product.png" class="s10-prod3">
                    </div>
                    <center class="pulse"><a href="javascript:void(0);" class="btn__send loading-btn"></a>
                        <a href="javascript:void(0);" class="btn__send loading-btn">
                            <btn class="c2abtn pulseOrder">ORDER NOW</btn>
                        </a>
                    </center>

                </div>
            </div>
        </div>
    </div>

    <div class="container">
        <div class="row">
            <div class="col-sm-10 col-sm-offset-1">
                <br>
                <img src="<?= $configs->root_path ?>assets/images/footer-logo.png" width="960" height="87"
                    class="lazyload center-block img-responsive" alt=""><br>

                <div class="col-lg-12 content centered" style="font-size:14px;">
                    <div class="row">
                        <div id="disclaimers" style="padding:30px 0 50px;">
                            <p>
                            </p>
                            <center>
                                <a href="javascript:void(0);"
                                    onClick="openNewWindow('../disclosures/terms.php','modal');">Terms &amp;
                                    Conditions</a> |
                                <a href="javascript:void(0);"
                                    onClick="openNewWindow('../disclosures/privacy.php','modal');">Privacy Policy</a> |
                                <a href="javascript:void(0);"
                                    onClick="openNewWindow('../disclosures/wireless.php','modal');">Wireless Policy</a>
                            </center>
                            <p></p>
                            <p>
                            </p>
                            <center>Notice: The products and
                                information found on this site are not intended to replace professional
                                medical advice or treatment. These statements have not been evaluated by
                                the Food and Drug Administration. These products are not intended to
                                diagnose, treat, cure or prevent any disease. Individual results may
                                vary.</center>
                            <p></p>
                            <p style="color:#000;"><strong>©
                                    <script>
                                        var date = new Date();
                                        document.write(date.getFullYear())
                                    </script> <?php echo $configs->step_1_name; ?>. All Rights Reserved.
                                </strong></p>
                        </div>
                    </div>
                </div>

            </div>
        </div>
    </div>
    <div class="fixed-btn">
        <a href="javascript:void(0)" class="btn__send loading-btn">RUSH MY ORDER NOW </a>
    </div>

    <!--popup processing wrapper-->
    <div class="popup-loading-wrapper" style="display:none;">
        <div class="popup">
            <figure class="product-image"></figure>
            <p>Reserving Your Bottle Of</p>
            <h2><?php echo $configs->step_1_name; ?> Male Enhancement Gummies</h2>
            <img src="<?= $configs->root_path ?>assets/images/icon-loading.png" alt="" class="loading-image">
        </div>

    </div>

    <!--end popup processing wrapper-->


    <script type="text/javascript" src="<?= $configs->root_path ?>assets/js/jquery-3.5.1.min.js"></script>
    <script type="text/javascript" src="<?= $configs->root_path ?>assets/js/slick.min.js"></script>
    <script type="text/javascript" src="<?= $configs->root_path ?>assets/js/scripts.js"></script>
    <script type="text/javascript" src="<?= $configs->root_path ?>assets/js/bookmarkscroll.js"></script>
    <script type="text/javascript" src="<?= $configs->root_path ?>assets/js/bootstrap.min.js" defer="defer"></script>
    <script type="text/javascript">
        $(document).ready(function () {
            $('.loading-btn').click(function (e) {
                $('.popup-loading-wrapper').show();
                setTimeout(function () {
                    window.location.href = '<?php echo $nextPage; ?>';
                }, 2000);
                return false
            });
        });

        $(function () {
            $(document).on('click', '#app_common_modal_close', function () {
                $('#app_common_modal').remove();
            });
        });

        function openNewWindow(page_url, type, window_name, width, height, top, left, features) {
            if (!type) {
                type = 'popup';
            }

            if (!width) {
                width = 480;
            }

            if (!height) {
                height = 480;
            }

            if (!top) {
                top = 50;
            }

            if (!left) {
                left = 50;
            }

            if (!features) {
                features = 'resizable,scrollbars';
            }

            if (type == 'popup') {
                var settings = 'height=' + height + ',';
                settings += 'width=' + width + ',';
                settings += 'top=' + top + ',';
                settings += 'left=' + left + ',';
                settings += features;

                win = window.open(page_url, window_name, settings);
                win.window.focus();
            } else if (type == 'modal') {
                var html = '';
                html += '<div id="app_common_modal">';
                html += '<div class="app_modal_body"><a href="javascript:void(0);" id="app_common_modal_close">X</a><iframe src="' + page_url + '" frameborder="0"></iframe></div>';
                html += '</div>';

                if (!$('#app_common_modal').length) {

                    $('body').append(html);
                }
                $('#app_common_modal').fadeIn();
            }

        }
    </script>

    <script type="text/javascript">
        $(function ($) {
            var spd = 100;
            var spdVal = 5;
            var cntDown = 5 * 60 * spdVal;
            setInterval(function () {
                var mn, sc, ms;
                cntDown--;
                if (cntDown < 0) {
                    return false;
                }
                mn = Math.floor(cntDown / spdVal / 60);
                mn = mn < 10 ? "0" + mn : mn;
                sc = Math.floor((cntDown / spdVal) % 60);
                sc = sc < 10 ? "0" + sc : sc;
                ms = Math.floor(cntDown % spdVal);
                ms = ms < 10 ? "0" + ms : ms;
                var result = mn + ":" + sc;
                if (document.getElementById("countdown")) {
                    document.getElementById("countdown").innerHTML = result;
                }
            }, spd);
        });
    </script>
</body>

</html>