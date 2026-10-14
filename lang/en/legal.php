<?php

return [
    'updated_label' => 'Last updated',
    'updated' => 'October 2026',
    'contact_title' => 'Contact',
    'contact_text' => 'Questions about these terms or your data? Reach us here:',
    'contact_none' => 'Use the contact details shown on our website.',
    'footer' => ['privacy' => 'Privacy policy', 'terms' => 'Terms of use', 'contact' => 'Contact'],

    'privacy' => [
        'title' => 'Privacy policy',
        'intro' => 'Sofra helps shops publish a digital menu and receive orders on WhatsApp. This page explains what personal data we handle and why. We keep it to the minimum the service needs.',
        'sections' => [
            ['title' => 'What we collect', 'body' => [
                'Shop owners: your name, email address, a hashed password (we never see or store it in plain text), and the shop details you enter — name, logo, products, prices, photos and WhatsApp number.',
                'Customers who view a menu: no account is needed and we do not ask for personal data to browse. If a customer leaves a review, the rating and note are saved, plus the name, phone/email and photo only if they choose to add them.',
                'Orders: when a customer sends an order, the message is prepared and opened in WhatsApp. Sofra does not store the contents of orders.',
                'Insights: Sofra only counts how many times a menu or product was opened, added to a cart, or the WhatsApp button was pressed, per shop and per day. We do not store visitor identity, IP address or cart contents, and visitors who send "Do Not Track" are not counted. The shop owner chooses how long these counters are kept, then they are deleted automatically.',
                'Subscription payments: if you pay by card, the payment is handled by Stripe on its own secure page. Sofra never sees or stores your card number; we keep only Stripe\'s customer and subscription reference and whether your payments succeeded.',
                'Technical data: a session cookie, a security token, and the visitor’s IP address for abuse prevention (for example rate limits).',
            ]],
            ['title' => 'How we use it', 'body' => [
                'To run your account and menu, deliver reviews to the shop owner by email and in the dashboard, keep the service secure, and contact you about your subscription or important changes.',
                'We do not sell personal data and we do not show advertising.',
            ]],
            ['title' => 'Who can see it', 'body' => [
                'A shop owner sees the reviews left on their own shop only. Our team may access data to provide support or prevent abuse.',
                'We rely on service providers for hosting and email delivery. WhatsApp is a separate service run by its own company with its own policy.',
            ]],
            ['title' => 'Cookies', 'body' => [
                'We only use essential cookies: to keep you signed in, to protect forms, and to remember your language. We do not use advertising or tracking cookies.',
            ]],
            ['title' => 'Retention and deletion', 'body' => [
                'We keep account and shop data while your account is active. Ask us to delete your account and we will remove your shop, products, images and reviews. Shop owners can delete individual reviews at any time.',
            ]],
            ['title' => 'Your rights', 'body' => [
                'You may ask to access, correct or delete your personal data, or object to its processing, under the applicable law, including the Turkish Personal Data Protection Law (KVKK) and, where relevant, the GDPR. Contact us using the details below.',
            ]],
            ['title' => 'Changes', 'body' => [
                'If we change this policy in a meaningful way we will update the date above and, where appropriate, notify you.',
            ]],
        ],
    ],

    'terms' => [
        'title' => 'Terms of use',
        'intro' => 'By creating an account or using Sofra you agree to these terms. Please read them; they are short.',
        'sections' => [
            ['title' => 'The service', 'body' => [
                'Sofra lets a shop publish a digital menu with a link and QR code, and lets customers prepare an order that opens in WhatsApp. Sofra is not a party to orders and does not handle payments between shops and their customers.',
            ]],
            ['title' => 'Your account', 'body' => [
                'You are responsible for the accuracy of the information you provide, for keeping your password safe, and for activity under your account. You must be allowed to run the shop you register.',
            ]],
            ['title' => 'Free trial and subscription', 'body' => [
                'New shops get a free trial period. After it ends, the subscription is :price per month, paid manually in agreement with our team. We will tell you before changing the price, and a change never applies to a period you already paid for.',
                'Your menu is not switched off automatically when the trial ends, but we may suspend a shop whose subscription stays unpaid after we have contacted you.',
            ]],
            ['title' => 'Your content', 'body' => [
                'You own what you upload (names, photos, descriptions, prices). You give us permission to store and display it as needed to run the service. You are responsible for it: it must be lawful, accurate and must not infringe anyone’s rights.',
                'You are responsible for how you handle customer reviews and any personal data in them.',
            ]],
            ['title' => 'Acceptable use', 'body' => [
                'Do not misuse the service: no illegal or misleading content, no attempts to break security, overload or scrape the platform, and no spam through the review form.',
            ]],
            ['title' => 'Availability', 'body' => [
                'We work to keep Sofra available but cannot promise uninterrupted service. We may update or improve features at any time.',
            ]],
            ['title' => 'Suspension and ending', 'body' => [
                'You may stop using Sofra and ask us to delete your account at any time. We may suspend or close accounts that break these terms or the law.',
            ]],
            ['title' => 'Liability', 'body' => [
                'To the extent the law allows, Sofra is provided “as is” and we are not liable for indirect losses, lost sales or disputes between a shop and its customers.',
            ]],
            ['title' => 'Governing law and changes', 'body' => [
                'These terms are governed by the laws of the Republic of Türkiye. We may update them; continuing to use Sofra after an update means you accept the new version.',
            ]],
        ],
    ],
];
