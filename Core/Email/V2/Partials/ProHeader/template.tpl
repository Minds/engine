<table
    border="0"
    cellpadding="0"
    cellspacing="0"
    width="100%"
    class="m-wrapper"
    <?php echo $emailStyles->getStyles('m-maxWidth'); ?> >
    <tr>
        <td align="center" valign="top" style="padding: 65px 0 38px 0;">
            <a href="<?php echo $vars['site_url']; ?>?<?= $vars['tracking']?>"  target="_blank">
                <img 
                    src="<?php echo $vars['logo_uri'] ?: $vars['cdn_assets_url'] . 'assets/logos/logo-email.png'; ?>"
                    alt="Logo"
                    style="display: block; width: 168px;"
                    border="0"
                />
            </a>
        </td>
    </tr>
</table>
