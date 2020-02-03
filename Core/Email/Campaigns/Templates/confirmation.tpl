<table
    width="100%"
    border="0"
    cellpadding="0"
    cellspacing="0"
    style="padding: 73px 0 0; font-family: sans-serif; font-size: 16px; line-height: 22px;"
>
    <tr>
        <td
            style="padding: 49px 83px 38px; font-size: 26px; line-height: 34px; background-color: #10314B; color: #FFFFFF;"
            align="center"
        >
            Welcome
            <span style="color: #B4C8D7;">@<?= $vars['username'] ?></span>
            to the Minds Community
        </td>
    </tr>

    <tr>
        <td>
            <img
                width="100%"
                src="<?= $vars['cdn_assets_url'] . 'assets/email-2020/confirmation-splash.jpg' ?>"
                alt=""
            />
        </td>
    </tr>

    <tr>
        <td style="padding: 40px 0 0; color: #808080;">
            Your journey to taking back control of your social media
            starts today!<br/>
            <br/>
            Please take a moment to verify your email address. This
            will help us verify that you are a real person, and not
            one of those pesky bots.
        </td>
    </tr>

    <tr>
        <td style="padding: 53px 0 0;"
            align="center">
            <a
                href="<?= $vars['confirmation_url'] ?>"
                style="color: #0091FF;"
            >
                <img
                    src="verify-account-btn.png"
                    alt="Verify Account"
                />
            </a>
        </td>
    </tr>

    <tr>
        <td style="padding: 30px 40px 0; word-break: break-all;">
            <a
                href="<?= $vars['confirmation_url'] ?>"
                style="color: #0091FF;"
            >
                <?= $vars['confirmation_url'] ?>
            </a>
        </td>
    </tr>

    <tr>
        <td style="padding: 53px 0 0; color: #808080;">
            Thanks,<br/>
            The Minds Team
        </td>
    </tr>
</table>
