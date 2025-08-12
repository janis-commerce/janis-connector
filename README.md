# Janis Connector [![Packagist Version](https://img.shields.io/packagist/v/janis-commerce/janis-connector.svg?style=flat-square)](https://packagist.org/packages/janis-commerce/janis-connector)


The purpose of this module is to create a connection to Janis Commerce API services. By doing this some information will be available in Magento 2 to be used.

Compatibility:
[![Version Status](https://img.shields.io/badge/Magento-2.3-orange.svg)]()
[![Version Status](https://img.shields.io/badge/Magento-2.4-orange.svg)]()
[![Composer Status](https://img.shields.io/badge/Composer-2-orange.svg)]()

# Features
- Easy to install
- Configuration via Magento 2 Backoffice
- Order Creation Notification to Janis
- Splitcart depending of Janis Configuration


# Installation and Configuration

### Installation via Composer

To install this module, just run the following command line:

```bash
composer require janis-commerce/janis-connector
```

For more information about JanisConnector via composer installation, follow us:

```bash
https://packagist.org/packages/janis-commerce/janis-connector
```

### Enable module

Once JanisConnector was installed, you need to enable the module. To do this, run the following next command lines over a magento project

```bash

    bin/magento cache:clean

    bin/magento module:enable JanisCommerce_JanisConnector

    bin/magento setup:upgrade

    # This is used to start the exclusive Janis Commerce crons.
    bin/magento cron:run --group=janis

```

### Parameter configurations

First at all, you have to localize the Backoffice Configurations Panel, then find out the "Stores" -> "Configuration", in left sidebar menu

![Magento Backoffice Configuration](https://github.com/janis-commerce/janis-connector/blob/master/Blob/images/image_1.png)

Click on "Janis Commerce" -> "Janis Connection"

![Janis Connection](https://github.com/janis-commerce/janis-connector/blob/master/Blob/images/image_2.png)

In this panel you'll find out the main Janis Connection configurations, to enable this module and allow it to communicate with Janis APIs.

![Janis Connection expanded](https://github.com/janis-commerce/janis-connector/blob/master/Blob/images/image_3.png)

To use the new Janis API where the slaName attribute is used, the Janis endpoint to receive a split cart field must be like this.

![Janis EndPoint](https://github.com/janis-commerce/janis-connector/src/master/blob/master/Blob/images/image_10.png)

Here you can see 2 main sections:

### Janis Connection
All this information must be requested to Janis Commerce and it will be provided depending of the client.

- Test Mode: Allows you to activate and deactivate the Testing Mode environment, which uses the following credentials, to connect to API services.

- CREDENTIALS FOR PRODUCTION ENVIRONMENT: When Test Mode is DISABLED, this credentials must be setup.
    - API Client: Janis Client code. Api Key Security Scheme Type.
    - API Key: Client data Key. Api Key Security Scheme Type.
    - API Secret: API secret data. Api Key Security Scheme Type.
    - API URL: Production Mode URL to target Janis Order Management System (OMS)(1.8.0).

- CREDENTIAL FOR TESTING ENVIRONMENT: When Test Mode is ENABLED, this credentials must be setup.
    - API Client [Test mode]: Janis Client code. Api Key Security Scheme Type.
    - API Key [Test mode]: Client data Key. Api Key Security Scheme Type.
    - API Secret [Test mode]: API secret data. Api Key Security Scheme Type.
    - API URL [Test mode]: Test Mode URL to target Janis Order Management System (OMS)(1.8.0).

### Orders

- Orders CRON job sync schedule with Janis: Configuration data that allows scheduling the periods in which the CRON process will run to send notifications to Janis.
- Janis account name: Data for connection to API services.
- Janis endpoint to notify order: URL for connection to Janis EndPoint, to which magento will send notifications of newly created orders.
- Janis endpoint to receive a split cart: URL for connection to Janis EndPoint, which returns the information of the division of the carts.
- Janis endpoint Sales Channel ID: Configuration data for the Sales Channel ID.

# Features

## Order Creation Notification
On one side we have the sending of notifications to janis, when a new order with an invoice has been created.

This is the body format built internally by this module:

```bash
{
    "accountName": customJanisAccountName,
    "orderId": orderIncrementIdCreated,
    "externalRef": orderIdCreated,
    "status": orderStatus,
    "statusCode": orderState
}
```

When Janis API Service receives the sended resquest, returns a response with the following format:

```bash
{
    "SendMessageResponse": {
        "ResponseMetadata": {
            "RequestId": "db0cdbe2-63fe-5202-8352-81afe0aa7d78"
        },
        "SendMessageResult": {
            "MD5OfMessageAttributes": "6515e53330941aeebeba4acdcec4078d",
            "MD5OfMessageBody": "915119691d28ecb53bbec55eddea7674",
            "MD5OfMessageSystemAttributes": null,
            "MessageId": "e1eb8a01-7728-47f4-abea-863157cec4e9",
            "SequenceNumber": null
        }
    }
}
```

This means that our request has been added to a waiting queue, to be able to process it.

This feature of the module comes integrated with a configurable **Cron Job**, which executes a scan of the orders created with an invoice and sends the respective notification to Janis Services.
Once Janis returns a satisfactory response, these orders will be excluded from future sweeps.

## Splitcart Request
On the other hand, this module has the functionality to request a splitcart payload from Janis API.

### Webapi URL

The webapi URL available to target is:
```bash
https://<my_domain>/rest/default/V1/split-cart
```

There are two format options to allow Client, to request information:

#### 1) Client Body format to allow the module to use quote items to build request
With this body format, JanisConnector add all items located in the cart, and sent them to get the splitcart payload.

```bash
{
    "slaName": "delivery", // ENUM: delivery | delivery | storePickup
    "dropoff": {
        "lat": latitude,
        "long": longitude
    }
}
```

#### 2) Client Body format to force the module to use custom products to build request
Also it is possible to add custom products, including **"skus"** field as is shown in the next example:

```bash
{
    "slaName": "delivery", // ENUM: delivery | delivery | storePickup
    "dropoff": {"lat": latitude, "long": longitude},
    "skus": [
        {"sku": productSku, "qty": quantity},
        {"sku": productSku, "qty": quantity},
        ...
    ]
}
```

Internally with those types of client requests, JanisConnector will build and send a body payload to a configurable Janis End Point, created with this format:

```bash
{
    "slaName": slaName
    "dropoff": {"coordinates":[latitude,longitude]},
    "salesChannel": {"referenceId": customId},
    "skus":
    [
        {"referenceId": productSku, "quantity": qty, "externalId": quoteItemId},
        ...
    ]
}
```
## Splitcart Request Variables
In this section there are some examples of request and responses from Janis Commerce:

According to 3 types of custom shipping methods available by Janis API, this module would create:

#### 1) Shipping type: delivery
Janis body request example:
```bash
{
    "slaName" : "delivery"
    "dropoff": {
        "coordinates": [0, 0]
    },
    "salesChannel": {
        "referenceId": "64654"
    },
    "skus": [
        {
            "referenceId": "13017878",
            "quantity": 1,
            "externalId": "5455"
        }
    ]
}
```

Janis payload response:

```bash
{
    "carts": [
        {
            "skus": [
                {
                    "referenceId": "13017878",
                    "quantity": 1,
                    "externalId": "5455"
                }
            ],
            "shippingOptions": [
                {
                    "carrierId": "6171c08a7605bc0008afb0de",
                    "carrierName": "Envío normal",
                    "slaName" : "delivery",
                    "shippingType": "delivery",
                    "price": 100,
                    "windows": [
                        {
                            "start": "2022-01-08T11:00:00.000Z",
                            "end": "2022-01-08T15:00:00.000Z",
                            "price": 0
                        },
                        {
                            "start": "2022-01-08T15:00:00.000Z",
                            "end": "2022-01-08T21:00:00.000Z",
                            "price": 100
                        },
                        {
                            "start": "2022-01-09T15:00:00.000Z",
                            "end": "2022-01-09T21:00:00.000Z",
                            "price": 0
                        }
                    ]
                }
            ]
        }
    ]
}
```

#### 2) Shipping type: storePickup
Janis body request example:
```bash
{
    "slaName" : "storePickup"
    "dropoff": {
        "coordinates": [
            0,
            0
        ]
    },
    "salesChannel": {
        "referenceId": "64654"
    },
    "skus": [
        {
            "referenceId": "13017878",
            "quantity": 1,
            "externalId": "5455"
        }
    ]
}
```

Janis payload response:

```bash
{
    "carts": [
        {
            "skus": [
                {
                    "referenceId": "13017878",
                    "quantity": 1,
                    "externalId": "5455"
                }
            ],
            "shippingOptions": [
                {
                    "carrierId": "618537a4f8928e0008376d55",
                    "carrierName": "Retiro en tienda",
                    "slaName" : "store_pickup"
                    "shippingType": "store_pickup",
                    "price": 0,
                    "estimatedDeliveryDate": "2021-10-18T18:00:00.000Z",
                    "pickupPoints": [
                        {
                            "id": "5d8bdac48ba45b0011913903",
                            "referenceId": "pp-0001",
                            "name": "Tienda #1",
                            "address": "Av. Principal # 1234, Región Metropolitana",
                            "schedule": [
                                {
                                    "day": "monday",
                                    "hours": [
                                        {
                                            "open": "08:00Z",
                                            "close": "12:00Z"
                                        },
                                        {
                                            "open": "13:00Z",
                                            "close": "20:00Z"
                                        }
                                    ]
                                }
                            ],
                            "coordinates": [
                                -70.682327717,
                                -33.3665865
                            ]
                        },
                        {
                            "id": "5d8bdac48ba45b0011913904",
                            "referenceId": "pp-0002",
                            "name": "Tienda #8",
                            "address": "Avenida Circunvalación # 112233, local 111, Región Metropolitana",
                            "schedule": [
                                {
                                    "day": "monday",
                                    "hours": [
                                        {
                                            "open": "12:00Z",
                                            "close": "20:00Z"
                                        }
                                    ]
                                },
                                {
                                    "day": "tuesday",
                                    "hours": [
                                        {
                                            "open": "12:00Z",
                                            "close": "20:00Z"
                                        }
                                    ]
                                },
                                {
                                    "day": "wednesday",
                                    "hours": [
                                        {
                                            "open": "12:00Z",
                                            "close": "20:00Z"
                                        }
                                    ]
                                }
                            ],
                            "coordinates": [
                                -70.54027597138355,
                                -33.41543295072441
                            ]
                        }
                    ]
                }
            ]
        }
    ]
}
```

#### 3) Shipping type: expressDelivery
Janis body request example:
```bash
{
    "slaName": "expressDelivery",
    "dropoff": {
        "coordinates": [
            0,
            0
        ]
    },
    "salesChannel": {
        "referenceId": "64654"
    },
    "skus": [
        {
            "referenceId": "13017878",
            "quantity": 1,
            "externalId": "5455"
        }
    ]
}
```

Janis payload response:

```bash
{
    "carts": [
        {
            "skus": [
                {
                    "referenceId": "13017878",
                    "quantity": 1,
                    "externalId": "5455"
                }
            ],
            "shippingOptions": [
                {
                    "carrierId": "618d34141c833a00085e3b46",
                    "carrierName": "Despacho Concepción",
                    "slaName" : "express_delivery",
                    "shippingType": "express_delivery",
                    "price": 100,
                    "estimatedDeliveryDate": "2022-01-08T23:24:08.413Z"
                }
            ]
        }
    ]
}
```
# Usage examples

There is a wide variety of uses that could be given to the functionalities provided by JanisConnector, among some we present:

### 1) Splitcart
A visual representation of the use of the splitcart would be the following:

Once the products have been loaded into the minicart/basket, as normal

![Splitcart example image 1](https://github.com/janis-commerce/janis-connector/blob/master/Blob/images/image_4.png)

When going to the next step and after having made the internal request to Janis, a cart with its respective subcarts would be shown

![Splitcart example image 2](https://github.com/janis-commerce/janis-connector/blob/master/Blob/images/image_5.png)

To improve the handling of this feature, you could use a third-party module, such as Attribute Base SplitCart.

For more information, follow their official website:

```bash
https://webkul.com/blog/magento2-cart-split-based-attribute/
```

### 2) Shipping methods available
With the information obtained from Janis, it is possible for us to put together a shipping method selector according to a product in which the customer is interested in purchasing. Thus we provide more accurate information and also provide greater comfort to said client.

![Shipping methods example](https://github.com/janis-commerce/janis-connector/blob/master/Blob/images/image_7.png)

### 3) Availability according to proximity

Using the customer's location as a reference, and the availability of the product they are looking at, it is possible to generate a map of nearby locations where they could purchase said product.

![Availability according to proximity example](https://github.com/janis-commerce/janis-connector/blob/master/Blob/images/image_8.png)

### 4) Shipping schedule and costs

Another scenario in which you can take advantage of the benefits of this module is when it could be made available to the customer, with information about the available shipping dates and their costs. In this way, the client can select the one that best suits their budget and urgency of acquiring the product.

![Shipping schedule and costs](https://github.com/janis-commerce/janis-connector/blob/master/Blob/images/image_9.png)

# Not included in this module
The Front End of Magento was not modified at all. This module only include the connection and the storage of the data retrieved from Janis Commerce.
i.e.: When the Split Cart information is retrieved, this won't be available in the FrontEnd, Magento must be customized to show the data according to the project.

