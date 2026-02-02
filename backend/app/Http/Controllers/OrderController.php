<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\OrderItem;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Carbon\Carbon;
use Illuminate\Support\Facades\Mail;

class OrderController extends Controller
{
    /**
     * Get all orders (with optional status filter)
     */
    public function index(Request $request): JsonResponse
    {
        $query = Order::with('items')->orderBy('created_at', 'desc');

        if ($request->has('status')) {
            $query->where('status', $request->input('status'));
        }

        $orders = $query->get()->map(function ($order) {
            return $this->formatOrder($order);
        });

        return response()->json($orders);
    }

    /**
     * Get single order by ID
     */
    public function show($id): JsonResponse
    {
        $order = Order::with('items')->find($id);

        if (!$order) {
            return response()->json(['error' => 'Order not found'], 404);
        }

        return response()->json($this->formatOrder($order));
    }

    /**
     * Get order by order number
     */
    public function showByNumber($orderNumber): JsonResponse
    {
        $order = Order::with('items')->where('order_number', $orderNumber)->first();

        if (!$order) {
            return response()->json(['error' => 'Order not found'], 404);
        }

        return response()->json($this->formatOrder($order));
    }

    /**
     * Create new order
     */
    public function store(Request $request): JsonResponse
    {
        $this->validate($request, [
            'contact' => 'nullable|email',
            'delivery' => 'required|array',
            'delivery.fullName' => 'required|string',
            'delivery.governorate' => 'required|string',
            'delivery.phone' => 'required|string',
            'delivery.phone2' => 'nullable|string',
            'delivery.addressDetails' => 'nullable|string',
            'notes' => 'nullable|string',
            'items' => 'required|array|min:1',
            'items.*.productId' => 'nullable|integer',
            'items.*.name' => 'required|string',
            'items.*.price' => 'required|numeric|min:0',
            'items.*.quantity' => 'required|integer|min:1',
            'subtotal' => 'required|numeric|min:0',
            'shipping' => 'required|numeric|min:0',
            'total' => 'required|numeric|min:0',
        ]);

        $delivery = $request->input('delivery');

        $order = Order::create([
            'order_number' => 'ORD-' . time(),
            'contact' => $request->input('contact'),
            'country' => $delivery['country'] ?? 'egypt',
            'full_name' => $delivery['fullName'],
            'address_details' => $delivery['addressDetails'] ?? null,
            'governorate' => $delivery['governorate'],
            'phone' => $delivery['phone'],
            'phone2' => $delivery['phone2'] ?? null,
            'notes' => $request->input('notes'),
            'shipping_method' => $request->input('shippingMethod', 'standard'),
            'payment_method' => $request->input('paymentMethod', 'cod'),
            'billing_address' => $request->input('billingAddress', 'same'),
            'subtotal' => $request->input('subtotal'),
            'shipping' => $request->input('shipping'),
            'total' => $request->input('total'),
            'status' => 'pending',
        ]);

        foreach ($request->input('items') as $item) {
            OrderItem::create([
                'order_id' => $order->id,
                'product_id' => $item['productId'],
                'name' => $item['name'],
                'price' => $item['price'],
                'image' => $item['image'] ?? null,
                'color' => $item['color'] ?? null,
                'size' => $item['size'] ?? null,
                'height' => $item['height'] ?? null,
                'quantity' => $item['quantity'],
                'added_at' => isset($item['addedAt']) ? $item['addedAt'] : Carbon::now(),
            ]);
        }

        $order->load('items');

        // Send notifications
        $this->sendTelegramNotification($order);
        $this->sendEmailNotification($order);

        return response()->json([
            'success' => true,
            'order' => $this->formatOrder($order),
            'message' => 'Order placed successfully',
        ], 201);
    }

    /**
     * Send Telegram notification
     */
    private function sendTelegramNotification(Order $order): void
    {
        $botToken = env('TELEGRAM_BOT_TOKEN');
        $chatIds = env('TELEGRAM_CHAT_ID');

        if (!$botToken || !$chatIds) {
            return;
        }

        // Parse chat IDs (support both single ID and array format [id1,id2])
        $chatIds = trim($chatIds, '[]');
        $chatIdArray = array_map('trim', explode(',', $chatIds));

        $itemsList = $order->items->map(function ($item, $index) {
            $itemTotal = $item->price * $item->quantity;
            $num = $index + 1;
            $text = "▫️ *{$num}. {$item->name}*\n";
            $text .= "   📏 المقاس: {$item->size}\n";
            $text .= "   🎨 اللون: {$item->color}\n";
            if ($item->height) {
                $text .= "   📐 الطول: {$item->height}\n";
            }
            $text .= "   🔢 الكمية: {$item->quantity}\n";
            $text .= "   💵 السعر: ج.م{$item->price} × {$item->quantity} = ج.م{$itemTotal}";
            return $text;
        })->implode("\n\n");

        $phoneText = $order->phone2 ? "{$order->phone} - {$order->phone2}" : $order->phone;

        $message = "🛒 *طلب جديد!*\n\n";
        $message .= "📦 *رقم الطلب:* `{$order->order_number}`\n\n";
        $message .= "━━━━━━━━━━━━━━━\n";
        $message .= "👤 *بيانات العميل:*\n";
        $message .= "الاسم: {$order->full_name}\n";
        if ($order->contact) {
            $message .= "📧 الايميل: {$order->contact}\n";
        }
        $message .= "📱 الهاتف: {$phoneText}\n";
        $message .= "📍 المحافظة: {$order->governorate}\n";
        if ($order->address_details) {
            $message .= "📝 تفاصيل العنوان: {$order->address_details}\n";
        }
        if ($order->notes) {
            $message .= "📋 ملاحظات: {$order->notes}\n";
        }
        $message .= "\n━━━━━━━━━━━━━━━\n";
        $message .= "🛍️ *المنتجات:*\n\n{$itemsList}\n\n";
        $message .= "━━━━━━━━━━━━━━━\n";
        $message .= "💰 *الإجمالي:*\n";
        $message .= "المنتجات: ج.م{$order->subtotal}\n";
        $message .= "الشحن: ج.م{$order->shipping}\n";
        $message .= "*المجموع: ج.م{$order->total}*\n\n";
        $message .= "💳 *طريقة الدفع:* " . ($order->payment_method === 'cod' ? 'الدفع عند الاستلام 💵' : $order->payment_method);

        // Send to all chat IDs
        foreach ($chatIdArray as $chatId) {
            if (empty($chatId)) continue;

            // Send main order message
            $this->sendTelegramMessage($botToken, $chatId, $message);

            // Send product images
            foreach ($order->items as $index => $item) {
                \Log::info("Order item {$index}: image = " . ($item->image ?? 'NULL'));
                if ($item->image) {
                    $num = $index + 1;
                    $caption = "📷 *صورة المنتج {$num}*\n";
                    $caption .= "{$item->name}\n";
                    $caption .= "اللون: {$item->color} | المقاس: {$item->size}";
                    if ($item->height) {
                        $caption .= " | الطول: {$item->height}";
                    }
                    $caption .= "\nالكمية: {$item->quantity}";

                    \Log::info("Sending Telegram photo for image: {$item->image}");
                    $this->sendTelegramPhoto($botToken, $chatId, $item->image, $caption);
                }
            }
        }
    }

    /**
     * Send Telegram text message
     */
    private function sendTelegramMessage(string $botToken, string $chatId, string $message): void
    {
        $url = "https://api.telegram.org/bot{$botToken}/sendMessage";

        $data = [
            'chat_id' => $chatId,
            'text' => $message,
            'parse_mode' => 'Markdown',
        ];

        $options = [
            'http' => [
                'method' => 'POST',
                'header' => 'Content-Type: application/x-www-form-urlencoded',
                'content' => http_build_query($data),
                'ignore_errors' => true,
            ],
            'ssl' => [
                'verify_peer' => false,
                'verify_peer_name' => false,
            ],
        ];

        $context = stream_context_create($options);
        @file_get_contents($url, false, $context);
    }

    /**
     * Get local file path from image URL
     */
    private function getLocalImagePath(string $image): ?string
    {
        // Extract path from URL
        $path = $image;

        // If it's a full URL, extract the path part
        if (str_starts_with($image, 'http://') || str_starts_with($image, 'https://')) {
            $parsed = parse_url($image);
            $path = $parsed['path'] ?? '';
        }

        // Remove leading slash
        $path = ltrim($path, '/');

        // Check if it's in storage/uploads
        if (str_starts_with($path, 'storage/')) {
            $localPath = base_path('public/' . $path);
            if (file_exists($localPath)) {
                return $localPath;
            }
        }

        // Try public path directly
        $localPath = base_path('public/' . $path);
        if (file_exists($localPath)) {
            return $localPath;
        }

        return null;
    }

    /**
     * Send Telegram photo (upload file directly using multipart form)
     */
    private function sendTelegramPhoto(string $botToken, string $chatId, string $imageUrl, string $caption): void
    {
        $url = "https://api.telegram.org/bot{$botToken}/sendPhoto";

        // Try to get local file path
        $localPath = $this->getLocalImagePath($imageUrl);

        if ($localPath && file_exists($localPath)) {
            // Upload file directly using multipart form data
            \Log::info("Uploading local file: {$localPath}");

            $boundary = '----' . md5(time());
            $fileContents = file_get_contents($localPath);
            $filename = basename($localPath);
            $mimeType = mime_content_type($localPath) ?: 'image/jpeg';

            // Build multipart body
            $body = '';

            // chat_id field
            $body .= "--{$boundary}\r\n";
            $body .= "Content-Disposition: form-data; name=\"chat_id\"\r\n\r\n";
            $body .= "{$chatId}\r\n";

            // caption field
            $body .= "--{$boundary}\r\n";
            $body .= "Content-Disposition: form-data; name=\"caption\"\r\n\r\n";
            $body .= "{$caption}\r\n";

            // parse_mode field
            $body .= "--{$boundary}\r\n";
            $body .= "Content-Disposition: form-data; name=\"parse_mode\"\r\n\r\n";
            $body .= "Markdown\r\n";

            // photo file
            $body .= "--{$boundary}\r\n";
            $body .= "Content-Disposition: form-data; name=\"photo\"; filename=\"{$filename}\"\r\n";
            $body .= "Content-Type: {$mimeType}\r\n\r\n";
            $body .= $fileContents . "\r\n";

            $body .= "--{$boundary}--\r\n";

            $options = [
                'http' => [
                    'method' => 'POST',
                    'header' => "Content-Type: multipart/form-data; boundary={$boundary}\r\n",
                    'content' => $body,
                    'ignore_errors' => true,
                ],
                'ssl' => [
                    'verify_peer' => false,
                    'verify_peer_name' => false,
                ],
            ];

            $context = stream_context_create($options);
            $response = @file_get_contents($url, false, $context);
            \Log::info("Telegram photo response: " . ($response ?: 'No response'));
        } else {
            // Fallback to URL method (for when APP_URL is a public domain)
            \Log::info("Sending photo by URL: {$imageUrl}");

            $data = [
                'chat_id' => $chatId,
                'photo' => $imageUrl,
                'caption' => $caption,
                'parse_mode' => 'Markdown',
            ];

            $options = [
                'http' => [
                    'method' => 'POST',
                    'header' => 'Content-Type: application/x-www-form-urlencoded',
                    'content' => http_build_query($data),
                    'ignore_errors' => true,
                ],
                'ssl' => [
                    'verify_peer' => false,
                    'verify_peer_name' => false,
                ],
            ];

            $context = stream_context_create($options);
            $response = @file_get_contents($url, false, $context);
            \Log::info("Telegram photo response: " . ($response ?: 'No response'));
        }
    }

    /**
     * Send Email notification
     */
    private function sendEmailNotification(Order $order): void
    {
        $to = env('MAIL_TO_ADDRESS', 'ahmedelsayedweb@gmail.com');
        $from = env('MAIL_FROM_ADDRESS', 'noreply@hoorstore.com');

        $itemsHtml = $order->items->map(function ($item) {
            return "<tr>
                <td style='padding: 10px; border-bottom: 1px solid #eee;'>{$item->name}</td>
                <td style='padding: 10px; border-bottom: 1px solid #eee;'>{$item->color} / {$item->size}</td>
                <td style='padding: 10px; border-bottom: 1px solid #eee;'>{$item->quantity}</td>
                <td style='padding: 10px; border-bottom: 1px solid #eee;'>ج.م{$item->price}</td>
            </tr>";
        })->implode('');

        $html = "
        <div style='font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto;'>
            <div style='background: #c9a66b; padding: 20px; text-align: center;'>
                <h1 style='color: white; margin: 0;'>HOOR STORE</h1>
            </div>

            <div style='padding: 20px;'>
                <h2 style='color: #333;'>طلب جديد #{$order->order_number}</h2>

                <div style='background: #f9f9f9; padding: 15px; border-radius: 5px; margin: 20px 0;'>
                    <h3 style='margin-top: 0;'>بيانات العميل</h3>
                    <p><strong>الاسم:</strong> {$order->full_name}</p>
                    " . ($order->contact ? "<p><strong>الايميل:</strong> {$order->contact}</p>" : "") . "
                    <p><strong>الهاتف:</strong> {$order->phone}" . ($order->phone2 ? " - {$order->phone2}" : "") . "</p>
                    <p><strong>المحافظة:</strong> {$order->governorate}</p>
                    " . ($order->address_details ? "<p><strong>تفاصيل العنوان:</strong> {$order->address_details}</p>" : "") . "
                    " . ($order->notes ? "<p><strong>ملاحظات:</strong> {$order->notes}</p>" : "") . "
                </div>

                <h3>المنتجات</h3>
                <table style='width: 100%; border-collapse: collapse;'>
                    <thead>
                        <tr style='background: #f5f5f5;'>
                            <th style='padding: 10px; text-align: left;'>المنتج</th>
                            <th style='padding: 10px; text-align: left;'>اللون/المقاس</th>
                            <th style='padding: 10px; text-align: left;'>الكمية</th>
                            <th style='padding: 10px; text-align: left;'>السعر</th>
                        </tr>
                    </thead>
                    <tbody>
                        {$itemsHtml}
                    </tbody>
                </table>

                <div style='margin-top: 20px; padding: 15px; background: #f9f9f9; border-radius: 5px;'>
                    <p><strong>المنتجات:</strong> ج.م{$order->subtotal}</p>
                    <p><strong>الشحن:</strong> ج.م{$order->shipping}</p>
                    <p style='font-size: 18px;'><strong>المجموع: ج.م{$order->total}</strong></p>
                </div>

                <div style='margin-top: 20px; padding: 15px; background: #e8f5e9; border-radius: 5px;'>
                    <p><strong>طريقة الدفع:</strong> " . ($order->payment_method === 'cod' ? 'الدفع عند الاستلام' : $order->payment_method) . "</p>
                </div>
            </div>

            <div style='background: #333; color: white; padding: 15px; text-align: center;'>
                <p style='margin: 0;'>HOOR Store - شكراً لك!</p>
            </div>
        </div>";

        $subject = "طلب جديد #{$order->order_number} - HOOR Store";

        $headers = "MIME-Version: 1.0\r\n";
        $headers .= "Content-type: text/html; charset=UTF-8\r\n";
        $headers .= "From: HOOR Store <{$from}>\r\n";

        @mail($to, $subject, $html, $headers);
    }

    /**
     * Update order status
     */
    public function update(Request $request, $id): JsonResponse
    {
        $order = Order::with('items')->find($id);

        if (!$order) {
            return response()->json(['error' => 'Order not found'], 404);
        }

        $allowedStatuses = ['pending', 'confirmed', 'processing', 'shipped', 'delivered', 'cancelled'];

        if ($request->has('status') && !in_array($request->input('status'), $allowedStatuses)) {
            return response()->json([
                'error' => 'Invalid status',
                'allowedStatuses' => $allowedStatuses,
            ], 400);
        }

        $order->update($request->only([
            'status',
            'shipping_method',
            'payment_method',
        ]));

        return response()->json($this->formatOrder($order));
    }

    /**
     * Delete order
     */
    public function destroy($id): JsonResponse
    {
        $order = Order::find($id);

        if (!$order) {
            return response()->json(['error' => 'Order not found'], 404);
        }

        $orderData = $this->formatOrder($order);
        $order->delete();

        return response()->json(['message' => 'Order deleted', 'order' => $orderData]);
    }

    /**
     * Format order for JSON response
     */
    private function formatOrder(Order $order): array
    {
        return [
            'id' => $order->id,
            'orderNumber' => $order->order_number,
            'contact' => $order->contact,
            'delivery' => [
                'country' => $order->country,
                'fullName' => $order->full_name,
                'addressDetails' => $order->address_details,
                'governorate' => $order->governorate,
                'phone' => $order->phone,
                'phone2' => $order->phone2,
            ],
            'notes' => $order->notes,
            'shippingMethod' => $order->shipping_method,
            'paymentMethod' => $order->payment_method,
            'billingAddress' => $order->billing_address,
            'items' => $order->items->map(function ($item) {
                return [
                    'id' => $item->id,
                    'productId' => $item->product_id,
                    'name' => $item->name,
                    'price' => $item->price,
                    'image' => $item->image,
                    'color' => $item->color,
                    'size' => $item->size,
                    'height' => $item->height,
                    'quantity' => $item->quantity,
                    'addedAt' => $item->added_at ? $item->added_at->toISOString() : null,
                ];
            }),
            'subtotal' => $order->subtotal,
            'shipping' => $order->shipping,
            'total' => $order->total,
            'status' => $order->status,
            'createdAt' => $order->created_at->toISOString(),
        ];
    }
}
