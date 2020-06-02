<tr>
    <td>
        <p>
            <?= $vars['translator']->trans('Your') ?> <?= $vars['translator']->trans($vars['type']) ?>
            <?= $vars['translator']->trans('has been') ?>
            <b><?= $vars['translator']->trans($vars['action']) ?></b> <?= $vars['translator']->trans('because it was determined to violate our Content Policy.') ?>
            <?= $vars['translator']->trans('To appeal this decision to a jury of your peers, please') ?> <a href="https://www.minds.com/settings/reported-content" target="_blank" <?php echo $emailStyles->getStyles('m-link'); ?>><?= $vars['translator']->trans('log in') ?></a> <?= $vars['translator']->trans('and submit an appeal to a jury of your peers from the Reported Content section of your settings.') ?>
        </p>
    </td>
</tr>
<tr>
    <td>
        <p>
            <?= $vars['translator']->trans('More can be learned about how the Appeals process works') ?> <a href="https://www.minds.com/content-policy" target="_blank" <?php echo $emailStyles->getStyles('m-link'); ?>><?= $vars['translator']->trans('here') ?></a>.
        </p>
    </td>
</tr>
