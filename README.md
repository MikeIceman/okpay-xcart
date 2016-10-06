MikeIceman/xcart-plugin
=======================

# Installation

0. Important! Make a full backup of your store first!<br />
1. Copy OKPAY Checkout files into your xcart root directory, overwrite any existing files.<br />
2. In your XCart admin panel, go to System settings > Cache management and click "Re-deploy the store".<br />
3. Go to Modules section and enable OKPAY Paymet Gateway module.

# Configuration

1. In your XCart admin panel, go to Store setup > Payment Methods.<br />
2. Click "Add payment method" button.<br />
3. Find "OKPAY" payment method and click "Add".<br />
4. Configure OKPAY payment method with your credentials.

*Note: Turn off any external HTML minification as XCart uses SSI includes to pass dynamic data from PHP to HTML sections. HTML minifiers strip this information out. CDN's such as MaxCDN and Cloudflare will have HTML minification on by default so make sure to check and turn it off. JS/CSS minification and optimization is OK.  

# Usage

When a shopper chooses the OKPAY payment method, they will be redirected to OKPAY Checkout where they will pay an invoice.  OKPAY Checkout will then notify your Xcart system that the order was paid for.  The customer will be redirected back to your store.  

The order status in the admin panel will be "Processed" if payment has been confirmed. 


# Support

## OKPAY Support

* [Homepage](https://www.okpay.com/)
* [Developer Center](http://dev.okpay.com/)
* [Support System](https://support.okpay.com/)
* [Support Forums](http://forum.okpay.com/)

## X-Cart Support

* [Homepage](http://www.x-cart.com/ecommerce-software.html)
* [Documentation](http://kb.x-cart.com/display/XDD/Definitive+guide)
* [Support Forums](http://forum.x-cart.com)

# Contribute

To contribute to this project, please fork and submit a pull request.

# License

The MIT License (MIT)

Copyright (c) 2007-2016 OKPAY

Permission is hereby granted, free of charge, to any person obtaining a copy
of this software and associated documentation files (the "Software"), to deal
in the Software without restriction, including without limitation the rights
to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
copies of the Software, and to permit persons to whom the Software is
furnished to do so, subject to the following conditions:

The above copyright notice and this permission notice shall be included in
all copies or substantial portions of the Software.

THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN
THE SOFTWARE.
