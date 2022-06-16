<!DOCTYPE html>
<html xml:lang="en" lang="en" xmlns="http://www.w3.org/1999/xhtml"
>

<head>
    <!--Help character display properly.-->
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8">
    <!--Set the initial scale of the email.-->
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <!--Force Outlook clients to render with a better MS engine.-->
    <meta http-equiv="X-UA-Compatible" content="IE=Edge">
    <!--Help prevent blue links and autolinking-->
    <meta name="format-detection" content="telephone=no, date=no, address=no, email=no">
    <!--prevent Apple from reformatting and zooming messages.-->
    <meta name="x-apple-disable-message-reformatting">

    <!--target dark mode-->
    <meta name="color-scheme" content="light dark">
    <meta name="supported-color-schemes" content="light dark only">

    <!-- Allow for better image rendering on Windows hi-DPI displays. -->
    <!--[if mso]>
    <noscript>
        <xml>
            <o:OfficeDocumentSettings>
                <o:AllowPNG/>
                <o:PixelsPerInch>96</o:PixelsPerInch>
            </o:OfficeDocumentSettings>
        </xml>
    </noscript>
    <![endif]-->

    <!--to support dark mode meta tags-->
    <style type="text/css">
        :root {
            color-scheme: light dark;
            supported-color-schemes: light dark;
        }
    </style>

    <!--webfont code goes here-->
    <!--[if (gte mso 9)|(IE)]><!-->
    <!--webfont <link /> goes here-->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;700;800&display=swap" rel="stylesheet">
    <style>
        /*Web font override styles go here
         h1, h2, h3, h4, h5, p, a, img, span, ul, ol, li { font-family: 'webfont name', Arial, Helvetica, sans-serif !important; } */
    </style>
    <!--<![endif]-->

    <style type="text/css">
        .body-fix {
            height: 100% !important;
            margin: 0 auto !important;
            padding: 0 !important;
            width: 100% !important;
            -webkit-text-size-adjust: 100%;
            -ms-text-size-adjust: 100%;
            -webkit-font-smoothing: antialiased;
            word-spacing: normal;
        }

        div[style*="margin:16px 0"] {
            margin: 0 !important;
        }

        table,
        td {
            border-collapse: collapse !important;
            mso-table-lspace: 0pt;
            mso-table-rspace: 0pt;
            -webkit-text-size-adjust: 100%;
            -ms-text-size-adjust: 100%;
        }

        img {
            border: 0;
            line-height: 100%;
            outline: none;
            text-decoration: none;
            display: block;
        }

        p,
        h1,
        h2,
        h3 {
            padding: 0;
            margin: 0;
        }

        a[x-apple-data-detectors] {
            color: inherit !important;
            text-decoration: none !important;
            font-size: inherit !important;
            font-family: inherit !important;
            font-weight: inherit !important;
            line-height: inherit !important;
        }

        u + #body a {
            color: inherit;
            text-decoration: none;
            font-size: inherit;
            font-family: inherit;
            font-weight: inherit;
            line-height: inherit;
        }

        #MessageViewBody a {
            color: inherit;
            text-decoration: none;
            font-size: inherit;
            font-family: inherit;
            font-weight: inherit;
            line-height: inherit;
        }

        .link:hover {
            text-decoration: none !important;
        }

        .fadeimg {
            transition: 0.3s !important;
            opacity: 1 !important;
        }

        .fadeimg:hover {
            transition: 0.3s !important;
            opacity: 0.5 !important;
        }

        /* start CTA HOVER EFFECTS */
        .cta {
            transition: 0.1s !important;
        }

        .cta span {
            transition: 0.1s !important;
        }

        .cta:hover {
            transition: 0.2s !important;
            background-color: #197CBE !important;
            transform: scale(1.05);
        }

        .cta:hover span {
            transition: 0.1s !important;
        }

        .cta-border:hover {
            border-bottom: 3px solid transparent !important;
        }

        /* end CTA HOVER EFFECTS */

        .mobile {
            display: none;
        }


    </style>

    <!--mobile styles-->
    <style>
        @media screen and (max-width: 600px) {
            .wMobile {
                width: 95% !important;
            }

            .wInner {
                width: 90% !important;
            }

            .wFull {
                width: 100% !important;
            }

            .imgFull {
                width: 100% !important;
                height: auto !important;
            }

            .desktop {
                width: 0 !important;
                display: none !important;
            }

            .mobile {
                display: block !important;
            }

            .std {
                font-size: 18px !important;
                line-height: 28px !important;
            }

            .tPad-0 {
                padding-top: 0 !important;
            }
        }
    </style>

    <!--dark mode styles-->
    <!--these are just example classes that can be used.-->
    <style>
        @media (prefers-color-scheme: dark) {

            /* Shows Dark Mode-Only Content, Like Images */
            .dark-img {
                display: block !important;
                width: auto !important;
                overflow: visible !important;
                float: none !important;
                max-height: inherit !important;
                max-width: inherit !important;
                line-height: auto !important;
                margin-top: 0px !important;
                visibility: inherit !important;
            }

            /* Hides Light Mode-Only Content, Like Images */
            .light-img {
                display: none;
                display: none !important;
            }

            /* Custom Dark Mode Background Color */
            .darkmode {
                background-color: #100E11 !important;
            }

            .darkmode2 {
                background-color: #000000 !important;
            }

            /* Custom Dark Mode Font Colors */
            h1, h2, h3, p, span, a, li {
                color: #fdfdfd !important;
            }


            /* Custom Dark Mode Text Link Color */
            .link {
                color: #028383 !important;
            }

            .footer a.link {
                color: #fdfdfd !important;
            }
        }

        /* Copy dark mode styles for android support */
        /* Shows Dark Mode-Only Content, Like Images */
        [data-ogsc] .dark-img {
            display: block !important;
            width: auto !important;
            overflow: visible !important;
            float: none !important;
            max-height: inherit !important;
            max-width: inherit !important;
            line-height: auto !important;
            margin-top: 0px !important;
            visibility: inherit !important;
        }

        /* Hides Light Mode-Only Content, Like Images */
        [data-ogsc] .light-img {
            display: none;
            display: none !important;
        }

        /* Custom Dark Mode Background Color */
        [data-ogsc] .darkmode {
            background-color: #F5F5F5 !important;
        }

        [data-ogsc] .darkmode2 {
            background-color: #000000 !important;
        }

        /* Custom Dark Mode Font Colors */
        [data-ogsc] h1, [data-ogsc] h2, [data-ogsc] h3, [data-ogsc] p, [data-ogsc] span, [data-ogsc] a, [data-ogsc] li {
            color: #fdfdfd !important;
        }

        /* Custom Dark Mode Text Link Color */
        [data-ogsc] .link {
            color: #028383 !important;
        }

        [data-ogsc] .footer a.link {
            color: #fdfdfd !important;
        }
    </style>

    <!--correct superscripts in Outlook-->
    <!--[if (gte mso 9)|(IE)]>
        <style>
          sup{font-size:100% !important;}
        </style>
        <![endif]-->
    <title></title>


</head>

<body id="body" class="darkmode body body-fix" bgcolor="#ffffff" style="background-color:#ffffff;">
    <div role="article" aria-roledescription="email" aria-label="Email from Minds" xml:lang="en" lang="en">
        <?php
        if ($vars['preheader']) {
            ?>
            <!--hidden preheader with preh-header spacer hack-->
            <div class="litmus-builder-preview-text" style="display: none;">
                <?php echo $vars['preheader']; ?>
            </div>
            <?php
        }
        ?>
        <!--start of email-->
        <table class="darkmode" bgcolor="#eeeeee" cellpadding="0" cellspacing="0" border="0" role="presentation" style="width: 100%;" >


            <!--main content area-->
            <tr>
                <td class="tPad-0" align="center" valign="top" style="padding-top: 20px;">
                    <table class="wFull darkmode2" cellpadding="0" cellspacing="0" border="0" role="presentation"
                           <?php echo $emailStylesV2->getStyles(['m-mainContent']); ?> >

                        <!--header-->
                        <tr>
                            <td align="center" valign="top" <?= $emailStylesV2->getStyles(['m-mainContent__header']) ?>>
                                <!--light mode logo image-->
                                <a href="<?php echo $vars['site_url']; ?>?utm_medium=email&utm_source=verify&utm_content=logo&__e_ct_guid=<?= $vars['guid']?>"
                                   target="_blank">
                                   <img class="light-img" src="<?php echo $vars['cdn_assets_url']; ?>/assets/logos/logo-light-mode.png" width="130" height="50"
                                        alt="Minds"
                                        <?= $emailStylesV2->getStyles(['m-mainContent__imageAltText']) ?> >

                                    <!--dark mode logo image-->
                                    <!--[if !mso]><! -->
                                    <div class="dark-img"
                                         <?= $emailStylesV2->getStyles(['dark-img']) ?> 
                                         align="center">
                                        <img src="<?php echo $vars['cdn_assets_url']; ?>/assets/logos/logo-dark-mode.png" width="130" height="50" alt="Minds"
                                            <?= $emailStylesV2->getStyles(['m-mainContent__imageAltText']) ?>
                                            border="0"/>
                                    </div>
                                    <!--<![endif]--></a>
                            </td>
                        </tr>

                        <!-- Start Main Article -->
                        <tr>
                            <td class="darkmode2" align="center" valign="top" <?= $emailStylesV2->getStyles(['m-mainContent__mainArticle']) ?> >
                                <?= $vars['body'] ?>
                            </td>
                        </tr>
                    </table>
                </td>
            </tr>

            <!--footer-->
            <tr>
                <td class="footer" align="center" valign="top" <?= $emailStylesV2->getStyles(['m-footer']) ?> >
                    <p <?= $emailStylesV2->getStyles(['m-footer__paragraph']) ?>>
                        Minds Inc © 2021 - PO Box 7681, Wilton, CT 06897<br><br>
                        <a href="<?php echo $vars['site_url']; ?>settings/canary/account/email-notifications" class="link" target="_blank"
                           <?= $emailStylesV2->getStyles(['m-footer__link']) ?>>
                            <?= $vars['translator']->trans('Manage email settings') ?>
                        </a>
                        <?php
                        if (isset($vars['campaign'])) {
                            ?>
                            &nbsp;&nbsp;|&nbsp;&nbsp;
                            <a href="<?php echo $vars['site_url']; ?>emails/unsubscribe/<?= $vars['guid']?>/<?= urlencode($vars['email'])?>/<?= $vars['campaign']?><?= '/' . $vars['topic']?>?__e_ct_guid=<?= $vars['guid']?>&campaign=<?= $vars['campaign'] ?>&topic=<?= $vars['topic'] ?>&state=<?= $vars['state']?>"
                               class="link"
                               target="_blank"
                               <?= $emailStylesV2->getStyles(['m-footer__link']) ?>>
                                <?= $vars['translator']->trans('Unsubscribe') ?>
                            </a>
                            <?php
                        }
                        ?>
                    </p>
                </td>
            </tr>

        </table>
    </div>

<!--analytics-->

</body>

</html>
