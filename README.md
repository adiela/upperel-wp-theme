<h1 align="center">
  <img src="https://storefront.files.wordpress.com/2017/02/storefront1.png?w=100&h=100&crop=1" alt="Storefront">
</h1>

<p align="center">
  <img src="https://poser.pugx.org/woocommerce/woocommerce/license" alt="license">
  <a href="https://woocommerce.com/"><img src="http://img.shields.io/badge/Designed%20for-WooCommerce-a46497.svg" alt="Designed for WooCommerce"></a>
  <img src="https://img.shields.io/wordpress/theme/dt/storefront.svg" alt="WordPress.org downloads">
  <img src="https://img.shields.io/wordpress/theme/r/storefront.svg" alt="WordPress.org rating">
</p>

_Storefront_ is a robust and flexible [WordPress](https://wordpress.org) theme, designed and built by the team at [WooCommerce](https://woocommerce.com/) to help you make the most out of using the [WooCommerce](https://woocommerce.com) plugin to power your online store. It's available to download for free from the WordPress [theme repository](https://wordpress.org/themes/storefront/).

It features deep integration with WooCommerce core plus several of the most popular extensions:

-   [WooCommerce Bookings](https://woocommerce.com/products/woocommerce-bookings/)
-   [WooCommerce Wishlists](https://woocommerce.com/products/woocommerce-wishlists/)
-   [WooCommerce Brands](https://woocommerce.com/products/brands/)
-   [WooCommerce Subscriptions](https://woocommerce.com/products/woocommerce-subscriptions/)

For developers, Storefront is the perfect starting point for your project. Its lean and extensible codebase will allow you to easily add functionality to your site via a child theme and/or custom plugin(s).

## Upperel customizations

Upperel is based on Storefront and adds the theme-specific features below.

### Custom blocks

Custom blocks live in `blocks/<name>/` and are registered in `inc/upperel-blocks.php`. They need no JavaScript build step: each editor script uses the global `wp.*` packages, and its dependencies are listed in the matching `*.asset.php` file. Block stylesheets are in `assets/css/blocks/`, so the normal `npm run build:css` compiles them, adds vendor prefixes and generates the RTL versions.

> **Note:** `.gitignore` excludes compiled `.css` files. Run `npm run build:css` (or `npm run build`) after cloning and before deploying, or the blocks will have no styles.

#### Image Slider (`upperel/image-slider`)

A slider that always fills the full width of the screen, even inside a narrower content column. Each slide can link to a different page.

**Using it in the editor**

1. Add the **Image Slider** block (it's in the Media category; search "slider").
2. Upload or pick several images at once.
3. Click a thumbnail under the preview to select that slide. In the block sidebar:
    - **Link to**: search for a page by name or paste any URL. Leave it empty for no link.
    - **Open in new tab**: open the link in a new tab.
    - **Alternative text**: describe the image for screen readers.
4. Use the block toolbar to replace the selected image, move the slide earlier or later, or remove it. The **+** button under the preview adds more images.

**Slider settings**

| Setting | Default | Notes |
| --- | --- | --- |
| Height (desktop) | 70% of screen height | Applies at 768px wide and above; minimum 240px |
| Height (mobile) | 50% of screen height | Applies below 768px; minimum 240px |
| Transition | Slide | Slide or Fade |
| Autoplay | On | Off for visitors whose device is set to reduce motion |
| Seconds per slide | 5 | 2–30 |
| Show arrows / dots | On | Hidden when there is only one slide |

The block also supports top and bottom margins and an HTML anchor.

**On the site**

- Visitors can use the arrows or dots, swipe on touch screens, or press the left and right arrow keys. A swipe never opens the slide's link.
- Autoplay pauses while the slider is hovered or focused, or the browser tab is hidden. When autoplay is on, a pause/play button appears next to the dots.
- Images are cropped from the centre to fill the slider height.
- Only the first image loads straight away; each later image loads just before it's shown.

**Files**

| File | Purpose |
| --- | --- |
| `blocks/image-slider/block.json` | Block settings and attributes |
| `blocks/image-slider/index.js` | Editor interface |
| `blocks/image-slider/render.php` | Front-end markup (rendered on the server) |
| `blocks/image-slider/view.js` | Front-end slider behaviour |
| `assets/css/blocks/image-slider.scss` | Front-end styles |
| `assets/css/blocks/image-slider-editor.scss` | Editor-only styles |

## Storefront Status

Please read [this document](./STOREFRONT_STATUS.md) explaining the current status of Storefront development.

## Storefront extensions

Looking to take your storefront powered store to the next level? Be sure to checkout the premium [Storefront extensions](https://woocommerce.com/product-category/storefront-extensions/).

## Storefront Documentation

You can view detailed Storefront documentation on the [WooCommerce documentation website](https://woocommerce.com/documentation/themes/storefront/).

## Storefront in your language

Storefront translations can be downloaded from [WordPress.org](https://translate.wordpress.org/projects/wp-themes/storefront). To use one of these translations it is recommended that you upload it to `wp-content/languages/themes/storefront-pt_BR.mo`. Adding it to this location means the file will not be lost when you update Storefront. It is however possible to add a translation to the `languages` folder in your child theme if you'd prefer.

## Storefront help & support

WooCommerce customers can get support at the [WooCommerce support portal](https://woocommerce.com/contact-us/). Otherwise, you can try posting on the [WordPress support forums](https://wordpress.org/support/theme/storefront/). Please remember, GitHub is for bug reports and contributions, _not_ support.

## Contributing to Storefront

If you have a patch, or you've stumbled upon an issue with Storefront core, you can contribute this back to the code. Please read our [contributor guidelines](https://github.com/woocommerce/storefront/blob/master/CONTRIBUTING.md) for more information about how you can do this.

If you have an idea or feature request please take a look at the [Storefront Feature Requests](https://woocommerce.com/feature-requests/storefront/) to see if it's already been suggested, planned, or is under development. If not, please add it there.

You can keep up with the latest Storefront developments on the [dev blog](https://woocommerce.wordpress.com/category/storefront/).

## Testing Storefront

Want to help test upcoming Storefront releases? Check out the [Storefront Beta Tester](https://github.com/seb86/Storefront-Beta-Tester) plugin.
