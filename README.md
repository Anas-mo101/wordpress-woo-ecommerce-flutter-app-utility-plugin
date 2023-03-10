## wordpress-woo-ecommerce-flutter-app-utility-plugin


![alt text](https://github.com/Anas-mo101/flutter-woo-ecommerce-app/blob/main/screenshots/app_arch.png?raw=true)

A WordPress plugin that helps integrate flutter ecommerce app [flutter-woo-ecommerce-app](https://github.com/Anas-mo101/flutter-woo-ecommerce-app) 


## Features
- Built with Getx State Management Package
- Follows MVC Architecture (kinda)
- Complete Purchase Workflow, from product to checkout (not including Purchase Gateway)
- API Caching System
- Internationalization
- Ads (not Google Adsense)
- Complains
- AR Virtual Try On -> branch: features/deep-ar-integration
- Image Detection Search -> branch: features/image-detection-search
- Social media integration (not done yet)
- Reels (not done yet)


## Missing
- Push notifications
- Analytics


## Aims
- To be compatible with any WordPress WooCommerce site
- To be lightweight and fast
- No Firebase ! or other 3rd party backend services
- Support Android and IOS

## API Reference

#### cart totals

```http
  POST https://<dn>/wp-json/app-utility/v1/totals
```
gets cart subtotals, tax total and total by providing line items


#### new complain

```http
  POST https://<dn>/wp-json/app-utility/v1/complain
```

submit a new complain message

#### complain response

```http
  POST https://<dn>/wp-json/app-utility/v1/complain/responed
```

send a follow up response to complain

#### get complain

```http
  GET https://<dn>/wp-json/app-utility/v1/complain/single?complain_id=XX
```

get a complain by id

#### all complains

```http
  GET https://<dn>/wp-json/app-utility/v1/complains
```

get all complains

#### shipping methods rates

```http
  GET https://<dn>/wp-json/app-utility/v1/shipping-methods-rates?zone_id=XX
```

get all shipping methods rate by zone

#### all ads

```http
  GET https://<dn>/wp-json/app-utility/v1/ads/all
```

get all ads

#### ads

```http
  GET https://<dn>/wp-json/app-utility/v1/ads
```

get ads

#### ads settings

```http
  GET https://<dn>/wp-json/app-utility/v1/ads/settings
```

get ads settings