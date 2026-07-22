<?php

namespace App\Http\Controllers;

use App\Models\Order;
use Inertia\Inertia;
use Inertia\Response;

class OrderWebController extends Controller
{
    public function index(): Response
    {
        $orders = Order::with(['outlet', 'user', 'items.product'])
            ->latest('placed_at')
            ->paginate(15);

        return Inertia::render('Dispatch/Orders/Index', [
            'orders' => $orders,
        ]);
    }
}
