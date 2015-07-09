# Moodec

Moodec is a **work-in-progress** eCommerce storefront plugin for Moodle. Users are able to browse through a catalogue of courses and add them to a cart, which they can purchase and enrol into via Paypal.


## Requirements

1. [Moodec Enrolment plugin](https://github.com/Regaez/moodle-enrol_moodec) (enrol_moodec) version 2014111000
2. Users must have an account to purchase courses, so self-registration is recommended to be enabled


## Configuration

There are a couple of steps to setting up your eCommerce environment.

### General settings

In the Site Administration block, under *Moodec > General settings*.  

1. **Business email** - enter your Paypal account email address here
2. **Currency** - Select the currency in which you wish to sell your products
3. **Courses per page** - The number of courses to be displayed on the catalogue page before pagination is activated.

### Page settings

In the Site Administration block, under *Moodec > Page settings*. You can configure the pages created by the plugin by enabling and disabling elements, such as product description, course image etc.

### Course Enrolment Method settings

You will also need to ensure that the **Moodec enrolment method** is enabled for the course, with the settings configured appropriately.

### Product settings

In order to enable a course for sale, you must navigate to the course page and in the Administration block, select *Course administration > Edit product settings*. You must tick the **Enable** checkbox to allow the product to show up in the store and be able to be bought.

Next select a *pricing model*, either **simple** or **variable**. 

1. **Simple products** have a single price, course enrolment duration and group.
2. **Variable products** can be configured to have up to 5 tiers. You can specify a name, price, enrolment duration and group for each tier.

#### Course description and image

The course description is inherited from Moodle's default course summary field, which can be found in *Course administration > Edit settings*. The product image is also pulled from the default course summary files section in the same settings page.

You can also add additional product information, under the *More settings* section when editing the course's product settings.


## Issues

As this is still work in progress, there are some known issues. However, feel free to report any you bugs you find. 

**NOTE:** it is recommended to only use this plugin in a test environment.