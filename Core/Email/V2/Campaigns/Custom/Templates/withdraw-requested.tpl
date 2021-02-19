<tr>
    <td>
        <p>
            Your on-chain transfer request of <?php echo $vars['amount']; ?> token(s) was submitted successfully.
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
