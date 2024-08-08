<h1 <?= $emailStylesV2->getStyles(['m-mainContent__h1']); ?>>
    <?= $vars['headerText']; ?>
</h1>

<?= $vars['actionButton']; ?>

<div <?= $emailStyles->getStyles('m-spacer--small'); ?>></div>

<?php if ($vars['unreadMessagesPartial']) { ?>
    <?= $vars['unreadMessagesPartial']; ?>
<?php } ?>
