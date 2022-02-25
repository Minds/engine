<html xmlns="http://www.w3.org/1999/xhtml">
  <head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <meta http-equiv="X-UA-Compatible" content="IE=edge" />
    <meta name="x-apple-disable-message-reformatting" />
    <title></title>
    <style>
      /* RESET STYLES*/

      /* Pad <td> instead of <p> b/c Outlook */
      #tbody tr td {
        padding: 10px 0;
      }

      p, li {
        margin: 0 !important;
        font-family: Roboto, Helvetica Neue, Helvetica, Arial, sans-serif !important;
        font-size:16px;
        line-height:22px;
        text-align:left;
        color: #4f4f50;
      }
      li {
        margin-bottom: 16px !important;
      }
      ul {
        padding-left: 16px !important;
      }
      img {
        border: 0;
        height: auto;
        line-height: 100%;
        outline: none;
        text-decoration: none;
      }
      table {
        border-collapse: collapse !important;
      }
      body {
        height: 100% !important;
        margin: 0 !important;
        padding: 0 !important;
        width: 100% !important;
      }



      /* iOS BLUE LINKS */
      a[x-apple-data-detectors] {
        color: inherit !important;
        text-decoration: none !important;
        font-size: inherit !important;
        font-family: inherit !important;
        font-weight: inherit !important;
        line-height: inherit !important;
      }

      /* ANDROID CENTER FIX */
      div[style*='margin: 16px 0;'] {
        margin: 0 !important;
      }

      /* CLIENT-SPECIFIC STYLES */
      body,
      table,
      td,
      a {
        -webkit-text-size-adjust: 100%;
        -ms-text-size-adjust: 100%;
      } /* Prevent WebKit and Windows mobile changing default text sizes */
      table,
      td {
        mso-table-lspace: 0pt;
        mso-table-rspace: 0pt;
      } /* Remove spacing between tables in Outlook 2007 and up */
      img {
        -ms-interpolation-mode: bicubic;
      } /* Allow smoother rendering of resized image in Internet Explorer */

      /* MOBILE-ONLY STYLES */
      @media screen and (max-width: 525px) {
        /* ALLOWS FOR FLUID TABLES */
        .m-wrapper {
          width: 100% !important;
          max-width: 100% !important;
        }

        /* USE THESE CLASSES TO HIDE CONTENT ON MOBILE */
        .m-mobileHide {
          display: none !important;
        }

        .m-imgMax {
          max-width: 100% !important;
          width: 100% !important;
          height: auto !important;
        }

        /* FULL-WIDTH TABLES */
        .m-responsiveTable {
          width: 90% !important;
        }

        /* ADJUST BUTTONS ON MOBILE */
        .m-mobileButtonContainer {
          margin: 0 auto;
          width: 100% !important;
        }

        .m-mobileButton {
          padding: 15px !important;
          border: 0 !important;
          font-size: 18px !important;
          display: block !important;
        }
      }
    </style>
  </head>
  <body style="margin: 0; padding: 0;">
    <!------------------------------>
    <!-- HIDDEN PREHEADER TEXT -->
    <!------------------------------>
        <?php if ($vars['preheader']): ?>
          <div
            style="display: none; font-size: 1px; color: #fefefe; line-height: 1px; font-family: Roboto, Helvetica, Arial, sans-serif; max-height: 0px; max-width: 0px; opacity: 0; overflow: hidden;"
          >
            <?php echo $vars['preheader'] ?>
          </div>
        <?php endif; ?>


      <table border="0" cellpadding="0" cellspacing="0" width="100%" style="max-width:100%">
      <!------------------------------>
      <!-- HEADER: Start -->
      <!------------------------------>
      <thead>
        <!------------------------------>
        <!-- LOGO: Start -->
        <!------------------------------>
        <tr>
          <td bgcolor="#ffffff" align="center">
            <!--[if (gte mso 9)|(IE)]>
                <table align="center" border="0" cellspacing="0" cellpadding="0" width="600">
                <tr>
                <td align="center" valign="top" width="600">
                <![endif]-->
            <?php if (isset($vars['custom_header'])) { echo $vars['custom_header']; } else { ?>
              <table
                border="0"
                cellpadding="0"
                cellspacing="0"
                width="100%"
                class="m-wrapper"
                <?php echo $emailStyles->getStyles('m-maxWidth'); ?> >
                <tr>
                  <td align="center" valign="top" style="padding: 65px 0 38px 0;">
                      <a href="<?php echo $vars['site_url']; ?>?__e_ct_guid=<?= $vars['guid']?>"  target="_blank">
                      <img src="<?php echo $vars['cdn_assets_url']; ?>assets/logos/logo-email.png" alt="Logo" style="display: block; width: 168px;max-height:80px;" border="0"
                      />
                    </a>
                  </td>
                </tr>
              </table>
            <?php } ?>

            <!--[if (gte mso 9)|(IE)]>
                </td>
                </tr>
                </table>
                <![endif]-->
          </td>
        </tr>
        <!------------------------------>
        <!-- LOGO: End -->
        <!------------------------------>

        <?php if ($vars['title']): ?>
        <!------------------------------>
        <!-- TITLE: Start -->
        <!------------------------------>
        <tr>
          <td bgcolor="#ffffff" align="center">
            <!--[if (gte mso 9)|(IE)]>
                <table align="center" border="0" cellspacing="0" cellpadding="0" width="600">
                <tr>
                <td align="center" valign="top" width="600">
                <![endif]-->
            <table
              border="0"
              cellpadding="0"
              cellspacing="0"
              width="100%"
              class="m-responsiveTable"
              <?php echo $emailStyles->getStyles('m-maxWidth'); ?> >
              <tr>
                <td>
                  <table
                    width="100%"
                    border="0"
                    cellspacing="0"
                    cellpadding="0">
                    <tr>
                      <td style="padding-bottom: 48px;">
                        <p
                          <?php echo $emailStyles->getStyles('m-title', 'm-fonts'); ?>
                        >
                          <?php echo $vars['title'] ?>
                        </p>
                      </td>
                    </tr>
                  </table>

                  <!--[if (gte mso 9)|(IE)]>
                      </td>
                      </tr>
                      </table>
                      <![endif]-->
                </td>
              </tr>
            </table>
          </td>
        </tr>
        <!------------------------------>
        <!-- TITLE: End -->
        <!------------------------------>
        <!------------------------------>
        <!-- BORDER/SPACER: Start -->
        <!------------------------------>
        <tr>
          <td bgcolor="#ffffff" align="center">
            <!--[if (gte mso 9)|(IE)]>
                <table align="center" border="0" cellspacing="0" cellpadding="0" width="600">
                <tr>
                <td align="center" valign="top" width="600">
                <![endif]-->
            <table
              border="0"
              cellpadding="0"
              cellspacing="0"
              width="100%"
              class="m-responsiveTable"
              <?php echo $emailStyles->getStyles('m-maxWidth'); ?> >
              <tr>
                <td <?php echo $emailStyles->getStyles('m-spacer--medium', 'm-borderTop'); ?> >
                </td>
              </tr>
            </table>
          </td>
        </tr>
        <!------------------------------>
        <!-- BORDER/SPACER: End -->
        <!------------------------------>
        <?php endif; ?>
      </thead>
      <!------------------------------>
      <!-- HEADER: End -->
      <!------------------------------>
      <!------------------------------>
      <!-- BODY: Start -->
      <!------------------------------>
        <tbody id="tbody" <?php echo $emailStyles->getStyles('m-copy'); ?>>
          <tr>
            <td bgcolor="#ffffff" align="center">
              <!--[if (gte mso 9)|(IE)]>
                  <table align="center" border="0" cellspacing="0" cellpadding="0" width="600">
                  <tr>
                  <td align="center" valign="top" width="600">
                  <![endif]-->
              <table
                border="0"
                cellpadding="0"
                cellspacing="0"
                class="m-responsiveTable"
                <?php echo $emailStyles->getStyles('m-maxWidth--copy'); ?>>
                <!------------------------------>
                <!-- GREETING: Start -->
                <!------------------------------>
                  <?php if (!($vars['hideGreeting'] ?? false)) { ?>
                    <tr>
                      <td>
                        <p <?php echo $emailStyles->getStyles('m-copy'); ?> >
                          <?= $vars['translator']->trans('Hi') ?> @<?php echo $vars['username'] ?>,
                        </p>
                      </td>
                    </tr>
                  <?php } ?>
                <!------------------------------>
                <!-- GREETING: End -->
                <!------------------------------>
                <!------------------------------>
                <!-- TEMPLATE: Start -->
                <!------------------------------>
                  <?php echo $vars['body'] ?>
                <!------------------------------>
                <!-- TEMPLATE: End -->
                <!------------------------------>
                <!------------------------------>
                <!-- SIGNATURE: Start -->
                <!------------------------------>
                  <tr>
                    <td s<?php echo $emailStyles->getStyles('m-spacer--small'); ?>></td>
                  </tr>
                  <tr>
                    <td>
                      <?php if ($vars['signoff'] ?? null): ?>
                        <p <?php echo $emailStyles->getStyles('m-copy', 'm-signature'); ?>>
                          <?php echo $vars['signoff'] ?>
                        </p>
                      <?php endif; ?>
                      <p <?php echo $emailStyles->getStyles('m-copy', 'm-signature', 'm-textColor--secondary'); ?> > The Minds Team
                      </p>
                    </td>
                  </tr>

                  <tr>
                    <td <?php echo $emailStyles->getStyles('m-spacer--medium'); ?>>
                    </td>
                  </tr>
                <!------------------------------>
                <!-- SIGNATURE: End -->
                <!------------------------------>
              </table>
              <!--[if (gte mso 9)|(IE)]>
                  </td>
                  </tr>
                  </table>
                  <![endif]-->
              </td>
            </tr>
        </tbody>
      <!------------------------------>
      <!-- BODY: End -->
      <!------------------------------>
      <!------------------------------>
      <!-- FOOTER: Start -->
      <!------------------------------>
        <tfoot>
          <tr>
            <td bgcolor="#ffffff" align="center">
              <!--[if (gte mso 9)|(IE)]>
                  <table align="center" border="0" cellspacing="0" cellpadding="0" width="600">
                  <tr>
                  <td align="center" valign="top" width="600">
                  <![endif]-->
              <table
                border="0"
                cellpadding="0"
                cellspacing="0"
                width="100%"
                <?php echo $emailStyles->getStyles('m-maxWidth'); ?> class="m-responsiveTable" >
                <!------------------------------>
                <!-- BORDER/SPACER: Start -->
                <!------------------------------>
                <?php if (!isset($vars['hideDownloadLinks']) || (!$vars['hideDownloadLinks'])): ?>
                  <tr>
                    <td <?php echo $emailStyles->getStyles('m-spacer--large', 'm-borderTop'); ?> >
                    </td>
                  </tr>
                  <!------------------------------>
                  <!-- BORDER/SPACER: End -->
                  <!------------------------------>
                  <!------------------------------>
                  <!-- DOWNLOAD APP: Start -->
                  <!------------------------------>
                  <tr>
                    <td>
                      <table
                        width="100%"
                        border="0"
                        cellspacing="0"
                        cellpadding="0"
                      >
                        <tr>
                          <td
                            align="center"
                            style="
                              font-weight: bold;
                              text-align: center;
                              font-family: Roboto, Helvetica, sans-serif;
                              color: #4f4f50;
                              font-size: 22px;
                            "
                          >
                            <?= $vars['translator']->trans('Download the Minds app today!') ?>
                          </td>
                        </tr>
                        <tr>
                          <td>
                            <!------------------------------>
                            <!-- TWO COLUMNS : DOWNLOAD BUTTONS -->
                            <!------------------------------>
                            <tr>
                              <td>
                                <table
                                  cellspacing="0"
                                  cellpadding="0"
                                  border="0"
                                  width="100%"
                                >
                                  <tr>
                                    <td <?php echo $emailStyles->getStyles('m-spacer--small'); ?>>
                                    </td>
                                  </tr>
                                  <tr>
                                    <td valign="top">
                                      <!------------------------------>
                                      <!-- LEFT COLUMN -->
                                      <!------------------------------>
                                      <table
                                        cellpadding="0"
                                        cellspacing="0"
                                        border="0"
                                        width="47%"
                                        style="width: 47%;"
                                        align="left"
                                      >
                                        <tr>
                                          <td>
                                            <table
                                              cellpadding="0"
                                              cellspacing="0"
                                              border="0"
                                              width="100%"
                                            >
                                              <tr>
                                                <td align="right">
                                                  <a href="<?php echo $vars['site_url']; ?>/mobile"
                                                    target="_blank"
                                                    style="text-decoration: none;"
                                                  >
                                                    <img
                                                    src="<?php echo $vars['cdn_assets_url']; ?>assets/homepage/android.png" style="width: 142px;max-height:44px;" alt="Google Play"
                                                    />
                                                  </a>
                                                </td>
                                              </tr>
                                            </table>
                                          </td>
                                        </tr>
                                      </table>

                                      <!------------------------------>
                                      <!-- RIGHT COLUMN -->
                                      <!------------------------------>
                                      <table
                                        cellpadding="0"
                                        cellspacing="0"
                                        border="0"
                                        width="47%"
                                        style="width: 47%;"
                                        align="right"
                                      >
                                        <tr>
                                          <td>
                                            <table
                                              cellpadding="0"
                                              cellspacing="0"
                                              border="0"
                                              width="100%"
                                            >
                                              <tr>
                                                <td align="left">
                                                  <a
                                                    href="https://itunes.apple.com/us/app/minds-com/id961771928?ls=1&mt=8"
                                                    target="_blank"
                                                    style="text-decoration: none;"
                                                  >
                                                    <img
                                                      src="<?php echo $vars['cdn_assets_url']; ?>assets/homepage/app-store.png" style="width: 142px;max-height:44px;"
                                                      alt="Apple App Store"
                                                    />
                                                  </a>
                                                </td>
                                              </tr>
                                            </table>
                                          </td>
                                        </tr>
                                      </table>
                                    </td>
                                  </tr>
                                </table>
                              </td>
                            </tr>
                          </td>
                        </tr>
                      </table>
                    </td>
                  </tr>
                  <!------------------------------>
                  <!-- DOWNLOAD APP: End -->
                  <!------------------------------>
                  <!------------------------------>
                  <!-- BORDER/SPACER: Start -->
                  <!------------------------------>
                  <tr>
                    <td <?php echo $emailStyles->getStyles('m-spacer--large'); ?>>
                    </td>
                  </tr>
                <?php endif; ?>
                <tr>
                  <td <?php echo $emailStyles->getStyles('m-spacer--small', 'm-borderTop'); ?> >
                  </td>
                </tr>
                <!------------------------------>
                <!-- BORDER/SPACER: End -->
                <!------------------------------>
                <!------------------------------>
                <!-- FOOTER LINKS: Start -->
                <!------------------------------>
                <tr>
                  <td bgcolor="#ffffff" align="center">
                    <!--[if (gte mso 9)|(IE)]>
                    <table align="center" border="0" cellspacing="0" cellpadding="0" width="600">
                    <tr>
                    <td align="center" valign="top" width="600">
                    <![endif]-->
                    <table
                      border="0"
                      cellpadding="0"
                      cellspacing="0"
                      width="95%"
                      class="m-responsiveTable" >
                      <!------------------------------>
                      <!-- TWO COLUMNS : FOOTER LINKS -->
                      <!------------------------------>

                      <tr>
                        <td>
                          <table
                            cellspacing="0"
                            cellpadding="0"
                            border="0"
                            width="100%"
                          >
                            <tr>
                              <td valign="top">
                                <!------------------------------>
                                <!-- LEFT COLUMN -->
                                <!------------------------------>
                                <table
                                  cellpadding="0"
                                  cellspacing="0"
                                  border="0"
                                  width="47%"
                                  style="width: 47%;"
                                  align="left"
                                >
                                  <tr>
                                    <td>
                                      <table
                                        cellpadding="0"
                                        cellspacing="0"
                                        border="0"
                                        width="100%"
                                      >
                                        <tr>
                                          <td
                                            align="left"
                                            style="
                                              font-size: 14px;
                                              font-family: Roboto, Helvetica, sans-serif;
                                              color: #7d7d82;
                                            "
                                          >
                                            <div>
                                              <div style="display: inline-block;">
                                                <?= $vars['translator']->trans('Sent to you from') ?>
                                              </div>
                                              <div style="display: inline-block;">
                                                Minds Inc
                                                <span style="white-space: nowrap;"
                                                  >© 2021</span
                                                >
                                              </div>
                                            </div>
                                            <div>
                                              <div style="display: inline-block;">PO Box 7681, </div>
                                              <div style="display: inline-block;">Wilton, CT 06897</div>
                                            </div>
                                          </td>
                                        </tr>
                                      </table>
                                    </td>
                                  </tr>
                                </table>

                                <!------------------------------>
                                <!-- RIGHT COLUMN -->
                                <!------------------------------>
                                <table
                                  cellpadding="0"
                                  cellspacing="0"
                                  border="0"
                                  width="47%"
                                  style="width: 47%;"
                                  align="right"
                                >
                                  <tr>
                                    <td>
                                      <table
                                        cellpadding="0"
                                        cellspacing="0"
                                        border="0"
                                        width="100%"
                                      >
                                        <tr>
                                          <td align="right">
                                            <a
                                              style="
                                                font-size: 14px;
                                                color: #1b85d6;
                                                text-decoration: underline;
                                                font-family: Roboto, Helvetica,
                                                  sans-serif;
                                              "
                                              href="https://www.minds.com/settings/canary/account/email-notifications"
                                              target="_blank"
                                              >
                                              <?= $vars['translator']->trans('Manage email settings') ?>
                                            </a>
                                            </br>
                                            <?php if (isset($vars['campaign'])): ?>
                                              <a
                                                style="
                                                  font-size: 14px;
                                                  color: #1b85d6;
                                                  text-decoration: underline;
                                                  font-family: Roboto, Helvetica,
                                                    sans-serif;
                                                "
                                                href="https://www.minds.com/emails/unsubscribe/<?= $vars['guid']?>/<?= urlencode($vars['email'])?>/<?= $vars['campaign']?><?= '/' . $vars['topic']?>?__e_ct_guid=<?= $vars['guid']?>&campaign=<?= $vars['campaign'] ?>&topic=<?= $vars['topic'] ?>&state=<?= $vars['state']?>"
                                                target="_blank"
                                              >
                                                <?= $vars['translator']->trans('Unsubscribe') ?>
                                              </a>
                                            <?php endif; ?>
                                          </td>
                                        </tr>
                                      </table>
                                    </td>
                                  </tr>
                                </table>
                              </td>
                            </tr>
                            <tr>
                              <td style="padding: 40px 0;"></td>
                            </tr>
                          </table>
                        </td>
                      </tr>
                    </table>

                    <!--[if (gte mso 9)|(IE)]>
                    </td>
                    </tr>
                    </table>
                    <![endif]-->
                  </td>
                </tr>
                <!------------------------------>
                <!-- FOOTER LINKS: End -->
                <!------------------------------>
              </table>
              <!--[if (gte mso 9)|(IE)]>
              </td>
              </tr>
              </table>
              <![endif]-->
            </td>
          </tr>
        </tfoot>
      <!------------------------------>
      <!-- FOOTER: End -->
      <!------------------------------>
    </table>
  </body>
</html>
