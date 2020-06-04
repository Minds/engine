<tr>
    <td>
        <p>
            <?= $vars['translator']->trans('Unfortunately, your channel has been banned for violating our Content Policy.') ?>
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
