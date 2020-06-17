# WooCommerce Picture Coupon Plugin

## Approach / Reasoning to the Solution

### Pictures and History

(wip)

### Filters and actions instead of reinventing the wheel

For each acceptance criteria, I was looking for filters and actions in order to make it more integrated with WooCommerce and WordPress itself than independent.

For example, instead of creating a settings page to allow admins to set the max number of profile pictures, I made use of the `WC_Integration form_fields` to be available in the WooCommerce Integrations pages.

In this same approach, the criteria "admins can see all available customer profile images on a customer's user profile page" was achieved by `edit_user_profile` action so a section is added to the WordPress default user profile page.

## Code Implementation Decisions

### Formatting defaults

To make sure my code was in compliance with some SkyVerge patterns, I tried to make its structure close to The official SkyVerge WooCommerce plugin framework code as well as the Jilt for WooCommerce plugin.

### Class loading

In my initial development, I was using Composer autoload to load classes. During some refactoring and to make sure I was following the same intention of following SkyVerge standards, I've decided to load them inside the `WCPC_Loader`. For a bigger plugin with a higher number of classes, I would reconsider the autoload standards.