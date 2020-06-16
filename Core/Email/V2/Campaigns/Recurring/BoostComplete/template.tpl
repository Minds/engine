<tr>
    <td>
        <p><?= $vars['translator']->trans('Your boost of') ?> <?php echo $vars['boost']['impressions'] ?> <?= $vars['translator']->trans('views is complete.') ?></p>
    </td>
</tr>
<?php echo $vars['actionButton']; ?>
<tr>
    <td>
        <p>
            <?= $vars['translator']->trans('For any issues, please contact us at') ?>
            <a href="mailto:info@minds.com" <?php echo $emailStyles->getStyles('m-link'); ?>>
                info@minds.com</a>.
        </p>
    </td>
</tr>
