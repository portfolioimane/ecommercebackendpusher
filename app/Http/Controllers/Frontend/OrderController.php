<?php

namespace App\Http\Controllers\Frontend;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Cart; // Import Cart model
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Events\OrderPlaced;

class OrderController extends Controller
{
    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|max:255',
            'phone' => 'required|string|max:20',
            'address' => 'required|string|max:255',
            'total_price' => 'required|numeric',
            'payment_method' => 'required|string',
            'items' => 'required|array',
            'items.*.product_id' => 'required|exists:products,id',
            'items.*.quantity' => 'required|integer|min:1',
            'items.*.price' => 'required|numeric', // Add price validation
            'items.*.image' => 'sometimes|string|max:255', // Optional image validation
        ]);

        // Create the order
        $order = Order::create([
            'user_id' => Auth::id(), // Only if user is logged in
            'name' => $request->name,
            'email' => $request->email,
            'phone' => $request->phone,
            'address' => $request->address,
            'total_price' => $request->total_price,
            'payment_method' => $request->payment_method,
            'status' => 'pending', // Initial order status
        ]);

        // Create order items
        foreach ($request->items as $item) {
            OrderItem::create([
                'order_id' => $order->id,
                'product_id' => $item['product_id'],
                'quantity' => $item['quantity'],
                'price' => $item['price'], // Pass the price from the frontend
                'image' => $item['image'], // Optional
            ]);
        }

        // Clear the user's cart
        $cart = Cart::where('user_id', Auth::id())->first();
        if ($cart) {
            $cart->items()->delete(); // Delete all cart items
        }

        // Load the order items with their associated products
        $order->load('items.product');
        
        // Broadcast the event
        event(new OrderPlaced($order));

        return response()->json([
            'message' => 'Order placed successfully!',
            'order' => $order,
        ], 201);
    }
}
