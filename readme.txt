=== Syscoin WordPress Plugin ===
Contributors: Syscoin
Tags: e-commerce, syscoin, description generation, ai
Requires at least: WordPress 5.0
Tested up to: 6.5.5
Stable tag: 1.3.1
Requires PHP: 7.0.0
License: GPL v2

== Description ==
The Syscoin WordPress Plugin is a versatile tool designed to seamlessly integrate the services of Syscoin's mobile app and website into your WordPress site. Enhance your e-commerce functionality and user experience by connecting your Syscoin account with your WordPress site.

== Frequently Asked Questions ==
= How do I connect my Syscoin account? =
- After installing the plugin, go to Syscoin Settings in your WordPress dashboard and enter your username and password.

== Changelog ==
= 1.3.0 =
- New feature: do a full check-up of your website using our new Diagnostics feature to find potential issues of efficiency and security.
- Lots of bug fixes and general improvements.

== Additional Notes ==
- This plugin requires a Syscoin account and works alongside the WooCommerce plugin.
- This plugin relies on the Syscoin/DashCommerce API to exchange information related only to the functioning of the plugin, specifically:
- - To send Syscoin credentials and retrieve account information, including a token that identifies your instance of the plugin;
- - To send this token periodically to keep the plugin connection active, and to revoke it when the user logs out of the plugin;
- - To retrieve, at the user's request, AI-generated product descriptions, by sending product information such as name, categories, tags, and variations;
- - To save a user-provided OpenAI API key, which is used to access the AI-related features of the plugin.
- This plugin relies, indirectly and through our own API, on the OpenAI API for AI-related features. Their Privacy Policy is available at https://openai.com/enterprise-privacy
- This plugin records data related to the accesses to your website. This data is used to provide analytics and statistics.

- For more information and support, visit https://syscoin.com.br/quem-somos/ or contact atendimento@syscoin.com.br.
- For detailed information on how we handle user data, please refer to our Privacy Policy at https://syscoin.com.br/privacy/.

- The Syscoin plugin relies on DashCommerce for most functions. The DashCommerce company is part of Syscoin and their privacy policy is the same as ours.
- The domains "https://us-central1-dashcommerce-app.cloudfunctions.net/" and "https://dashcommerce.com.br/", referenced in this plugin, are part of the DashCommerce/Syscoin ecosystem.
- You can find the DashCommerce privacy policy at https://dashcommerce.app/privacy/.

