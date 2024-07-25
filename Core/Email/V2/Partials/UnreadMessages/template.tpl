<?php if ($vars['unreadChatRooms']) { ?>
    <p <?= $emailStyles->getStyles('m-title', 'm-fonts'); ?>>
        Unread messages
    </p>

    <div <?= $emailStyles->getStyles('m-spacer--small'); ?>></div>

    <table
        border="0"
        cellpadding="0"
        cellspacing="0"
        <?php echo $emailStyles->getStyles('m-maxWidth--copy'); ?>
    >
        <?php foreach ($vars['unreadChatRooms'] as $unreadChatRoom) { ?>
            <tr>
                <td <?= $emailStyles->getStyles('m-unreadChatMessages__listItem'); ?>>
                    <a href="<?= $unreadChatRoom['room_url']; ?>" target="_blank" style="text-decoration: none;">
                        <table
                            border="0"
                            cellpadding="0"
                            cellspacing="0"
                            <?php echo $emailStyles->getStyles('m-unreadChatMessages__fullWidth'); ?>
                        >
                            <tr <?= $emailStyles->getStyles('m-unreadChatMessages__bordered'); ?>>
                                <!-- Avatar -->
                                <td <?= $emailStyles->getStyles('m-unreadChatMessages__col', 'm-unreadChatMessages__col--first', 'm-unreadChatMessages__avatarCol'); ?>>
                                    <?php foreach ($unreadChatRoom['avatar_urls'] as $avatarUrl) { ?>
                                        <img src="<?= $avatarUrl ?>" alt="User avatar" <?php echo $emailStyles->getStyles('m-unreadChatMessages__avatar'); ?>/>
                                    <?php } ?>
                                </td>
                                <!-- Group name -->
                                <td <?= $emailStyles->getStyles('m-unreadChatMessages__col', 'm-unreadChatMessages__fullWidth', 'm-unreadChatMessages__roomName'); ?>><?= $unreadChatRoom['name']; ?></td>
                                <!-- Unread indicator -->
                                <td <?= $emailStyles->getStyles('m-unreadChatMessages__col', 'm-unreadChatMessages__col--last'); ?>>
                                    <div style="height: 8px; width: 8px; border-radius: 10px; background-color: <?= $vars['unreadIconColor']; ?>;"></div>
                                </td>
                            </tr>
                        </table>
                    </a>
                </td>
            </tr>
        <?php } ?>
    </table>

    <div <?php echo  $emailStyles->getStyles('m-spacer--small'); ?>></div>

    <?= $vars['viewInChatActionButton']; ?>
<?php } ?>