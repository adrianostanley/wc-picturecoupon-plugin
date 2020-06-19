# WooCommerce Picture Coupon Plugin

Here's a "brief" explanation about my decisions and guidelines to develop the requested plugin.

Along with the next paragraphs, I tried to add to the explanation some ideas for having a better plugin
in terms of usability, security, and the coding standards itself. Those are things I wanted to do but tried
to keep the plugin short and simple considering that its main purpose is to demonstrate some of my skills.

## Compatibility

WooCommerce Picture Coupon Plugin was developed in a PHP 7.4.5 environment with WordPress 5.3.2. For the sake
of simplicity, I didn't spend time with compatibility with older versions of PHP, although I haven't used
its new features to prevent errors if it will be tested by you in lower versions.

In order to allow users to create an account, I've checked both options in the WooCommerce settings
below. It was not clear to me if the plugin should warn admin users about that in case they're unchecked,
but to keep it simple, I've selected them manually. 

* WooCommerce > Settings > Account & Privacy
  * Allow customers to create an account during checkout
  * Allow customers to create an account on the "My Account" page

**Note:** Although I have used `__('...')` functions, there are no i18n files for the WooCommerce Picture Coupon Plugin.

## Approach / Reasoning to the Solution

Before entering in details about the development of the project, I took some guidelines for myself to be
followed during this week of development. Due to time constraints and the main purpose of this project, I
got to stick to these points in order to deliver an easy-to-understand code as well as with the best practices
as possible.

So, these are the things in my mind while developing the Picture Coupon plugin:

* Use WordPress and WooCommerce API actions and filters whenever they're available to achieve the plugin
requirements;
* Don't create new tables at this time, only if extremely necessary to keep the plugin simple;
* Treat the profile picture domain classes as objects;
* Keep the plugin code simple and easy to read - via the documentation above each function and even the
code itself;
* Observe how SkyVerge had developed Jilt and its framework plugins to make an attempt to release code
in compliance with its standards;
* Don't spend a lot of time with the layout and the usability itself. Although they are very important,
especially in a plugin used by users with good and bad WordPress skills, I prefer to dedicate my time to
its architecture and in the code standards.
* Register all the improvements I'd do if I had the time to implement them. It's important to explain the
reasons for the solution, but it's also very important to figure out how they would be better in a real project.

To achieve the plugin requirements based on my guidelines, I made use of basically seven classes:

* `WCPC_Loader` and `WCPC_Setup`: they fire up the plugin, by loading its classes, adding actions and
filters as well as building the integration with WooCommerce. They're both singleton classes, although
`WCPC_Setup` has a public constructor to allow WooCommerce to instantiate it (which is something I'd love
to refactor if I had more time to find a more elegant solution);
* `WCPC_Functions`: concentrates all the core functions of Picture Coupon plugin;
* `WCPC_Uploader`: the uploader is a view component to upload, restore and remove profile pictures;
* `WCPC_Rest_API_Controller`: this is a simple class with two endpoints added via WordPress API functions;
* `WCPC_Picture` and `WCPC_History`: described below.

### `WCPC_Picture` and `WCPC_History` classes

The main objects of the core are represented by `WCPC_Picture` and `WCPC_History`. A picture is just a way
to encapsulate the picture ID which is stored in `wp_posts` after being uploaded by `WCPC_Uploader`. It has
a few helper methods to retrieve basic information like the file name, extension, and public URL.

Instead of storing the user's profile picture and older profile pictures in separated structures, I chose
to work with a single object representing the user's history - `WCPC_History`. The history is a list of
uploaded pictures by the user, even if they didn't set them as their main profile picture. That way, the
most recent picture is the one that the plugin will consider as the selected.

When a user chooses to restore a previously uploaded picture, the history just manages its pictures list
to move the selected one to the end of it. With that structure, I was able to achieve both **Future Considerations**
"Remove their profile images" and "Replace their profile images".

`WCPC_History` is also the only source of the user's profile picture for the rest of the plugin functionalities.
Whenever the plugin needs to retrieve the user's profile picture or even the stored pictures, it just has
to load the user's history by the user ID and call the respective methods.

Finally, `WCPC_History` data is stored in the `wp_usermeta` table. Besides the history class, the other structures
don't even have to deal with that nor even know where is the data coming from. I've made sure `WCPC_History`
encapsulates all the logic and data manipulation. If a future version of this plugin would be making use of
a dedicated table in the database for storing the user's profile pictures data, this class is the only one that would have
to be adapted.  

### Filters and actions instead of reinventing the wheel

For each acceptance criteria, I was looking for filters and actions in order to make it more integrated
with WooCommerce and WordPress.

For example, instead of creating a settings page to allow admins to set the max number of profile pictures,
I made use of the `WC_Integration->form_fields` to be available in the WooCommerce Integrations pages.

In this same approach, the criteria "admins can see all available customer profile images on a customer's
user profile page" was achieved by `edit_user_profile` action so a section is added to the WordPress
default user profile page.

### Acceptance Criteria

For each of the following acceptance criteria, I'll give a brief description of how it was achieved.

It's necessary to point that for the sake of the simplicity of this research project demonstration along
with the time constraints, I chose to use most of WordPress and WooCommerce resources. The **how can I
improve this** section below each criterion shows how I would handle them in a real scenario.

#### 1 - Customers can upload multiple profile pictures

![Account detals](https://i.imgur.com/qezR1ef.jpg)

**Navigate to:** My Account > Account details > Change your profile picture (first section).

The `WCPC_Uploader` encapsulates all the view components for uploading, restoring, and removing a picture.

I chose to use a simple approach in this case which was the browser's default file uploader inside an HTML
form that is handled by `WCPC_Uploader`, which stores the uploaded file(s) and calls the attachment
functions provided by WordPress to store them in the proper tables like `wp_posts`.

After this process, the profile picture has an ID, which is sent to the user's history to be stored as one
of his/her pictures. 

**How can I improve this?** By using a better file uploader, preferably one with asynchronous requests,
loading bars, and friendly error messages to warn users about bad dimensions for a profile picture, heavy
files, etc. One of those widgets to cut pictures to make them square would also be recommended to prevent
users to upload pictures with rectangular dimensions.

#### 2 - Customers can change their primary profile image

![Cart widget](https://i.imgur.com/CopeojU.jpg)

![Profile picture management](https://i.imgur.com/DnvsVrc.jpg)

**Navigate to:** My Account > Account details > Change your profile picture (first section).

When the user's history contains more than 1 picture, the "older" ones are shown in a table so the users
can click on the "Use this" link.

At this moment, a JavaScript function is called to set the picture ID in another form which is passed again
to `WCPC_Uploader`. The uploader transfers the ID to `WCPC_History` which is our domain class so it can
replace the current profile picture with an older one.

**Note:** If the maximum number of profile images is set by an admin with a lower value than a user's
history, the images are not removed. However, from this time he/she won't be able to upload more
profile pictures.

**How can I improve this?** I'd create a component with better usability than a table with links and
asynchronous requests when a user replaces or remove a picture.

#### 3 - Admins can see all available customer profile images on a customer’s user profile page

![User profile](https://i.imgur.com/GOmZZ4d.jpg)

**Navigate to:** WordPress dashboard (wp-admin) > Users > select a user > scroll down to "Profile pictures" section.

The `edit_user_profile` action allows me to add content to the WordPress default user profile page.
By doing so, I used the resources provided by `WCPC_History` to load the user profile pictures. 

**How can I improve this?** By creating a custom user profile page that would focus on
the plugin features added to WordPress default users.

#### 4 - Admins can see the primary selected profile picture on a customer’s order

![User's profile picture at the time of checkout](https://i.imgur.com/QGSZ9t2.jpg)

**Navigate to:** WordPress dashboard (wp-admin) > WooCommerce > Orders > click on the order > the image
is shown in a right side meta box called "User's Profile Picture".

This was achieved by an action called `add_meta_boxes` and a WordPress API function called `add_meta_box`.

To store the profile picture of the user at the moment of the checkout, I made use of a WooCommerce action
called `woocommerce_checkout_create_order` which allows me to call `update_meta_data` on the order instance
while it's stored.

The user's primary picture ID is then stored in `wp_postmeta` since orders are stored as posts. If the user replaces
his profile picture or even removes it from the history, the ID will still point to the right picture
at the checkout since at this point, it's not bound with the user's settings anymore.

**How can I improve this?** It depends on the amount of information that will be stored, but in a scenario
where the plugin stores tons of metadata, I'd create a dedicated table instead of using the WordPress
`wp_postmeta`. 

#### 5 - Admins can restrict the maximum number of profile images a user can have from within the plugins admin settings

![Plugin settings](https://i.imgur.com/JiDVSKJ.jpg)

**Navigate to:** WordPress dashboard (wp-admin) > WooCommerce > Settings > Integration tab >
WooCommerce Picture Coupon Plugin link.

This was achieved at the moment of the plugin setup by `WCPC_Setup` via the `init_form_fields()` function
by adding a custom form field in `$this->form_fields`.

The user's history (`WCPC_History`) makes use of this field to prevent new pictures to be added and the
`WCPC_Uploader` shows a friendly message to users regarding the limit reached.

**How can I improve this?** If the plugin grows with the need for complex settings groups and tabs, I'd
consider creating its own settings page using the WordPress API.

#### 6 - When visiting a previous order the admin will see the profile image set as the default at the time of that order

The description in **4 - Admins can see the primary selected profile picture on a customer’s order** already
covers this criterion. 

#### 7 - There is a REST API endpoint that returns the name, file type, associated user, and public URL for all customer profile images

![Rest API call](https://i.imgur.com/VA1MS1w.jpg)

`WCPC_Rest_API_Controller register_routes` function adds two endpoints:

* `/profile-pictures/all` returns a JSON with all users with their histories that contain all of their
profile pictures;
* `/profile-pictures/user_id` returns a JSON with the history of a user where `user_id` must be an integer
bigger than zero and be a valid user ID.

Both endpoints call functions in the `WCPC_Rest_API_Controller` as well and they make use of the
`WCPC_History` functions to retrieve and return users' histories data.

**How can I improve this?** By creating a controller with a better design as seen in Jilt and the 
SkyVerge framework plugins. I have to say it was the first time I had to release endpoints via WordPress
API and although I read a few about how a controller must be designed, I chose to keep it simple and
working for the sake of this research project delivery.

#### 8 - Only authenticated users can access image information over the API

Unfortunately, I wasn't able to achieve this with the basic WordPress REST API endpoints. Although
`WCPC_Rest_API_Controller` has a function called `is_allowed()` which is being passed as the
`permission_callback` in `register_rest_route`, I couldn't find a way in time to authenticate
the requests by including a nonce parameter as suggested by its documentation. I left the function returning
`true` so the endpoints can be used.

I was also taking a look at `WC_Jilt_REST_Settings_Controller`, `WC_REST_Controller`, and how `wc_rest_check_manager_permissions`
works to get a better idea on how SkyVerge does that but had no success to test that in my development
environment.

**How can I improve this?** Using better authentication methods like OAuth2.

## Code Implementation Decisions

### Formatting defaults

To make sure my code complied with some SkyVerge standards, I tried to make its structure close
to some SkyVerge WooCommerce plugins code, like Jilt.

### Classloading

In my initial development, I was using Composer autoload to load classes. During some refactoring and
to make sure I was in the right direction of following SkyVerge standards, I've decided to load
them inside the `WCPC_Loader`. For a bigger plugin with a higher number of classes, I would reconsider
the autoload standards.

## Improvements

Here's a list of functionalities and improvements I would work on if the plugin was about to be
released as a real project:

* Better separation of actions and filters functions instead of all of them being located in `WCPC_Functions`;
* Separate the HTML generation from some logics in my domain classes;
* Database cleaner methods to remove stored data when the plugin is uninstalled;
* Better design for the Rest API endpoints controller and, of course, fixing its authentication;
* Huge maintenance in the user widgets to improve usability, especially for the uploader components;
* Improve the plugin compatibility, since it was developed with a PHP 7.4.5 version and it should work
even in lower versions, preferrable from 5.6.0;
* Show warnings in the WordPress admin dashboard if the WooCommerce settings for allowing customers to
create accounts are disabled;
* i18n files.