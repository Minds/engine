<!--HEADER TEXT-->
<h1 <?= $emailStylesV2->getStyles(['m-mainContent__h1']); ?>>
    <?= $vars['headerText']; ?>
</h1>

<!--BODY TEXT-->
<p <?= $emailStylesV2->getStyles(['m-mainContent__paragraph']) ?> >
    1. Install the Networks Previewer app on your mobile device. Get it on <a href="https://play.google.com/store/apps/details?id=com.minds.mobilepreview&hl=en_US&gl=US" target="_blank" <?= $emailStyles->getStyles('m-link'); ?>>Android</a> or <a href="https://apps.apple.com/us/app/networks-previewer/id6473803640" target="_blank"<?= $emailStyles->getStyles('m-link'); ?>>iOS</a>.
</p>
<p <?= $emailStylesV2->getStyles(['m-mainContent__paragraph']) ?> >
    2. Scan this QR code with the Networks Previewer app, or with the camera app on your mobile device. (If you're reading this email on your mobile device, <a href="<?= $vars['mobileDeepLinkUrl']; ?>" target="_blank" <?= $emailStyles->getStyles('m-link'); ?>>click this link instead</a>.) 
</p>

<!--QR CODE IMG-->
<img width="200" alt="QR Code"  style="text-align: center; margin-top: 20px; margin-bottom: 30px; max-width: 200px;" src="<?= $vars['qrCodeImgSrc']; ?>" />

<p <?= $emailStylesV2->getStyles(['m-mainContent__paragraph']) ?> >
    3. Preview your own app, as it will appear with your current configuration.
</p>