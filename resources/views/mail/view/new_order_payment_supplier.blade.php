@component('mail::message')
# Hello {{ $name }},

You have an order containing your product(s). Below are the details of the product(s) in the order and the amount you will receive:

- **Product(s) Ordered:** {{ $productNames }}
- **Amount (Excluding 10MG Commission):** {{ $order->discount_price ?? $order->actual_price }}
- **10MG Commission:** {{ $order_detail->tenmg_commission }}
- **Your Total to Receive:** {{ $netAmount }}

> Please note that this amount will appear in your wallet, but you will not be able to access it until the goods are delivered.

Thank you for being a part of {{ config('app.name') }}!

Best Regards,  
The {{ config('app.name') }} [Staging] Team
@endcomponent
