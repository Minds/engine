<?php
    $wireDate = date('l F jS Y', ($vars['timestamp']));
    $amount = $vars['amount'];
    $receiverName = $vars['receiver']->get('name');
?>
<tr>
    <td>
        <p>You made a payment to
            <a
                href="<?php echo $vars['site_url']?><?php echo $receiverName ?>"
                <?php echo $emailStyles->getStyles('m-link'); ?>>
                 @<?php echo $receiverName; ?></a>.
        </p>
    </td>
</tr>
<tr>
    <td>
        <p>
            <div>
                <div style="display: inline-block;">Transfer date: </div>
                <div style="display: inline-block;"><?php echo $wireDate; ?></div>
            </div>
            <div>
                <div style="display: inline-block;">Amount: </div>
                <div style="display: inline-block;"><?php echo $amount; ?></div>
            </div>
        </p>
    </td>
</tr>
<tr>
    <td>
        <p>
            For any issues, including the recipient not receiving your payment, please contact us at
            <a href="mailto:info@minds.com" <?php echo $emailStyles->getStyles('m-link'); ?>>
                info@minds.com</a>.
        </p>
    </td>
</tr>
