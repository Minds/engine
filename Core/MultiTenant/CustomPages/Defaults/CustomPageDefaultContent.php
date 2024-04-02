<?php
namespace Minds\Core\MultiTenant\CustomPages\Defaults;

use Minds\Core\MultiTenant\CustomPages\Enums\CustomPageTypesEnum;

class CustomPageDefaultContent
{
    public static function get(): array
    {
        return [
            CustomPageTypesEnum::PRIVACY_POLICY->value => '<!--
Do a find/replace on this document for the following terms and replace with values for your business.

{COMPANY NAME}
{EMAIL ADDRESS}

-->

### Privacy policy
Last Updated: 2024-01-22

#### 1. Overview
Your privacy is important to {COMPANY NAME} (the “Company”, “we”, “our”, or “us”). It is our policy to respect the privacy of our users regarding all information we may collect through the operation of the social network.

This policy describes among other things, how and why we collect data about you when you use our Network, what data we collect, what we do with that data, how we look after it, and what your rights are in relation to it.

Please read this policy carefully to understand our policies and practices regarding your information and how we will treat it. If you do not agree with our policies and practices, your choice is not to use our Network. By accessing or using our Network, you agree to this privacy policy. This policy may change from time to time (see Section [16]). Your continued use of the Network after we make changes is deemed to be acceptance of those changes, so please check the policy periodically for updates.

The Network is encrypted via HTTPS, and we encrypt and/or hash all sensitive information while in motion or when stored at rest, including emails, passwords, and private messages.

#### 2. Data We Collect About You
Certain features of the Network may require you to submit information (including personal information, which is defined below) as a condition to use certain features of the Network. “Personal information” means any information that identifies, relates to, describes, is reasonably capable of being associated with, or could reasonably be linked, directly or indirectly, with an identified or identifiable individual (e.g., a verified name, fingerprints or other biometric data, email address, street address, telephone number or social security number).

The personal information we collect via or in relation to the Network can be split into three broad categories: information you provide to us; information collected automatically; and information we obtain from third parties.

##### Information You Provide To Us.
We may ask for and collect the following information when create an account and/or use the Network:

- Identity and Profile Information: We require you to provide certain personal information to create an account on the Network. The personal information we require includes your email address.
- Payment Information: We may request your credit card or other payment information to collect payment for purchases made through the Network.  We use Stripe, a third-party provider to process the credit card information and do not store your payment information. The use and storage of your payment information is governed by the own privacy policy of such provider.
- Communications: When you communicate with us by email, or through/via the Network, we collect information about your communication and any information you choose to provide.

##### Information Collected Automatically
We may use automatic data collection technologies and collect certain information automatically when you use the Network. This information includes:

- Interaction information and history: Information about your interactions and history with the Network, including the pages that you visit and links you click on, search history, logs, and location data, the time, frequency and duration of your Network visits and uses, and what features you used; Information about your computer and internet connection, including your IP address, device type, operating system, and browser type; and country, region, and city.
- Cookies: Like many websites on the Internet, when you visit the Network, the Company may assign your computer one or more cookies to facilitate access to the Network and to personalize your online experience. Through the use of a cookie, the Company also may automatically collect information about your online activity on the Network, such as the web pages you visit, the time and date of your visits, the links you click, and the searches you conduct on the Network. The Company may use this data to better understand how you interact with the Network, to monitor aggregate usage by our users and web traffic routing on the Network, and to improve the Network. Most browsers automatically accept cookies, but you can usually modify your browser setting to decline cookies. If you choose to decline cookies, please note that you may not be able use some of the interactive features offered on the Network. In addition, please be aware that other parties may also place their own cookies on or through the Network and may collect or solicit personal information from you. Other cookies may reflect de-identified demographic or other data linked to the registration data you have submitted to the Company in hashed, non-human readable form. No personally identifiable information is contained in these cookies.

The information we collect automatically may include personal information. It helps us to improve our Network and to deliver a better and more personalized service, including by enabling us to: estimate our audience size and usage patterns; store information about your preferences, allowing us to customize our Network according to your individual interests; speed up your searches; recognize you when you return to our Network.

##### Information We Obtain From Third Parties.
We may also receive information about you from our service providers and business partners, including companies that assist with the operation and hosting of the Network, as well as analytics, data processing and management, account management, customer and technical support, and other services which we use to personalize your experience on the Network. When we receive information from other sources, we rely on them having the appropriate provisions in place telling you how they collect data and who they may share it with.

#### 3. Use of Personal Information
We will use the personal information to:

- Carry out our obligations arising from any contracts entered into between you and us and to provide you with the Network;
- Better understand our users;
- Respond to your enquiries or to process your requests in relation to your information;
- Notify you about your account and important changes or developments to our Network;
- Process payments for services, and complete other Network-related purchases and payments;
- Ensure that the Network are being presented in the most effective manner for you, making the Network easier for you to use and to provide you with a smooth, efficient, safe and tailored experience;
- Detect and protect us against error, fraud and other criminal activity, and enforce our agreements;
- Administer the Network and for internal operations, including troubleshooting, data analysis, testing, research, and statistical purposes;
- Improve the Network and user experience to ensure that content is presented in the most effective manner for you;
- Allow you to participate in interactive features of our Network, when you choose to do so; and
- Be used as part of our efforts to keep the Network safe and secure.
- In any other way we may describe when you provide the information; and
- For any other purpose with your consent.

#### 4. Sharing Your Personal Information
The following describes the ways we may share your personal information in the normal course of business and in order to provide our Network.

With your consent: We will disclose your personal information when and if you have agreed that we may do so. We will make this clear to you at the point at which we collect your personal information, including the purpose(s) for which we will use the personal information.

Intended purpose: We will use your personal information to fulfill the purpose for which you provide it, or for any other purpose disclosed by us when you provide the information.

Service providers: We use contractors, service providers, and other third parties to facilitate or outsource some aspects of our Network and some of these service providers will process your personal information. These third parties’ collection and use of information are subject to their own privacy policies.

If required by law: We may be required to disclose your personal information by law, in response to a valid order of a court or other authority, or to respond to regulatory requests or requests relating to criminal investigations.

Change of control: We may transfer your personal information to a buyer or other successor in the event of a merger, divestiture, restructuring, reorganization, dissolution, or other sale or transfer of some or all of our assets, whether as a going concern or as part of bankruptcy, liquidation, or similar proceeding, in which personal information held by us about our Network users is among the assets transferred. If this happens, you will be informed of this transfer.

Enforcing our terms of use: We may disclose your personal information to enforce or apply our terms of use and other agreements, including for billing and collection purposes.

Protecting our rights: We may disclose your personal information if we feel this is necessary in order to protect or defend our legitimate rights and interests and/or to ensure the safety and security of the Network.

Subsidiaries and affiliates: We may disclose your personal information to our subsidiaries and affiliates, based on our instructions and in compliance with our Privacy Policy and any other appropriate confidentiality and security measures.

#### 5. Data Security
The Company (either itself or through third party service providers) maintains a variety of commercially reasonable electronic and procedural safeguards designed to protect your personal information from unauthorized access, use, alteration and disclosure. For example, we use accepted tools and techniques to protect against unauthorized access to our systems. You should be aware that we have no control over the security of other sites on the Internet that you might visit or with which you might interact even when a link may appear to another site from one of the Network. You must play your part in the safety and security of your information. Where we have given you (or where you have chosen) a password for access to certain parts of our Network, you are responsible for keeping this password confidential and thus must not to share your password with anyone. We want you to feel confident using the Network, but no system can be completely secure. Therefore, although we takes steps to secure your information, we do not promise, and you should not expect, that your personal information or communications will always remain secure. In the event of a breach of the confidentiality or security of your information, including your personal information, we will notify you as necessary and if possible so you can take appropriate protective steps. We may notify you under such circumstances using the e-mail address(es) that we have on record for you. You also should carefully handle and disclose your personal information.

Like most website operators, the Company also collects non-personally-identifying information of the sort that web browsers and servers typically make available, such as the browser type, language preference, referring site, and the date and time of each visitor request. The Company’ purpose in collecting non-personally identifying information is to better understand how the Company’ visitors use its website. From time to time, the Company may release non-personally-identifying information in the aggregate, e.g., by publishing a report on trends in the usage of its website.

#### 6. Notification and Disclosure of Breach
The Company will notify users if we believe their Personal Information is disclosed in any unauthorized way. Although the Company provides reasonable administrative, physical and electronic security measures to protect Personal Information from unauthorized access, we cannot assure the security of any information you transmit or guarantee that this information will not be accessed, disclosed, altered, or destroyed.

We will make any legally required disclosures of any breach of the security, confidentiality, or integrity of your unencrypted Personal Information. To the extent the law of your jurisdiction allows for notification of a breach via e-mail or public posting, you agree to accept notice in that form.

If you send us a request (for example via a support email or via one of our feedback mechanisms), we reserve the right to publish it in order to help us clarify or respond to your request or to help us support other users.

#### 7. Updating and Retention of Your Data
For instructions on accessing or correcting your information, or to request a copy of the information we hold about you, please contact us by email at {EMAIL ADDRESS}. Please note that we may not accommodate a request to change information if we believe the change would violate any law or legal requirement or cause the information to be incorrect.

We retain your personal information as long as we are providing the Network to you. We retain your personal information after we cease providing the Network to you, even if you delete your account, to the extent necessary to fulfill your request (for example, keeping your email address to make sure it’s not on our mailing list); to comply with our legal and regulatory obligations; for the purpose of fraud monitoring, detection and prevention; to comply with our tax, accounting, and financial reporting obligations; or where we are required to retain the data by our contractual commitments to our partners. Where we retain data, we do so in accordance with any limitation periods and records retention obligations that are imposed by applicable law. Even if you delete your account and we delete your information from our systems, keep in mind that the deletion by our third-party providers may not be immediate and that the deleted information may persist in backup copies for a reasonable period of time.

#### 8. How Long We Keep Your Information

Personal Information that you provide to the Network may be retained indefinitely, to the extent it is consistent with our Terms of Use and applicable law. The Company may in its discretion elect to retain Personal Information only while deemed necessary for business, legal and tax purposes.

#### 9. Children
The Network are not intended for use by children. Individuals under the age of 18 are not permitted to use the Network and must not attempt to register an account or submit any personal information to us. We do not knowingly collect any personal information from any person who is under the age of 18, allow them to register an account. If it comes to our attention that we have collected personal information from a person under the age of 18, we will delete this information as quickly as possible.

If you are a parent or guardian and you learn that your child has provided us with personal information, please notify us immediately at {EMAIL ADDRESS}.

#### 10. Government, Regulatory Agencies, Courts, and Law Enforcement
We may access, preserve, and share any information about you, without your consent, to government or law enforcement officials or private parties if we believe in good faith that it is necessary or appropriate to respond to requests, claims and legal process (including, but not limited to, subpoenas), to comply with applicable laws, to protect the property and rights of the Company, you or a third party, to protect the safety of the public or any person, or to prevent or stop activity we may consider to pose a risk of being, or is, illegal, unethical or legally actionable.

We may also access, preserve and share information when we have a good faith belief it is necessary to: detect, prevent and address fraud and other illegal activity; to protect ourselves, you and others, including as part of audits, inquiries or investigations; and to prevent death or imminent bodily harm. Information we receive about you may be accessed, processed and retained for an extended period of time when it is the subject of a legal request or obligation, governmental investigation, or investigations concerning possible violations of our terms or policies, or otherwise to prevent harm.

If you voluntarily share or submit any information (e.g., name) or content (e.g., pictures), or commentary (e.g., forums, message boards, chats) through the Network, or link it to any social media platforms, you acknowledge that the post and any content or information associated with it may become available to the public.

#### 11. Business Transfers
If the Company, or substantially all of its assets, were acquired, or in the unlikely event that the Company goes out of business or enters bankruptcy, user information would be one of the assets that is transferred or acquired by a third party. You acknowledge that such transfers may occur, and that any acquirer of the Company may continue to use your Personal Information as set forth in this policy. At any time users are able to delete all content and personally identifiable information from the site.

#### 12. How We Respond to Do Not Track Signals
The Company does not track its users over time and across third party websites to provide targeted advertising. We do not support Do Not Track (“DNT”). Do Not Track is a preference you can set in your web browser to inform websites that you do not want to be tracked. You can enable or disable Do Not Track by visiting the preferences or settings page of your web browser. For further details, visit donottrack.us.

#### 13. Other Websites and Services
The Network may contain links to other websites or services that we do not own or operate. We are not responsible for the practices employed by any websites or services linked to or from the Network, including the information or content contained within them. Your browsing and interaction on any third-party website or service, including those that have a link on our Network, are subject to that third party’s own rules and policies, not ours. In addition, you agree that we are not responsible and do not have control over any third-parties that you authorize to access your Personal Information. If you are using a third-party website or service and you allow them to access your Personal Information you do so at your own risk.

#### 14. International Considerations
We have developed data practices designed to assure all the Company information is appropriately protected, but we cannot always know where Personal Information may be accessed or processed. While our primary facilities are in the United States, we may transfer Personal Information or other information to facilities outside of the United States. In addition, we may employ other companies and individuals to perform functions on our behalf. If we disclose Personal Information to a third party or to our employees outside of the United States, we will seek assurances that any information we may provide to them is safeguarded adequately and in accordance with this Privacy Policy and applicable privacy laws.

If you live in or you are visiting from the European Union or other regions with laws governing data collection and use, please note that you are agreeing to the transfer of your Personal Information, including sensitive data, by us from your region to countries which may not have data protection laws that provide the same level of protection that exists in countries in the European Economic Area, including the United States. By providing your Personal Information, you consent to any transfer and processing in accordance with this policy.

#### 15. Residents of Certain States
California. California Civil Code Section 1798.83 permits our users who are California residents to request and obtain from us, once a year and free of charge, information about categories of personal information (if any) we disclosed to third parties for direct marketing purposes and the names and addresses of all third parties with which we shared personal information in the immediately preceding calendar year. We do not currently disclose any information to third parties for direct marketing purposes.

Nevada. We do not currently sell your “personally identifiable information” as defined under applicable Nevada law and will not sell it without providing a right to opt out.

#### 16. Privacy Policy Changes and Further Information
Although most changes are likely to be minor, the Company may change its Privacy Policy from time to time, and in the Company’ sole discretion. The Company advises you to frequently check this page for any changes to its Privacy Policy. Your continued use of the Network after any change in this Privacy Policy will constitute your acceptance of such changes.

It is our goal to make our privacy practices easy to understand. If you have questions or concerns about this privacy policy or if you would like more detailed information about our privacy practices, please contact us at: {EMAIL ADDRESS}.
',
            CustomPageTypesEnum::TERMS_OF_SERVICE->value => '<!--
Do a find/replace on this document for the following terms and replace with values for your business.

{EMAIL ADDRESS}
{COMPANY NAME}
{NETWORK NAME}
{EMAIL ADDRESS}
{STATE OF BUSINESS}

-->

### Terms of service
Last Updated: 2024-01-22

These terms of use are entered into by and between you and {COMPANY NAME} ("Company," "we," or "us"). The following terms and conditions, together with any documents they expressly incorporate by reference (collectively, "Terms of Use"), govern your access to and use of {NETWORK NAME}, including any content, functionality, and services offered on or through {NETWORK NAME} (the "Network") and any other online services provided by us or our legal affiliates, including any content, functionality, and features offered on or through the Network to you as a guest or registered user of the Network (each, a “User”).

Please read the Terms of Use carefully before you start to use the Network. By using the Network or by clicking to accept or agree to the Terms of Use when this option is made available to you, you accept and agree to be bound and abide by these Terms of Use and our Privacy Policy, incorporated herein by reference. If you do not want to agree to these Terms of Use or the Privacy Policy, you must not access or use the Network.

You understand that we may change these Terms of Use from time to time in our sole discretion and in accordance with Section [18] below. It is your responsibility to periodically check these Terms of Use so that you are aware of any changes, as they are binding on you.

This Network is offered and available to users who are 18 years of age or older and to users who are 12 years of age or older and possess legal parental or guardian consent to enter into these Terms of Use. In any case, you affirm that you are over the age of 13. By using this Network, you represent and warrant that you meet all of the foregoing eligibility requirements. If you do not meet all of these requirements, you must not access or use the Network.

#### 1. Accessing the Network and Account Security

We reserve the right to withdraw or amend this Network, and any service or material we provide on the Network, in our sole discretion without notice. We will not be liable if for any reason all or any part of the Network is unavailable at any time or for any period. From time to time, we may restrict access to some parts of the Network, or the entire Network, to users, including registered users.

The use of our Network requires a username and channel. If you choose, or are provided with, a username, channel, password, or any other piece of information as part of our security procedures, you must treat such information as confidential, and you must not disclose it to any other person or entity. You also acknowledge that your account is personal to you and agree not to provide any other person with access to this Network or portions of it using your username, channel password, or other security information. You agree to notify us immediately of any unauthorized access to or use of your username or password or any other breach of security. You should use particular caution when accessing your account from a public or shared computer so that others are not able to view or record your password or other personal information.

To access the Network or some of the resources it offers, you may be asked to provide certain registration details or other information. It is a condition of your use of the Network that all the information you provide on the Network is correct, current, and complete. You agree that all information you provide to register with this Network or otherwise, including, but not limited to, through the use of any interactive features on the Network, is governed by our Privacy Policy, and you consent to all actions we take with respect to your information consistent with our Privacy Policy.

We have the right to disable any username, channel, password, or other identifier, whether chosen by you or provided by us, at any time in our sole discretion for any or no reason, including if, in our opinion, you have violated any provision of these Terms of Use

#### 2. Responsibility of Registered Users
You are responsible for:

- Keeping your account information (including your access credentials) secret and secure.
- Making all arrangements necessary for you to have access to the Network.
- All activity that occurs through your channel or username and all of your activity on the Network and for ensuring that all persons who access the Network through your internet connection are aware of these Terms of Use and comply with them.

You agree and acknowledge that:

1. You shall not sell, rent, lease, lend, transfer, license or assign your account, credentials, or any account rights.
2. You are solely responsible for your interaction with other Users of the Network, whether online or offline. You agree that we are not responsible or liable for the conduct of any User. We reserve the right, but has no obligation, to become involved in disputes between you and other Users of the Network.
3. You shall not describe or assign keywords to your channel in a misleading or unlawful manner, including any manner which trades on the name or reputation of others. We reserve the right to change or remove any description or keyword that we consider unlawful or otherwise likely to cause us liability.
4. You shall immediately notify us of any unauthorized uses of your username, account, or channel, or of any other breaches of security by emailing {EMAIL ADDRESS}.
5. If you operate a channel, comment on a post, post material to the Network, post links or create (or allow any third party to create) or otherwise make material available by means of the Network, including any text, photo, video, audio, code or other work of authorship (any such material, “User Content”), you are entirely responsible for the content of, and any liability resulting from or relating to that User Content or your conduct.
6. By using the Network, you represent and warrant that your User Content and conduct do not and will not violate these Terms of Use (including our Content Policy), infringe the rights of any other person or entity (including intellectual property rights and privacy rights), or violate any applicable law, rule, or regulation.
7. By submitting User Content to the Network, you grant us a perpetual, irrevocable, worldwide, royalty-free, and non-exclusive license to use, reproduce, modify, distribute, publish, process and adapt (each, a “Use”) your User Content for the purpose of providing and promoting the Network, without any notice of, consent to or compensation for any such use, unless otherwise licensed by the User, through the Network.
8. You grant other Users permission to share your User Content on other Network channels and add their own User Content to it (e.g., to “remind” your User Content).
9. All User Content, that is not otherwise marked by the license owner, is licensed under the All Rights Reserved license.
10. If you delete User Content, we will use reasonable efforts to remove it from the Network, but you acknowledge that caching or references to the User Content may not be made immediately unavailable.
11. By registering an account and making User Content available, you further represent and warrant that the user content does not violate our Content Policy referenced in Section [15];
12. Your use of the Network and User Content will not infringe the rights of any other party (including intellectual property rights and privacy rights) or violate any applicable law, rule, or regulation;
13. Any Use by us of any User Content will not infringe the rights of any other party (including intellectual property rights and privacy rights) or violate any applicable law, rule, or regulation;
14. If any other party has rights to intellectual property you incorporate into any User Content, you have either (i) received permission from such other to so incorporate such intellectual property into such User Content, including but not limited to any software, or (ii) secured from such other party a waiver as to all rights in or to such User Content;
15. You have fully complied with any third-party licenses relating to all User Content and have done all things necessary to grant us the license set forth under Section [2(7)] and to successfully grant to others any relevant rights under any such third-party licenses;
16. You also give other Network Users permission to share your Content under the legal terms outlined in the license you select, whether Creative Commons, All Rights Reserved, or any other available license.

#### 3. Prohibited Uses
You shall not use or access the Network:

1. In any way that violates any applicable United States federal, state or local law, rule, or regulation (including, without limitation, any intellectual property laws or privacy laws or laws regarding sanctions, or the export of data or software to and from the United States or other countries);
2. To post unlawful, infringing, or other content not allowed under these Terms of Use.
3. To impersonate, attempt to impersonate, or falsely imply that you are associated with the Company, another User, or any other person or entity;
4. In any manner that could disable, alter, overburden, damage, or impair the Network, or engage in any other conduct that restricts or interferes with any other party’s use, which, as determined by us, may harm the Company or Users of the Network or expose them to liability, including but not limited to transmitting any worms, viruses, spyware, malware or any other code of a destructive, malicious, intrusive, or disruptive nature intended to cause denial of service;
5. To use, distribute, modify, create derivative works from, or copy the Network or any feature of the Network or any User account or channel (including any User Content) in whole or in part, or decompile, reverse engineer, disassemble, attempt to derive the source code or underlying algorithms of the Network or any feature of the Network, except as may be permitted under any license applicable to the Network;
6. To create accounts or access data (including User information) through unauthorized means, by using an automated device, caching, script, bot, spider, crawler or scraper or any such weaponized capability intended as a malware threat to the Company;
7. To attempt to gain unauthorized access, or permit unauthorized access, to the Network or any feature of the Network (including any User account or channel) or any of our related systems or networks, or bypass any measures we take to restrict access to the Network or related systems or networks;
8. To substantially replicate products or services offered by the Company in an impersonating manner, including by republishing the Network content or creating a separate publishing platform; or
9. Without limiting any of the above representations or warranties or obligations, we reserve the right to, in our sole discretion, (i) reject or remove any User Content that, in our reasonable opinion, violates any term or condition of these Terms of Use (or any of our policies) or is in any way unlawful under applicable law (ii) ban and remove any channel that, in our reasonable opinion, violates any term or condition of these Terms of Use (or any of our policies) or is in any way unlawful or (iii) terminate or deny access to and use of the Network to any person or entity whose use is unlawful.
10. In the event a channel is banned due to the breach of these Terms of Use, and the channel owner no longer has access to the channel, we will not have any obligation to the channel owner or any other third party.

If you encounter a violation of these Terms of Use as you browse the Network, please report the violation by email to {EMAIL ADDRESS}.

#### 4. HTTPS
The Network supports HTTPS and encrypts all sensitive information stored at rest on it; free HTTPS is offered by default. By signing up and using a custom domain or sub-domain on the Network, you authorize us to act on the domain name registrant’s behalf (by requesting the necessary certificates, for example) for the sole purpose of providing HTTPS on your site. We reserve the right to require HTTPS before publishing such custom domains. Your use of the domain name is also subject to the policies of the Internet Corporation for Assigned Names and Numbers (“ICANN”).

A summary of your rights and responsibilities as a domain name registrant under ICANN’s 2009 Registrar Accreditation Agreement can be found at: https://www.icann.org/resources/pages/benefits-2013-09-16-en. You can learn more about domain name registration generally at: https://www.icann.org/resources/pages/educational-2012-02-25-en

#### 5. Intellectual Property Rights
The Network and its entire contents, features, and functionality (including but not limited to all information, software, text, displays, images, video, audio, icons and scripts and other intellectual property, and the design, selection, and arrangement thereof) are owned by the Company, its licensors, or other providers of such material and are protected by United States and international copyright, trademark, patent, trade secret, and other intellectual property or proprietary rights laws.

These Terms of Use grant you a limited, non-exclusive, non-sublicensable, non-transferable license to access and use the Network in accordance with these Terms for your personal, non-commercial use only and for lawful purposes. You must not reproduce, distribute, modify, create derivative works of, publicly display, publicly perform, republish, download, store, or transmit any of the material on our Network, except as follows:

- Your computer may temporarily store copies of such materials in RAM incidental to your accessing and viewing those materials.
- You may store files that are automatically cached by your Web browser for display enhancement purposes.
- You may print one copy of a reasonable number of pages of the Network for your own personal, non- commercial use and not for further reproduction, publication, or distribution.
- If we provide desktop, mobile, or other applications for download, you may download a single copy to your computer or mobile device solely for your own personal, non-commercial use, provided you agree to be bound by our end user license agreement for such applications.

You must not:

- Modify copies of any materials from the Network.
- Delete, conceal, or alter any copyright, trademark, or other proprietary rights notices from copies of materials from the Network.
- Reproduce, modify, adapt, prepare derivative works based on, perform, display, publish, distribute, transmit, broadcast, sell, license or otherwise exploit any content (other than your User Content) on the Network, except as may be permitted by these Terms of Use.

You must not access or use for any commercial purposes any part of the Network or any services or materials available through the Network.

If you print, copy, modify, download, or otherwise use or provide any other person with access to any part of the Network in breach of the Terms of Use, your right to use the Network will stop immediately and you must, at our option, return or destroy any copies of the materials you have made. No right, title, or interest in or to the Network or any content on the Network is transferred to you, and all rights not expressly granted are reserved by the Company. Any use of the Network not expressly permitted by these Terms of Use is a breach of these Terms of Use and may violate copyright, trademark, and other laws.

#### 6. Reporting Copyright Infringement, DMCA Policy and Other Violations
1. We prohibit Users of our Network from submitting, uploading, posting, or otherwise transmitting any materials that violate another person’s intellectual property rights. To report allegations of infringement, please contact us at {EMAIL ADDRESS}.
2. As we ask others to respect our intellectual property rights, we respect the intellectual property rights of others. If you believe that material located on or linked to the Network violates your copyright, you are encouraged to notify us in accordance with our Digital Millennium Copyright Act (“DMCA”) Policy set forth below. We will respond to all such notices in accordance with applicable law. We will ban or terminate a channel’s access to and use of the Network if, under appropriate circumstances, that channel is determined to be a repeat copyright or intellectual property rights infringer. In the case of such ban or termination, we will have no obligation to provide access to the Network.

#### 7. Monitoring and Enforcement; Ban and Termination
We have the right to:

- Modify, ban or terminate the Network for any reason, without notice, at any time, and without liability to you;
- Refuse, terminate, or suspend your access to all or part of the Network for any or no reason, including, without limitation, any violation of these Terms of Use; and
- Force forfeiture of any username for any reason. Upon any such refusal, termination or suspension of your access to the Network, all licenses and other rights granted to you under these Terms of Use will immediately cease.

#### 8. Linking to the Network
You may link to our Network homepage, provided you do so in a way that is fair and lawful, but you must not establish a link in such a way as to suggest any form of association, approval, or endorsement on our part without our express written consent, which may be withheld for any reason or no reason in our sole discretion. You may use these features solely as they are provided by us, and must not otherwise: (a) establish a link from any website that is not owned by you; (b) cause the Network or portions of it to be displayed on, or appear to be displayed by, any other site (for example, scraping, framing, deep linking, or in-line linking); or (c) take any action with respect to the Network that is inconsistent with these Terms of Use.

#### 9. Links on the Network
If the Network contains links to other sites and resources provided by third parties, these links are provided for your convenience only. This includes links contained in advertisements, including banner advertisements and sponsored links. You acknowledge that we have no control over the contents of those sites or resources, and accept no responsibility, and we disclaim any liability for them or for any loss or damage that may arise from your use of them. If you decide to access any of the third-party websites linked to our Network, you do so entirely at your own risk and subject to the terms and conditions of use for such websites.

#### 10. Advertisements
You acknowledge and agree that the Network may inject advertised content into your newsfeed, including in connection with our agreements with publishers. In addition, you acknowledge and agree that other User channels to which you are subscribed may remind advertised content to your feed. We may provide Users with paid accounts to opt-out of any such advertisements.

#### 11. Services Content
1. We may update the Network from time to time, but it will not necessarily be complete or up-to-date at any given time. Any of the material on the Network may be out of date at any given time, and we are under no obligation to update such material.
2. You acknowledge that we may not always identify paid services, sponsored content, or commercial communications as such, except as may be required by applicable law.
3. Although it is our intention for the Network to be available as much as possible, there will be occasions when the Network’s availability may be interrupted, including, without limitation, for scheduled or unscheduled maintenance or upgrades, for emergency repairs, or due to failure of telecommunications links and/or software or hardware.
4. We reserve the right to remove any content from the Network that is deemed in violation of these Terms of Use, without prior notice. Content removed from the Network may continue to be stored, including, without limitation, in order to comply with certain legal obligations, but may not be retrievable without a valid court order.

#### 12. Geographic Restrictions
The Network is based in the United States is accessible for use to persons located all over the world with the exception of those countries that are sanctioned by OFAC (https://www.treasury.gov/resource-center/sanctions/Programs/Pages/Programs.aspx). Access to the Network may not be legal by certain persons or in certain countries. If you access the Network from outside the United States, you do so on your own initiative and are responsible for compliance with local laws, though these Terms of Use are governed solely by United States law.

#### 13. Payments and Automatic Renewals
We offer optional paid services (including – without limitation – memberships, paid replies, and “boost”) (any such services, an “Upgrade”). Please note that any such Upgrade may be subjected to additional terms and conditions as specified at the time of purchase.

By requesting an Upgrade you agree to pay us the applicable one time, monthly or annual subscription fees indicated for that service. Payments will be charged on a pre-paid basis on the day you sign up for an Upgrade and will cover the use of that service for the time period as indicated at the time of purchase.

Unless you notify us before the beginning of the applicable subscription period that you want to cancel, your subscription or service will automatically renew, and you authorize us to collect the then-applicable annual or monthly subscription fee for such Upgrade (as well as any taxes) using any credit card or other payment mechanism we have on record for you. Upgrades can be cancelled at any time in User settings.

PLEASE NOTE THAT ALL PURCHASES ARE FINAL AND NON-REFUNDABLE.

#### 14. Content Policy
Your use of the Network is subject to the network content policy, as may be amended from time to time and which hereby incorporated by reference and made a part hereof in its entirety (the "Content Policy"). Any User Content must in their entirety comply with the Content Policy and with all applicable federal, state, local, and international laws and regulations.

#### 15. Responsibility of Visitors
We do not review, and cannot review, all of the material, including computer software, posted to our Network, and cannot therefore be responsible for that material’s content, use or effects. We do not represent or imply that we endorse the material there posted, or that we believe such material to be accurate, useful or non-harmful. You are responsible for taking precautions as necessary to protect yourself and your computer systems from viruses, worms, Trojan horses, and other harmful or destructive content. The Network may contain content posted by others that is offensive, indecent, or otherwise objectionable, as well as content containing technical inaccuracies, typographical mistakes, fake news, propaganda, satire, or other errors.

#### 16. Restricted Use
You may not use the Network or any associated API access to impersonate or attempt to impersonate the Company, the Network or any products or services offered by us; to falsely imply that you are associated with the Company and/or the Network, another User, or any other person or entity; or for any other fraudulent or misleading purpose.

Your use of the Network is at all times subject to these Terms of Use and the terms and conditions of the GNU Affero General Public License v3.0 (“AGPL-3.0”). If we have reason to believe that you have violated or attempted to violate these Terms or the AGPL-3.0, your ability to use and access the Network may be temporarily or permanently revoked, with or without notice.

#### 17. Content Review Policy and Third Party Services
We have not reviewed, and cannot review, all of the material, including computer software, made available through the websites and webpages to which the Network links, and that link to the Network. We do not have any control over such other websites and is not responsible for their contents or their use. By linking to any such other website, we do not represent or imply that it endorses such website. You are responsible for taking precautions as necessary to protect yourself and your computer systems from viruses, worms, Trojan horses, and other harmful or destructive content. We disclaim any responsibility for any harm resulting from your use of such other websites and webpages.

You understand and agree that: (a) we are not responsible for, and do not control, third party services; and that (b) we are not responsible for the availability of such external sites or resources, and do not endorse nor are we responsible or liable for any content, advertising, products or other materials on or available from such third party services. You acknowledge and agree that we shall not be responsible or liable, directly or indirectly, for any damage or loss caused or alleged to be caused by or in connection with the use of, or reliance upon, any such content, goods or services available on or through any such third party services.

#### 18. Changes
We may change or update the Network in whole or in part at any time and we reserve the right to change these Terms of Use at any time. If we make changes to these Terms of Use that are material, we will let you know by sending you an email or other communication. The notice will designate a reasonable period of time after which the changes to these Terms of Use will take effect. If you disagree with our changes, then you should stop using the Network within the designated notice period. Your continued use of the Network indicates your acceptance of such changes, your continued use of the Services will be subject to these Terms of Use, as updated.

#### 19. Privacy Policy; Other Policies
Your access to and use of the Network is at all times subject to our Privacy Policy, which addresses how we collect, use, share, and store your information. Your access to and use of the Network may be subject to one or more other policies adopted from time to time by us.

If we adopt a new policy relating to the Network that is material, we will let you know by sending you an email or other communication before the new policy takes effect. The notice will designate a reasonable period of time after which such new policy will take effect. If you disagree with such new policy, then you should stop using the Network within the designated notice period. Your continued use of the Network indicates your acceptance of such new policy, and your continued use of the Services will be subject to such new policy.

#### 20. Disclaimer of Warranties and Limitation of Liability
1. YOU UNDERSTAND THAT WE CANNOT AND DO NOT GUARANTEE OR WARRANT THAT FILES AVAILABLE FOR DOWNLOADING FROM THE INTERNET OR THE SERVICES WILL BE FREE OF VIRUSES OR OTHER DESTRUCTIVE CODE. YOU ARE RESPONSIBLE FOR IMPLEMENTING SUFFICIENT PROCEDURES AND CHECKPOINTS TO SATISFY YOUR PARTICULAR REQUIREMENTS FOR ANTI-VIRUS PROTECTION AND ACCURACY OF DATA INPUT AND OUTPUT, AND FOR MAINTAINING A MEANS EXTERNAL TO OUR SITE FOR ANY RECONSTRUCTION OF ANY LOST DATA.
2. ANY VIOLATION OF THESE TERMS MAY, IN OUR DISCRETION, RESULT IN TERMINATION OF YOUR ACCOUNT. YOU UNDERSTAND AND AGREE THAT WE CANNOT AND WILL NOT BE RESPONSIBLE FOR THE CONTENT POSTED ON THE NETWORK AND YOU USE THE NETWORK AT YOUR OWN RISK. IF YOU VIOLATE THE LETTER OR SPIRIT OF THESE TERMS OF USE, OR OTHERWISE CREATE RISK OR POSSIBLE LEGAL EXPOSURE FOR THE COMPANY, WE CAN STOP PROVIDING ALL OR PART OF THE NETWORK TO YOU.
3. TO THE FULLEST EXTENT PERMITTED BY THE APPLICABLE LAW, WE OFFER THE NETWORK “AS-IS” AND MAKES NO REPRESENTATIONS OR WARRANTIES OF ANY KIND CONCERNING THE NETWORK, EXPRESS, IMPLIED, STATUTORY OR OTHERWISE, INCLUDING, WITHOUT LIMITATION, WARRANTIES OF TITLE, MERCHANTABILITY, FITNESS FOR A PARTICULAR PURPOSE, NON-INFRINGEMENT, OR COMPATIBILITY WITH ANY SOFTWARE OR HARDWARE.
4. WE DO NOT WARRANT THAT THE FUNCTIONS OR CONTENT CONTAINED ON THE NETWORK WILL BE AVAILABLE, UNINTERRUPTED OR ERROR-FREE, THAT DEFECTS WILL BE CORRECTED, OR THAT THE SERVERS ARE FREE OF VIRUSES OR OTHER HARMFUL COMPONENTS THROUGH USE OR DOWNLOADING MATERIAL FROM THE NETWORK.
5. WE DO NOT WARRANT OR MAKE ANY REPRESENTATION REGARDING USE OR THE RESULT OF USE OF THE CONTENT IN TERMS OF ACCURACY, RELIABILITY, OR OTHERWISE.
6. YOUR USE OF THE NETWORK AND ITS CONTENT IS AT YOUR OWN RISK. EXCEPT TO THE EXTENT REQUIRED BY APPLICABLE LAW AND THEN ONLY TO THAT EXTENT, IN NO EVENT WILL WE, OUR EMPLOYEES, OFFICERS, DIRECTORS, AFFILIATES OR AGENTS (“THE COMPANY PARTIES”) BE LIABLE TO YOU ON ANY LEGAL THEORY FOR ANY INCIDENTAL, DIRECT, INDIRECT, PUNITIVE, ACTUAL, CONSEQUENTIAL, SPECIAL, EXEMPLARY OR OTHER DAMAGES, INCLUDING WITHOUT LIMITATION, LOSS OF REVENUE OR INCOME, LOST PROFITS, PAIN AND SUFFERING, EMOTIONAL DISTRESS, COST OF SUBSTITUTE GOODS OR SERVICES, OR SIMILAR DAMAGES SUFFERED OR INCURRED BY YOU OR ANY THIRD PARTY THAT ARISE IN CONNECTION WITH THE NETWORK (OR THE TERMINATION THEREOF FOR ANY REASON), EVEN IF THE COMPANY PARTIES HAVE BEEN ADVISED OF THE POSSIBILITY OF SUCH DAMAGES.
7. WE SHALL NOT BE RESPONSIBLE OR LIABLE WHATSOEVER IN ANY MANNER FOR ANY CLAIMS OF INFRINGEMENT RELATING TO THE NETWORK, FOR YOUR USE OF THE NETWORK, OR FOR THE CONDUCT OF THIRD PARTIES WHETHER ON THE NETWORK OR RELATING TO THE NETWORK. WE DO NOT WARRANT THAT THE USER CONTENT OF ANY OTHER USERS WILL BE MADE AVAILABLE TO YOU AT ALL TIMES OR AT ANY TIME. WE SHALL HAVE NO LIABILITY FOR THE REMOVAL OF ANY USER OR ANY USER CONTENT FROM THE NETWORK (WHETHER BY THE APPLICABLE USER OR ANY OTHER THIRD PARTY). IF YOU BRING OR ATTEMPT TO BRING ANY CLAIM AGAINST THE COMPANY ARISING OUT OF THE REMOVAL OF ANY USER OR USER CONTENT, YOU SHALL BE RESPONSIBLE FOR ALL OF THE COMPANY’S LOSSES IN CONNECTION WITH SUCH CLAIM, INCLUDING BUT NOT LIMITED TO ATTORNEYS’ FEES AND COSTS.
8. IN NO EVENT WILL THE AGGREGATE LIABILITY OF THE COMPANY AND THE COMPANY PARTIES (JOINTLY), IN ANY ACTION OR CLAIM, WHETHER IN CONTRACT, WARRANTY, TORT (INCLUDING NEGLIGENCE, WHETHER ACTIVE, PASSIVE OR IMPUTED), OR OTHER THEORY, ARISING OUT OF OR RELATING TO THE NETWORK, THESE TERMS OF USE EXCEED THE GREATER OF: (I) THE AMOUNT YOU PAID TO US FOR THE USE OF THE NETWORK WITHIN THE 12-MONTH PERIOD PRECEDING THE EVENT GIVING RISE TO SUCH ACTION OR CLAIM; OR (II) 100 DOLLARS.

#### 21. Indemnification
YOU AGREE TO INDEMNIFY AND HOLD HARMLESS THE COMPANY PARTIES FROM AND AGAINST ANY AND ALL LOSS, EXPENSES, DAMAGES, AND COSTS, INCLUDING WITHOUT LIMITATION REASONABLE ATTORNEYS’ FEES, RESULTING, WHETHER DIRECTLY OR INDIRECTLY, FROM YOUR VIOLATION OF THESE TERMS OF USE. YOU ALSO AGREE TO INDEMNIFY AND HOLD HARMLESS THE COMPANY PARTIES FROM AND AGAINST ANY AND ALL CLAIMS BROUGHT BY THIRD PARTIES ARISING OUT OF YOUR USE OF THE NETWORK.

#### 22. Dispute Resolution, Arbitration; Jurisdiction
PLEASE READ THE FOLLOWING SECTION CAREFULLY BECAUSE IT REQUIRES YOU TO ARBITRATE CERTAIN DISPUTES AND CLAIMS WITH THE COMPANY AND LIMITS THE MANNER IN WHICH YOU CAN SEEK RELIEF FROM US.

1. **Binding Arbitration.** Except for any disputes, claims, suits, actions, causes of action, demands or proceedings (collectively, “Disputes”) in which either Party seeks injunctive or other equitable relief for the alleged unlawful use of intellectual property, including, without limitation, copyrights, trademarks, trade names, logos, trade secrets or patents, you and the Company (i) waive your and our respective rights to have any and all Disputes arising from or related to these Terms of Use resolved in a court, and (ii) waive your and our respective rights to a jury trial. Instead, you and the Company will arbitrate Disputes through binding arbitration pursuant to the Federal Arbitration Act, 9 U.S.C. § 1 et seq. (“FAA”), to the maximum extent permitted by applicable law. (This includes the referral of a Dispute to one or more persons charged with reviewing the Dispute and making a final and binding determination to resolve it instead of having the Dispute decided by a judge or jury in court).
2. **No Class Arbitrations, Class Actions or Representative Actions.** Any Dispute arising out of or related to these Terms of Use is personal to you and the Company and will be resolved solely through individual arbitration and will not be brought as a class arbitration, class action or any other type of representative proceeding. THERE WILL BE NO CLASS ARBITRATION OR ARBITRATION IN WHICH AN INDIVIDUAL ATTEMPTS TO RESOLVE A DISPUTE AS A REPRESENTATIVE OF ANOTHER INDIVIDUAL OR GROUP OF INDIVIDUALS. FURTHER, A DISPUTE CANNOT BE BROUGHT AS A CLASS OR OTHER TYPE OF REPRESENTATIVE ACTION, WHETHER WITHIN OR OUTSIDE OF ARBITRATION, OR ON BEHALF OF ANY OTHER INDIVIDUAL OR GROUP OF INDIVIDUALS.
3. **Notice; Informal Dispute Resolution.** Each Party will notify the other Party in writing of any Dispute within thirty (30) calendar days of the date it arises, so that the Parties can attempt in good faith to resolve the Dispute informally. Notice to the Company shall be sent by email to {EMAIL ADDRESS}. Notice to you shall be by email to the then-current email address in your Account. Your notice must include (i) your name, postal address, email address and telephone number, (ii) a description in reasonable detail of the nature or basis of the Dispute, and (iii) the specific relief that you are seeking. If you and the Company cannot agree how to resolve the Dispute within thirty (30) calendar days after the date notice is received by the applicable Party, then either you or the Company may, as appropriate and in accordance with these Terms of Use, commence an arbitration proceeding or, to the extent specifically provided for in these Terms of Use, file a claim in court.
4. **Process.** Any arbitration will occur in the State of {STATE OF BUSINESS}. Arbitration will be conducted confidentially by a single arbitrator in accordance with the rules of the Judicial Arbitration and Mediation Services (“JAMS”), which are hereby incorporated by reference. The state and federal courts located in the State of {STATE OF BUSINESS} will have exclusive jurisdiction over (i) any appeals and the enforcement of an arbitration award, or (ii) any claim filed in court where permitted in these Terms of Use.
5. **Authority of Arbitrator.** As limited by the FAA, these Terms of Use and the applicable JAMS rules, the arbitrator will have (i) the exclusive authority and jurisdiction to make all procedural and substantive decisions regarding a Dispute, including the determination of whether a Dispute is arbitrable, and (ii) the authority to grant any remedy that would otherwise be available in court; provided, however, that the arbitrator does not have the authority to conduct a class arbitration or a representative action, which is prohibited by these Terms of Use. The arbitrator may only conduct an individual arbitration and may not consolidate more than one individual’s claims, preside over any type of class or representative proceeding or preside over any proceeding involving more than one individual.
6. **Rules of JAMS.** The rules of JAMS and additional information about JAMS are available on the JAMS website. By agreeing to be bound by these Terms of Use, you (i) acknowledge and agree that you have read and understand the rules of JAMS, or (ii) waive any claim that the rules of JAMS are unfair or should not apply for any reason.
7. **Injunctions and Court Proceedings.** With respect to any action or proceeding for which courts are an expressly permitted method of adjudication hereunder (set forth below), the exclusive jurisdiction and venue for all such actions or proceedings arising out of, or related to, these Terms of Use will be in an appropriate state or federal court located in the State of {STATE OF BUSINESS} (the “Designated Courts”), and the Parties hereby irrevocably consent to the personal and subject matter jurisdiction of such court, waive any claim that such courts do not constitute a convenient and appropriate venue for such actions or proceedings, and waive the right to a trial by jury. Each Party consents to service of process upon itself by means of any of the methods for delivery of notice that are specified in these Terms of Use. To the extent that arbitration pursuant to the Terms of Use of this section is not permitted under applicable law, a Party may seek resolution of any dispute in the Designated Courts. To the extent the Parties mutually agree to forego arbitration for a dispute, the Parties shall seek resolution of such dispute in the Designated Courts. Permitted appeals and/or enforcement of an arbitration award shall take place in the Designated Courts. Certain breaches of these Terms of Use by a User may, by its gravity or nature, cause immediate and irreparable injury to the Company that cannot be adequately compensated for in damages, including, without limitation, infringement of the Company’s intellectual property rights. Accordingly, notwithstanding anything to the contrary in these Terms of Use, in the event of any such breach and in addition to all other remedies available herein, the Company may seek solely injunctive relief from the Designated Courts without posting a bond or other security.

#### 23. Governing Law
These Terms of Use will be governed by and construed and enforced in accordance with the laws of the State of {STATE OF BUSINESS}, without regard to conflict of law rules or principles (whether of the State of {STATE OF BUSINESS} or any other jurisdiction) that would cause the application of the laws of any other jurisdiction.

#### 24. Waiver and Severability
No waiver by the Company of any term or condition set out in these Terms of Use shall be deemed a further or continuing waiver of such term or condition or a waiver of any other term or condition, and any failure of the Company to assert a right or provision under these Terms of Use shall not constitute a waiver of such right or provision. If any provision of these Terms of Use is held by a court or other tribunal of competent jurisdiction to be invalid, illegal or unenforceable for any reason, such provision shall be eliminated or limited to the minimum extent such that the remaining provisions of these Terms of Use will continue in full force and effect.

#### 25. Notice
1. **To you:** We may provide any notice to you under these Terms of Use by: (i) posting a notice on our Network; or (ii) sending an email to the email address then associated with your account. Notices we provide by posting on our Network will be effective upon posting and notices we provide by email will be effective when we send the email. It is your responsibility to keep your email address current. You will be deemed to have received any email sent to the email address then associated with your account when we send the email, whether or not you actually receive or read the email.
2. **To us:** To give us notice under these Terms of Use, you must contact us by email at {EMAIL ADDRESS}. We may update this email address for notices to us by posting a notice on our website or sending an email to you. Notices to us will be effective when received by us.
3. **Language:** All communications and notices to be made or given pursuant to these Terms of Use must be in the English language.

#### 26. Entire Agreement
These Terms of Use constitute the sole and entire agreement between you and the Company regarding the Network and supersede all prior and contemporaneous understandings, agreements, representations, and warranties, both written and oral, regarding the Company.

#### 27. Force Majeure
The Company and its affiliates will not be liable for any delay or failure to perform any obligation under these Terms of Use where the delay or failure results from any cause beyond our reasonable control, including (without limitation) acts of God, labor disputes, or other industrial disturbances, electrical, telecommunications, hardware, software or other utility failures, earthquake, storms or other elements of nature, pandemic or epidemic, blockages, embargoes, riots, acts or orders of government, acts of terrorism, war, or any other force, event or condition outside of our control.

#### 28. Creative Commons Sharealike License
We’ve adapted these Terms of Use from Wordpress’ terms of service. These Terms of Use are consequently available under a [Creative Commons Sharealike license](https://creativecommons.org/licenses/by-sa/4.0/), which means that you’re more than welcome to copy them, adapt them, and repurpose them for your own use. Just make sure to revise them so that your own terms of service reflect your actual practices.

#### 29. Your Comments and Concerns
Please direct questions or concerns regarding these Terms of Use or the Network to us at {EMAIL ADDRESS}.

### DMCA policy
We do not condone nor authorize activities that infringe copyright or intellectual property rights. We will remove any infringing content if properly notified that such content infringes on another’s copyrights. A copyright owner or an agent thereof may notify us of any copyright infringement on the Network by submitting a notification pursuant to the Digital Millennium Copyright Act (“DMCA”) by providing our designated copyright agent with the following information in writing (see 17 U.S.C. 512(c)(3) for further detail):

1. An electronic or physical signature of the person authorized to act on behalf of the owner of the copyright’s interest;
2. A description of the copyrighted work that you claim has been infringed;
3. A description of the material that you claim is infringing and where it is located on the Network;
4. Identification of the URL or other specific location where the material that you claim is infringing is located;
5. Your address, telephone number, and email address;
6. A statement by you that you have a good faith belief that the disputed use is not authorized by the copyright owner, its agent, or the law; and
7. A statement by you, made under penalty of perjury, that the above information in your notice is accurate and that you are the copyright owner or authorized to act on the copyright owner’s behalf.

You can contact our designated copyright agent via email at {EMAIL ADDRESS}.

We reserve the right to terminate the account of any user that it determines is a “repeat infringer”. A repeat infringer is a user who has repeatedly been notified of infringing activity and/or has had content repeatedly removed.
',
            CustomPageTypesEnum::COMMUNITY_GUIDELINES->value => '### General community guidelines

This community runs on Minds Networks, which enables community administrators to set and enact their own content and moderation policies. Underlying those community-specific policies is a general content policy based on the United States First Amendment and US law.

#### General content policy
The underlying general content policy prohibits spam and content that is illegal under US law.

##### Spam
Spam may take many forms, and the policy is intended to cover a range of prohibited behavior, including:

- Repeated, unwanted, and/or unsolicited actions, automated or manual, that negatively impact the community.
- Content that is designed to further unlawful acts (such as phishing) or mislead recipients as to the source of the material (such as spoofing).
- Commercially-motivated spam, that typically aims to drive traffic from the network over to another website, service, or initiative through backlinking or other inauthentic methods.
- Inauthentic engagements, that e.g., try to make channels or content appear more popular than they are.
- Coordinated activity, that attempts to artificially influence opinion through the use of multiple accounts, fake accounts, and/or scripting or automation.

##### Illegal content
Content that is illegal under US law is not permitted on the network and will result in a ban of the offending account. Illegal content categories include:

- Intellectual property violations
- Incitement to violence
- Terrorism
- Trafficking
- Revenge porn
- Sexualization of minors
- Extortion
- Fraud
- Animal abuse

#### Moderation
##### Community reports
All registered members of the community can generate reports of content and user accounts. Upon reporting content or accounts, the reporter specifies the alleged offense. Reports then go to a moderation queue where network moderators review the reports and determine both (a) their validity, and (b) the consequences of the violations.

##### Moderators
The network administrator can act as a moderator, and can optionally enable other members of the community to also act as moderators. Moderators have the ability to directly delete content, ban accounts, as well as review a queue of community-generated reports.

##### Minds
Community-generated reports of illegal activity may be reviewed and acted on by Minds staff. If the report is valid, offending user accounts will be banned.
',
        ];
    }
}
