# Hello {{ $name }},

Your order has been successfully placed! We are processing your order and will notify you accordingly.

## Order Details:
- **Order ID:** {{ $order->identifier }}
- **Total Amount:** {{ $order->actual_price ?? $order->discount_price }}

If you have any questions or need further assistance, feel free to contact us.

Thank you for choosing {{ config('app.name') }}!

Best Regards,  
The {{ config('app.name') }} [Staging] Team