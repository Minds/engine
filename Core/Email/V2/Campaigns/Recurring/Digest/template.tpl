
<h1 <?= $emailStylesV2->getStyles(['m-mainContent__h1']) ?>>
    <?php echo $vars['headerText']; ?>
</h1>

<?php echo $vars['actionButton']; ?>

<?php if (count($vars['activities']) > 0) { ?>

    <div <?php echo  $emailStyles->getStyles('m-spacer--small'); ?>></div>

    <p
        <?php echo $emailStyles->getStyles('m-title', 'm-fonts'); ?>
    >
        Top Posts
    </p>

    <div <?php echo  $emailStyles->getStyles('m-spacer--small'); ?>></div>

    <table
        border="0"
        cellpadding="0"
        cellspacing="0"
        <?php echo $emailStyles->getStyles('m-maxWidth--copy'); ?>>

        <?php foreach ($vars['activities'] as $activityEntity) { 
            $activity = $activityEntity->export();
        ?>
            <tr>
                <td>
                    <table
                        border="0"
                        cellpadding="0"
                        cellspacing="0"
                        <?php echo $emailStyles->getStyles(
                            'm-maxWidth',
                            'm-digest__activity',
                            isset($vars['color_scheme']) && $vars['color_scheme'] === 'DARK' ? 'm-border--dark' : 'm-border--light'
                        );
                    ?>>
                            <?php if ($activity['thumbnail_src']) { ?>
                                <tr>
                                    <td <?php echo $emailStyles->getStyles('m-clear'); ?> >
                                        <a href="<?php echo $vars['site_url']; ?>newsfeed/<?php echo $activity['guid']; ?>?<?php echo $vars['tracking']; ?>&utm_content=thumbnail">
                                            <img src="<?php echo $activity['thumbnail_src']; ?>" style="width: 100%; max-width: 100%; max-height: 300px; object-fit:cover;" />
                                        </a>
                                    </td>
                                </tr>
                            <?php } elseif ($activity['custom_type'] === 'batch' && is_array($activity['custom_data']) && count($activity['custom_data'])) { ?>
                                <tr>
                                    <td <?php echo $emailStyles->getStyles('m-clear'); ?> >
                                        <a href="<?= $vars['site_url']; ?>newsfeed/<?= $activity['guid']; ?>?<?= $vars['tracking']; ?>&utm_content=thumbnail">
                                            <img src="<?= $activity['custom_data'][0]['src']; ?>" style="width: 100%; max-width: 100%; max-height: 300px; object-fit:cover;" />
                                        </a>
                                    </td>
                                </tr>  
                            <?php } ?>
                            <tr>
                                <td <?php echo $emailStyles->getStyles('m-clear', 'm-digestActivity__body'); ?> >
                                    <table
                                        border="0"
                                        cellpadding="0"
                                        cellspacing="0"
                                        <?php echo $emailStyles->getStyles('m-maxWidth'); ?>>
                                        <tr>
                                            <td <?php echo $emailStyles->getStyles('m-fonts', 'm-clear'); ?> >
                                                <a href="<?php echo $vars['site_url']; ?><?php echo $activity['ownerObj']['username']; ?>?<?php echo $vars['tracking']; ?>&utm_content=avatar"
                                                    <?php echo $emailStyles->getStyles('m-digest__avatar'); ?>
                                                >
                                                    <img
                                                        src="<?php echo $vars['site_url']; ?>icon/<?php echo $activity['ownerObj']['guid'];?>/medium/<?php echo $activity['ownerObj']['icontime'];?>"
                                                        width="16"
                                                        height="16"
                                                        <?php echo $emailStyles->getStyles(
                                                            'm-digest__avatarImg',
                                                            isset($vars['color_scheme']) && $vars['color_scheme'] === 'DARK' ? 'm-border--dark' : 'm-border--light'
                                                        ); ?>
                                                    />
                                                </a>
                                                <a href="<?php echo $vars['site_url']; ?><?php echo $activity['ownerObj']['username']; ?>?<?php echo $vars['tracking']; ?>&utm_content=display_name"
                                                    <?php echo $emailStyles->getStyles('m-digest__name', 'm-textColor--primary', 'm-fonts'); ?>
                                                >
                                                    <?php echo $activity['ownerObj']['name']; ?>
                                                </a>
                                                <span  <?php echo $emailStyles->getStyles('m-digest__username', 'm-textColor--secondary', 'm-fonts'); ?> >@<?php echo $activity['ownerObj']['username']; ?></span>
                                            </td>
                                        </tr>
                                        <tr>
                                            <td <?php echo $emailStyles->getStyles('m-clear', 'm-digestActivity__text', 'm-textColor--primary'); ?> >
                                                <a
                                                    href="<?php echo $vars['site_url']; ?>newsfeed/<?php echo $activity['guid']; ?>?<?php echo $vars['tracking']; ?>&utm_content=body"
                                                    <?= $emailStyles->getStyles('m-noTextDecoration'); ?>
                                                >
                                                    <?php 
                                                        if (!$activity['title'] && $activity['message']) {
                                                            $length = 140;
                                                            $activity['title'] = substr($activity['message'], 0, $length) . (strlen($activity['message']) > 140 ? '...' : '');
                                                        }
                                                        if (!$activity['title'] && $activity['link_title'] ?? null) {
                                                            $activity['title'] = $activity['link_title'];
                                                        }
                                                    ?>
                                                    <?php if ($activity['title']) { ?> 
                                                        <p <?php echo $emailStyles->getStyles('m-fonts', 'm-clear', 'm-preWrap'); ?>><?php echo $activity['title']; ?></p>
                                                    <?php } ?>
                                                </a>
                                            </td>
                                        </tr>
                            </table>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
        <tr>
            <td <?php echo $emailStyles->getStyles('m-spacer--tiny'); ?> >
            </td>
        </tr>
        <?php } ?>
    </table>

<?php } ?>

<?php if ($vars['hasDigestActivity']) { ?>

    <div <?php echo  $emailStyles->getStyles('m-spacer--small'); ?>></div>

    <p
        <?php echo $emailStyles->getStyles('m-title', 'm-fonts'); ?>
    >
        Your activity
    </p>

    <div <?php echo  $emailStyles->getStyles('m-spacer--small'); ?>></div>

        <table
            border="0"
            cellpadding="0"
            cellspacing="0"
            <?php echo $emailStyles->getStyles(
                'm-digest__yourActivity',
                'm-maxWidth--copy',
                isset($vars['color_scheme']) && $vars['color_scheme'] === 'DARK' ? 'm-border--dark' : 'm-border--light'
            ); ?>>

            <tr>
                <td <?php echo $emailStyles->getStyles('m-digestYourActivity__col'); ?> >
                    <a href="<?php echo $vars['site_url']; ?>notifications?<?php echo $vars['tracking']; ?>"
                        <?php echo $emailStyles->getStyles('m-fonts', 'm-link'); ?>
                    >
                        Unread Notifications
                    </a>
                </td>
                <td <?php echo $emailStyles->getStyles('m-digestYourActivity__col'); ?> ><?php echo $vars['unreadNotificationsCount']; ?></td>
            </tr>
        </table>

<?php } ?>

<?php if ($vars['unreadMessagesPartial'] ?? null) { ?>
    <div <?= $emailStyles->getStyles('m-spacer--medium'); ?>></div>
    <?= $vars['unreadMessagesPartial']; ?>
<?php } ?>

<div <?php echo  $emailStyles->getStyles('m-spacer--small'); ?>></div>