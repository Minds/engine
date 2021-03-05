<tr>
    <td>
        <p>
            Your on-chain transfer request of <?php echo $vars['amount']; ?> token(s) was submitted successfully. Assuming your channel has not violated the terms, you should receive confirmation within 24 hours.
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
