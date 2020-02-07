<html xmlns="http://www.w3.org/1999/xhtml">
<head>
    <meta http-equiv="Content-Type"
        content="text/html; charset=UTF-8"/>
    <title></title>
</head>

<body style="margin:0; padding:0;">
<!-- WRAPPER TABLE FOR CENTERING -->
<table
    width="100%"
    border="0"
    cellspacing="0"
    cellpadding="0"
>
    <tr>
        <td align="center">
            <!-- CONTAINER -->
            <table
                width="488"
                border="0"
                cellspacing="0"
                cellpadding="0"
                style="width: 100%; max-width: 488px; padding: 73px 0 50px; font-family: sans-serif; font-size: 16px; line-height: 22px;"
            >
                <tbody>
                <tr>
                    <td align="center">
                        <!-- LOGO -->
                        <img
                            src="<?php echo $vars['cdn_assets_url'] . 'assets/email-2020/logo.svg' ?>"
                            width="116"
                            height="43"
                            alt="Minds"
                        />
                    </td>
                </tr>
                <tr>
                    <td>
                        <!-- INNER CONTENT -->
                        <?php echo $vars['body'] ?>
                    </td>
                </tr>

                <tr>
                    <td style="padding: 38px 0 0;">
                        <img
                            src="<?php echo $vars['cdn_assets_url'] . 'assets/email-2020/sep.png' ?>"
                            alt=""
                        />
                    </td>
                </tr>

                <!-- MOBILE APP LINKS -->
                <tr>
                    <td
                        style="padding: 60px 0 0; font-size: 22px; line-height: 29px; color: #5B5B5B;"
                        align="center"
                    >
                        Download the Minds App Today!
                    </td>
                </tr>

                <tr>
                    <td
                        style="padding: 35px 0 0; font-size: 13px; line-height: 18px;"
                        align="center"
                    >
                        <a
                            href="<?php echo $vars['site_url'] . 'mobile?__e_ct_guid=' . $vars['guid'] ?>"
                            style="color: #0091FF; text-decoration: none;"
                        >
                            <img
                                src="<?php echo $vars['cdn_assets_url'] . 'assets/email-2020/dl-android-app.png' ?>"
                                alt="Download Android App"
                                style="display: inline-block;"
                            />
                        </a>
                        &nbsp;
                        <a
                            href="https://itunes.apple.com/us/app/minds-com/id961771928?ls=1&mt=8"
                            style="color: #0091FF; text-decoration: none;"
                        >
                            <img
                                src="<?php echo $vars['cdn_assets_url'] . 'assets/email-2020/dl-ios-app.png' ?>"
                                alt="Download on the Apple App Store"
                                style="display: inline-block;"
                            />
                        </a>
                    </td>
                </tr>

                <tr>
                    <td style="padding: 75px 0 0;">
                        <img
                            src="<?php echo $vars['cdn_assets_url'] . 'assets/email-2020/sep.png' ?>"
                            alt=""
                        />
                    </td>
                </tr>

                <tr>
                    <td
                        style="padding: 38px 0 0; font-size: 13px; line-height: 18px; color: #808080;"
                    >
                        Minds, Inc. &copy; <?php echo date('Y') ?>
                        &nbsp;
                        <a href="<?php echo $vars['site_url'] . 'settings/emails?' . $vars['tracking'] ?>" style="color: #0091FF;">Manage email settings</a>
                    </td>
                </tr>

                <tr>
                    <td
                        style="padding: 12px 0 0; font-size: 13px; line-height: 18px; color: #808080;"
                    >
                        <a href="<?php echo $vars['site_url'] . sprintf(
                            'emails/unsubscribe/%s/%s/%s/%s?%s',
                            $vars['username'],
                            $vars['email'],
                            $vars['campaign'],
                            $vars['topic'],
                            $vars['tracking']
                        ) ?>" style="color: #0091FF;">Unsubscribe</a> from this type of email
                    </td>
                </tr>

                <tr>
                    <td
                        style="padding: 12px 0 0; font-size: 13px; line-height: 18px; color: #808080;"
                    >
                        Sent you from Minds, Inc.
                    </td>
                </tr>
                </tbody>
            </table>
        </td>
    </tr>
</table>
</body>
</html>
