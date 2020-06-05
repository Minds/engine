<tr>
    <td>
        <p>
            <?= $vars['translator']->trans('Thank you for your purchase! We sincerely appreciate your support. Your tokens have now been issued.') ?>
        </p>
    </td>
</tr>
<tr>
    <td>
        <p>
            <div><?= $vars['translator']->trans('Date') ?>: <?php echo $vars['date']; ?></div>
            <div><?= $vars['translator']->trans('Token Amount') ?>: <?php echo $vars['amount']; ?></div>
        </p>
    </td>
</tr>
<tr>
    <td>
        <p>
            <?= $vars['translator']->trans('Remember, you can manage your subscriptions and payment methods in your billing settings.') ?>
        </p>
    </td>
</tr>
<tr>
    <td>
        <p>
            <?= $vars['translator']->trans('For any issues, please contact us at') ?>
            <a href="mailto:info@minds.com" <?php echo $emailStyles->getStyles('m-link'); ?>>
                info@minds.com</a>.
        </p>
    </td>
</tr>
