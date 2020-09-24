As a Pro user, you earned $<?php echo $vars['usd']; ?> for the referrals and the pageviews your content received last month. Check out more details in your [analytics console](https://minds.com/analytics/dashboard/earnings).

<?php if ($vars['method'] === 'usd') { ?>
This amount will be deposited to your bank account within the next 30 days.
<?php } ?>

<?php if ($vars['method'] === 'eth') { ?>
This amount has been sent to the ETH address you provided us with.
<?php } ?>

Want to earn more? Check out this [blog](https://www.minds.com/minds/blog/how-to-earn-money-with-pro-1046186757943361536). 
