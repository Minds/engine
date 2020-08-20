<tr>
    <td>
        <p>
            <?= $vars['translator']->trans('Unfortunately, your channel has been banned for violating our ') ?>
            <a href="https://minds.com/content-policy"<?php echo $emailStyles->getStyles('m-link'); ?>>
                <?= $vars['translator']->trans('Content Policy') ?>
            </a>
            <?= $vars['translator']->trans('. The specific reason is: ') ?><?= $vars['translator']->trans($vars['reason']) ?>.
        </p>
    </td>
</tr>
<tr>
    <td>
        <p>
            <?= $vars['translator']->trans('If you wish to appeal further, you may contact us with any supporting information at ') ?>
            <a href="mailto:info@minds.com" <?php echo $emailStyles->getStyles('m-link'); ?>>info@minds.com</a>
            <?= $vars['translator']->trans(' for our review.') ?>
        </p>
    </td>
</tr>
