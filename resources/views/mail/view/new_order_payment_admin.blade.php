@component('mail::message')
# Hello Admin,

A new order has been placed. Below are the details:

- **Order ID:** {{ $order->identifier }}
- **Pharmacy Name:** {{ $pharmacyName }}
- **Products Ordered:** {{ $productNames }}
- **Total Order Amount:** {{ $order->discount_price ?? $order->actual_price }}

Please review the order details as needed.

Best Regards,  
The {{ config('app.name') }} [Staging] Team
@endcomponent
