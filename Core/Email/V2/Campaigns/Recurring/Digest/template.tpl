<?php if (count($vars['activities']) > 0) { ?>
    <tr>
        <td>
            <p
                <?php echo $emailStyles->getStyles('m-title--ltr', 'm-fonts'); ?>
            >
                Some highlights for you
            </p>
        </td>
    </tr>
    <tr>
        <td <?php echo $emailStyles->getStyles('m-spacer--tiny'); ?> >
        </td>
    </tr>
    <tr>
        <td <?php echo $emailStyles->getStyles('m-clear'); ?> >
            <table
                border="0"
                cellpadding="0"
                cellspacing="0"
                <?php echo $emailStyles->getStyles('m-maxWidth'); ?>>

                <?php foreach ($vars['activities'] as $activityEntity) { 
                    $activity = $activityEntity->export();
                ?>
                    <tr>
                        <td <?php echo $emailStyles->getStyles('m-clear'); ?> >
                            <table
                                border="0"
                                cellpadding="0"
                                cellspacing="0"
                                <?php echo $emailStyles->getStyles('m-maxWidth'); ?>>
                                <tr>
                                    <td <?php echo $emailStyles->getStyles('m-fonts', 'm-clear'); ?> >
                                        <a href="<?php echo $vars['site_url']; ?><?php echo $activity['ownerObj']['username']; ?>?<?php echo $vars['tracking']; ?>"
                                            <?php echo $emailStyles->getStyles('m-digest__avatar'); ?>
                                        >
                                            <img
                                                src="https://cdn.minds.com/icon/<?php echo $activity['ownerObj']['guid'];?>/medium/<?php echo $activity['ownerObj']['icontime'];?>"
                                                width="30"
                                                height="30"
                                                <?php echo $emailStyles->getStyles('m-digest__avatarImg'); ?>
                                                />
                                        </a>
                                        <a href="<?php echo $vars['site_url']; ?><?php echo $activity['ownerObj']['username']; ?>?<?php echo $vars['tracking']; ?>"
                                            <?php echo $emailStyles->getStyles('m-digest__username', 'm-textColor--primary', 'm-fonts'); ?>
                                        >
                                            <?php echo $activity['ownerObj']['name']; ?>
                                        </a>
                                        - <?php echo date("jS M", $activity['time_created']); ?>
                                    </td>
                                </tr>
                            </table>
                        </td>
                    </tr>
                    <tr>
                        <td <?php echo $emailStyles->getStyles('m-spacer--tiny'); ?> >
                        </td>
                    </tr>
                    <tr>
                        <td <?php echo $emailStyles->getStyles('m-clear'); ?> >
                            <table
                                border="0"
                                cellpadding="0"
                                cellspacing="0"
                                <?php echo $emailStyles->getStyles('m-maxWidth'); ?>>
                                <tr>
                                    <td <?php echo $emailStyles->getStyles('m-clear'); ?> >
                                        <?php 
                                            if (!$activity['title'] && $activity['message']) {
                                                $length = 140;
                                                $activity['title'] = substr($activity['message'], 0, $length) . (strlen($activity['message']) > 140 ? '...' : '');
                                            }
                                        ?>
                                        <?php if ($activity['title']) { ?> 
                                            <h2 <?php echo $emailStyles->getStyles('m-fonts', 'm-clear'); ?>><?php echo $activity['title']; ?></h2>
                                        <?php } ?>
                                    </td>
                                </tr>
                                <?php if ($activity['thumbnail_src']) { ?>
                                    <tr>
                                        <td <?php echo $emailStyles->getStyles('m-spacer--tiny'); ?> >
                                        </td>
                                    </tr>
                                    <tr>
                                        <td <?php echo $emailStyles->getStyles('m-clear'); ?> >
                                            <a href="<?php echo $vars['site_url']; ?>newsfeed/<?php echo $activity['guid']; ?>?<?php echo $vars['tracking']; ?>">
                                                <img src="<?php echo $activity['thumbnail_src']; ?>" style="width: 100%; max-width: 100%;" />
                                            </a>
                                        </td>
                                    </tr>
                                    <tr>
                                        <td <?php echo $emailStyles->getStyles('m-spacer--tiny'); ?> >
                                        </td>
                                    </tr>
                                <?php } ?>

                                <tr>
                                    <td <?php echo $emailStyles->getStyles('m-clear'); ?> >
                                        <a href="<?php echo $vars['site_url']; ?>newsfeed/<?php echo $activity['guid']; ?>?<?php echo $vars['tracking']; ?>"
                                            <?php echo $emailStyles->getStyles('m-fonts', 'm-link'); ?>
                                        >
                                            Read more
                                        </a>
                                    </td>
                                </tr>

                                <tr>
                                    <td <?php echo $emailStyles->getStyles('m-spacer--tiny'); ?> >
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
        </td>
    </tr>
<?php } ?>

<?php if ($vars['hasDigestActivity']) { ?>
    <tr>
        <td>
        <p
            <?php echo $emailStyles->getStyles('m-title--ltr', 'm-fonts'); ?>
        >
            Your activity
        </p>
        </td>
    </tr>

    <tr>
        <td>
            <table
                border="0"
                cellpadding="0"
                cellspacing="0"
                <?php echo $emailStyles->getStyles('m-digest__yourActivity'); ?>>

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
        </td>
    </tr>
<?php } ?>
