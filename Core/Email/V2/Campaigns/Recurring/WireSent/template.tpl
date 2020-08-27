<?php
    $wireDate = date('l F jS Y', ($vars['timestamp']));
    $amount = $vars['amount'];
    $receiverName = $vars['receiver']->get('name');
    $receiverUsername = $vars['receiver']->get('username');
?>
<tr>
    <td>
        <p>You made a payment to
            <a
                href="<?php echo $vars['site_url']?><?php echo $receiverUsername ?>"
                <?php echo $emailStyles->getStyles('m-link'); ?>>
                 <?php echo $receiverName; ?></a>.
        </p>
    </td>
</tr>
<tr>
    <td>
        <p>
            <div>
                <div style="display: inline-block;"><?= $vars['translator']->trans('Transfer date') ?>: </div>
                <div style="display: inline-block;"><?php echo $wireDate; ?></div>
            </div>
            <div>
                <div style="display: inline-block;"><?= $vars['translator']->trans('Amount') ?>: </div>
                <div style="display: inline-block;"><?php echo $amount; ?></div>
            </div>
        </p>
    </td>
</tr>
<tr>
    <td>
        <p>
            <?= $vars['translator']->trans('For any issues, including the recipient not receiving any payment, please contact us at') ?>
            <a href="mailto:info@minds.com" <?php echo $emailStyles->getStyles('m-link'); ?>>
                info@minds.com</a>.
        </p>
    </td>
</tr>
