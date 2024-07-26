<?php
/**
 * Email Styles
 * Since CSS in emails don't work properly, we are forced to use inline styles
 * This class marries the two by providing a dictionary of classes that can be applied in combination
 *
 * NOTE: Cannot be used in conjunction with hard-coded inline styles,
 * as whichever comes second will be cancelled.
 */

namespace Minds\Core\Email\V2\Common;

class EmailStyles
{
    protected $styles;

    //Define your 'css classes' here
    //In your templates, class <?php $emailStyles->getStyle('m-fonts', 'm-link', 'm-clear')
    public function __construct()
    {
        $this->styles = [
        // TEXT ///////////////////////////
            'm-fonts' => 'font-family:Roboto,Helvetica Neue,Helvetica,Arial,sans-serif;',
            'm-copy' => 'font-size:16px;line-height:22px;text-align:left;font-family:Roboto,Helvetica Neue,Helvetica,Arial,sans-serif;color: #4f4f50;',
            'm-border--light' => 'border: 1px solid #d4d4d4;',
            'm-border--dark' => 'border: 1px solid #53565a;',
            'm-link' => 'text-decoration: underline; color: #1b85d6 !important;',
            'm-noTextDecoration' => 'text-decoration: none;',
            'm-preWrap' => 'white-space: pre-wrap;',
            'm-textColor--primary' => 'color: #4f4f50;',
            'm-textColor--secondary' => 'color: #7d7d82 !important;',
            'm-textColor--white' => 'color: #FFFFFF !important;',
            'm-title' => 'font-size:28px; text-align:center; color: #4f4f50; margin-top: 0; font-weight: 700;',
            'm-title--ltr' => 'font-size:26px; text-align:left;color: #4f4f50;margin-top: 0; font-weight: 700;',
            'm-signature' => 'margin: 0 0 4px 0;padding: 0 !important;',

        // SPACING & LAYOUT ///////////////////////////
            'm-maxWidth' => 'max-width:600px;',
            'm-maxWidth--copy' => 'width:80%;max-width:500px;margin:auto;',
            'm-spacer--tiny' => 'padding: 10px 0;',
            'm-spacer--small' => 'padding: 22px 0;',
            'm-spacer--medium' => 'padding: 40px 0;',
            'm-spacer--large' => 'padding: 56px 0;',
            'm-borderTop' => 'border-top:1px solid #d4d4d4;',
            'm-clear' => 'margin: 0px; padding: 0px;',
        // PARTIAL : SUGGESTED CHANNELS //////////////
            'm-avatar-size' => 'height: 50px; width: 44px;',
            'm-newsfeedSidebar__header' => 'margin-bottom: 8px; font-size: 14px; color: #7d7d82; height: 18px;font-weight:400;',
            'm-suggestions__link' => 'text-decoration: none;',
            'm-suggestions__sidebar' => 'width: 540px; border: 1px solid #d4d4d4; padding: 0;',
            'm-suggestionsSidebarListItem__avatar' => 'width: 28px; height: 28px; padding: 8px',
            'm-suggestionsSidebarList__item' => 'border-bottom: 1px solid #d4d4d4;',
            'm-suggestionsSidebarListItem__description' => 'color: #7d7d82;font-size: 11px; line-height: 16px; font-family: Roboto; overflow: hidden; text-overflow:ellipsis; white-space:nowrap; width: 480px; height: 20px;',

            'm-header' => 'font-size: 24px; color: #000; margin-bottom: 10px;',
            'm-subtitle' => 'font-family:Roboto-Light; font-size: 14px; color: #4A4A4A !important; line-height: 30px;',
        
            // PARTIAL : Digest
            'm-digest__avatar' => 'text-decoration: none;',
            'm-digest__avatarImg' => 'border-radius: 30px; display: inline-block; vertical-align: middle; line-height: 20px',
            'm-digest__name' => 'padding-left: 4px; text-decoration: none; font-size: 14px; line-height: 20px; font-weight: 700;',
            'm-digest__username' => 'text-decoration: none; font-size: 14px;',
            'm-digest__yourActivity' => 'width: 100%; padding: 20px;',
            'm-digestYourActivity__col' => 'padding: 10px;',
            'm-digest__activity' => 'width: 100%;',
            'm-digestActivity__body' => 'padding: 16px;',
            'm-digestActivity__text' => 'font-size: 14px; padding-top: 4px;',

            // PARTIAL : Digest
            'm-unreadNotifications__col' => 'padding: 20px;',
            'm-unreadNotifications__title' => 'font-size: 15px; font-weight: 400; margin-bottom: 20px;',
            'm-unreadNotifications__count' => 'width: 100%; border: 1px solid #d4d4d4; margin-bottom: 20px; box-sizing: border-box;',
            'm-unreadNotificationsCount__text' => 'text-decoration: none; font-size: 17px; font-weight: 500',
            'm-unreadNotifications__previews' => 'width: 100%; border-collapse: collapse;',
            'm-unreadNotifications__preview' => 'width: 100%; border: 1px solid #d4d4d4; padding: 20px; font-size: 15px;',
        
            // PARTIAL : Unread chat messages
            'm-unreadChatMessages__col' => 'padding: 16px 12px;',
            'm-unreadChatMessages__col--first' => 'padding-left: 24px;',
            'm-unreadChatMessages__col--last' => 'padding-right: 32px;',
            'm-unreadChatMessages__borderRounded' => 'border-radius: 4px;',
            'm-unreadChatMessages__listItem' => 'padding: 10px 0;',
            'm-unreadChatMessages__avatarCol' => 'width: 40px;',
            'm-unreadChatMessages__fullWidth' => 'width: 100%;',
            'm-unreadChatMessages__avatar' => 'width: 40px; height: 40px; border-radius: 50%; object-fit: cover;',
            'm-unreadChatMessages__roomName' => 'font-family: Inter; font-size: 14px; font-style: normal; font-weight: 500; line-height: 20px;',

            // PARTIAL: Tenant Welcome email
            'm-tenantWelcome__subtitle' => 'color: #000; font-family: Inter, sans-serif; font-size: 28px; font-weight: 800;',
            'm-tenantWelcome__catchUpSubtitle' => 'margin: 60px 20px 30px;',
    
            // PARTIAL: Tenant Welcome email memberships
            'm-tenantWelcome__membershipBox' => 'margin: 30px 20px; padding: 40px 20px 30px 20px; border-radius: 30px;',
            'm-tenantWelcome__membershipBox--dark' => 'background-color: rgba(255,255,255, 0.10); border: 1px solid rgba(255,255,255, 0.05)',
            'm-tenantWelcome__membershipBox--light' => 'background-color: rgba(0,0,0, 0.10); border: 1px solid rgba(0,0,0, 0.05)',
            'm-tenantWelcome__membershipSubtitle' => 'margin-bottom: 10px;',
            'm-tenantWelcome__membershipPrice' => 'margin: 10px 20px; color: #000; font-family: Inter, sans-serif; font-size: 18px; font-weight: 700;',
            'm-tenantWelcome__membershipDescription' => 'margin: 10px 20px 20px; color: #000; font-family: Inter, sans-serif; font-size: 18px;',
        
            // PARTIAL: Tenant Welcome email groups
            'm-tenantWelcome__groupsSectionSubtitle' => 'margin: 60px 20px 30px;',
            'm-tenantWelcome__groupsTable' => 'width: 100%; table-layout: fixed;',
            'm-tenantWelcome__groupsTableCell' => 'text-align: center; vertical-align: baseline;',
            'm-tenantWelcome__groupBox' => 'max-width: 200px; padding: 10px; margin: auto;',
            'm-tenantWelcome__avatar' => 'width: 96px; height: 96px; border-radius: 50%; object-fit: cover; margin: auto;',
            'm-tenantWelcome__groupName' => 'margin: 20px 0; font-family: Inter, sans-serif; font-size: 18px; font-weight: 700;',
            'm-tenantWelcome__groupDescription' => 'margin: 20px 0; color: #000; font-family: Inter, sans-serif; font-size: 18px; font-weight: 400;',
            'm-tenantWelcome__groupLink' => 'color: #1b85d6 !important; margin: 10px 0 30px; font-family: Inter, sans-serif; font-size: 18px; font-weight: 400;'
        ];
    }

    public function getStyles(...$styleKeys)
    {
        $styles = array_intersect_key($this->styles, array_flip($styleKeys));

        return ' style="'.implode(';', $styles).'" ';
    }
}
