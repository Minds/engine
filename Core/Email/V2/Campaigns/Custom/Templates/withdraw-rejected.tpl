<tr>
    <td>
        <p>
            Your on-chain transfer request has been rejected. Your <?php echo $vars['amount']; ?> off-chain token(s) were refunded.
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
